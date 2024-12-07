<?php

namespace App\Contexts;

class Parameters extends AbstractContext
{
    public function type(): string
    {
        return 'parameters';
    }
    public function toArray(): array
    {
        return parent::toArray()['children'];
    }
}
