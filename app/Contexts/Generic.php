<?php

namespace App\Contexts;

class Generic extends BaseContext
{

    public function type(): string
    {
        return 'generic';
    }

    public function castToArray(): array
    {
        return [];
    }
}
