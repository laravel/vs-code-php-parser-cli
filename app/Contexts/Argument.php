<?php

namespace App\Contexts;

class Argument extends BaseContext
{
    public function type(): string
    {
        return 'argument';
    }

    public function castToArray(): array
    {
        return [];
    }
}
