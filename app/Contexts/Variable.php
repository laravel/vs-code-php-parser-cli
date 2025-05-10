<?php

namespace App\Contexts;

class Variable extends AbstractContext
{
    public ?string $name = null;

    protected bool $hasChildren = false;

    public function type(): string
    {
        return 'variable';
    }

    public function castToArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
