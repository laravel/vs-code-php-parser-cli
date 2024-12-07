<?php

namespace App\Contexts;

class ArrayValue extends AbstractContext
{
    public function type(): string
    {
        return 'array';
    }
}
