<?php

namespace App\Contexts;

class Assignment extends AbstractContext
{
    public ?string $name = null;

    public AssignmentValue $value;

    protected bool $hasChildren = false;

    public function init()
    {
        $this->value = new AssignmentValue;
    }

    public function type(): string
    {
        return 'assignment';
    }

    public function castToArray(): array
    {
        return [
            'name'  => $this->name,
            'value' => $this->value->toArray(),
        ];
    }
}
