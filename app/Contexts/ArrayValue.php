<?php

namespace App\Contexts;

class ArrayValue extends BaseContext
{
    public function type(): string
    {
        return 'array';
    }
}
