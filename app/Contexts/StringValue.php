<?php

namespace App\Contexts;

class StringValue extends BaseContext
{
    public ?string $value = null;

    protected bool $hasChildren = false;

    public function type(): string
    {
        return 'string';
    }

    public function castToArray(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
