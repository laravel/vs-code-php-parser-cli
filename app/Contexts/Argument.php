<?php

namespace App\Contexts;

class Argument extends AbstractContext
{
    public function type(): string
    {
        return 'argument';
    }

    public function toArray(): array
    {
        return parent::toArray()['children'][0] ?? [];
    }
}
