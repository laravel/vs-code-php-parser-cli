<?php

namespace App\Contexts;

abstract class AbstractContext
{
    public $children = [];

    protected array $freshObject;

    protected bool $hasChildren = true;

    protected ?AbstractContext $parent = null;

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

    public function searchForVar(string $name): AssignmentValue | string | null
    {
        if ($this instanceof ClosureValue) {
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
            [
                'type' => $this->type(),
            ],
            $this->castToArray(),
            ($this->label !== '') ? ['label' => $this->label] : [],
            ($this->hasChildren) ? ['children' => array_map(fn($child) => $child->toArray(), $this->children)] : [],
        );
    }

    public function toJson($flags = 0)
    {
        return json_encode($this->toArray(), $flags);
    }
}
