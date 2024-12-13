<?php

namespace App\Contexts;

class Blade extends AbstractContext
{
    public function type(): string
    {
        return 'blade';
    }
}
