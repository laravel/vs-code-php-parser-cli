<?php

namespace App\Contexts;

class Variable extends AbstractContext
{
    public ?string $varName = null;

    public ?string $className = null;

    protected bool $hasChildren = false;

    public function type(): string
    {
        return 'variable';
    }

    public function castToArray(): array
    {
        return [
            'varName' => $this->varName,
            'className' => $this->className,
        ];
    }
}
