<?php

namespace App\Contexts;

class Parameters extends BaseContext
{
    public function type(): string
    {
        return 'parameters';
    }

    public function castToArray(): array
    {
        return [];
    }
}
