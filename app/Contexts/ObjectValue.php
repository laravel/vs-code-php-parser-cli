<?php

namespace App\Contexts;

class ObjectValue extends AbstractContext
{
    public ?string $name = null;

    public Arguments $arguments;

    public function init()
    {
        $this->arguments = new Arguments;
    }

    public function type(): string
    {
        return 'object';
    }

    public function castToArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
