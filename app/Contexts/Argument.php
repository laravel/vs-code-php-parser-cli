<?php

namespace App\Contexts;

class Argument extends AbstractContext
{
    public function type(): string
    {
        return 'argument';
    }

    public function isAutoCompleting(): bool
    {
        if ($this->autocompleting) {
            return true;
        }

        return collect($this->children)->first(
            fn ($child) => $child->autocompleting
        ) !== null;
    }
}
