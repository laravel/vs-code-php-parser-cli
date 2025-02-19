<?php

namespace App\Contexts;

use Illuminate\Support\Arr;
use Microsoft\PhpParser\Range;

abstract class AbstractContext
{
    public array $children = [];

    public bool $autocompleting = false;

    protected array $freshObject;

    protected bool $hasChildren = true;

    public ?AbstractContext $parent = null;

    public array $start = [];

    public array $end = [];

    abstract public function type(): string;

    public function castToArray(): array
    {
        return [];
    }

    public function __construct(protected $label = '')
    {
        if (method_exists($this, 'init')) {
            call_user_func([$this, 'init']);
        }

        $this->freshObject = $this->freshArray();
    }

    public function flip()
    {
        return array_merge(
            Arr::except($this->toArray(), ['children']),
            ['parent' => $this->parent?->flip()],
        );
    }

    public function findAutocompleting(?AbstractContext $context = null)
    {
        $context = $context ?? $this;
        $result = $this->seachForAutocompleting($context, true);
        $lastResult = null;

        while ($result !== null) {
            $lastResult = $result;
            $result = $this->seachForAutocompleting($result);
        }

        return $lastResult;
    }

    protected function seachForAutocompleting(AbstractContext $context, $checkCurrent = false)
    {
        if ($checkCurrent && $context->autocompleting && ($context instanceof MethodCall || $context instanceof ObjectValue)) {
            return $context;
        }

        $publicProps = Arr::except(get_object_vars($context), ['freshObject', 'parent']);

        foreach ($publicProps as $child) {
            $child = is_array($child) ? $child : [$child];

            foreach ($child as $subChild) {
                if ($subChild instanceof AbstractContext) {
                    $result = $this->findAutocompleting($subChild);

                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    protected function freshArray()
    {
        return $this->toArray();
    }

    public function initNew(AbstractContext $newContext)
    {
        $newContext->parent = $this;

        $this->children[] = $newContext;

        return $newContext;
    }

    public function searchForVar(?string $name): AssignmentValue|string|null
    {
        if ($name === null) {
            return null;
        }

        if (property_exists($this, 'parameters') && $this->parameters instanceof Parameters) {
            foreach ($this->parameters->children as $param) {
                if ($param->name === $name) {
                    return $param->types[0] ?? null;
                }
            }
        }

        foreach ($this->children as $child) {
            if ($child instanceof Assignment && $child->name === $name) {
                return $child->value;
            }
        }

        return $this->parent?->searchForVar($name) ?? null;
    }

    public function addPropertyToNearestClassDefinition(?string $name, $types = [])
    {
        if ($name === null) {
            return;
        }

        if ($this instanceof ClassDefinition) {
            $this->properties[] = [
                'name'  => $name,
                'types' => $types,
            ];
        } else {
            $this->parent?->addPropertyToNearestClassDefinition($name, $types);
        }
    }

    public function nearestClassDefinition()
    {
        if ($this instanceof ClassDefinition) {
            return $this;
        }

        return $this->parent?->nearestClassDefinition() ?? null;
    }

    public function searchForProperty(string $name)
    {
        if ($this instanceof ClassDefinition) {
            return collect($this->properties)->first(fn ($prop) => $prop['name'] === $name);
        }

        return $this->parent?->searchForProperty($name) ?? null;
    }

    public function pristine(): bool
    {
        return $this->freshObject === $this->freshArray();
    }

    public function touched(): bool
    {
        return !$this->pristine();
    }

    public function toArray(): array
    {
        return array_merge(
            ['type' => $this->type()],
            $this->autocompleting ? ['autocompleting' => true] : [],
            $this->castToArray(),
            ($this->label !== '') ? ['label' => $this->label] : [],
            (count($this->start) > 0) ? ['start' => $this->start] : [],
            (count($this->end) > 0) ? ['end' => $this->end] : [],
            ($this->hasChildren)
                ? ['children' => array_map(fn ($child) => $child->toArray(), $this->children)]
                : [],
        );
    }

    public function toJson($flags = 0)
    {
        return json_encode($this->toArray(), $flags);
    }

    public function setPosition(Range $range)
    {
        $this->start = [
            'line'   => $range->start->line,
            'column' => $range->start->character,
        ];

        $this->end = [
            'line'   => $range->end->line,
            'column' => $range->end->character,
        ];
    }
}
