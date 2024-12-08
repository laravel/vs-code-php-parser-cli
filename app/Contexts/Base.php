<?php

namespace App\Contexts;

class Base extends AbstractContext
{
    public function type(): string
    {
        return 'base';
    }
}
