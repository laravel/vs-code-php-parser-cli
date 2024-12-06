<?php

namespace App\Contexts;

class Arguments extends BaseContext
{
    public function type(): string
    {
        return 'arguments';
    }

    public function toArray(): array
    {
        return parent::toArray()['children'];
    }
}
