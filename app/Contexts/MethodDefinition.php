<?php

namespace App\Contexts;

class MethodDefinition extends BaseContext
{
    public ?string $name = null;

    public function type(): string
    {
        return 'methodDefinition';
    }

    public function castToArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
