<?php

namespace App\Contexts;

class Assignment extends BaseContext
{
    public ?string $name = null;

    public AssignmentValue $value;

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
            'name' => $this->name,
            'value' => $this->value->toArray(),
        ];
    }
}
