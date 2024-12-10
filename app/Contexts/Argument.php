<?php

namespace App\Contexts;

class Argument extends AbstractContext
{
    public ?string $name = null;

    public function type(): string
    {
        return 'argument';
    }

    public function castToArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function isAutoCompleting(): bool
    {
        if ($this->autocompleting) {
            return true;
        }

        return collect($this->children)->first(
            fn($child) => $child->autocompleting
        ) !== null;
    }
}
