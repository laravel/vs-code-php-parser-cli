<?php

namespace App\Contexts;

use Illuminate\Support\Arr;

class Parameter extends AbstractContext
{
    public ?string $name = null;

    public array $types = [];

    public ParameterValue $value;

    protected bool $hasChildren = false;

    public function init()
    {
        $this->value = new ParameterValue;
    }

    public function type(): string
    {
        return 'parameter';
    }

    public function castToArray(): array
    {
        return [
            'types' => $this->types,
            'name'  => $this->name,
            // 'value' => $this->value->toArray(),
        ];
    }

    public function toArray(): array
    {
        return Arr::except(parent::toArray(), ['type']);
    }
}
