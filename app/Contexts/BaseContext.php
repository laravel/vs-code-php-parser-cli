<?php

namespace App\Contexts;

abstract class BaseContext
{
    public $children = [];

    protected array $freshObject;

    protected bool $hasChildren = true;

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

    public function initNew(BaseContext $newContext)
    {
        // if ($this->pristine()) {
        //     return $this;
        // }

        $this->children[] = $newContext;

        return $newContext;
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
