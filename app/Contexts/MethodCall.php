<?php

namespace App\Contexts;

class MethodCall extends AbstractContext
{
    public ?string $name = null;

    public ?string $class = null;

    public Arguments $arguments;

    public function init()
    {
        $this->arguments = new Arguments;
    }

    public function type(): string
    {
        return 'methodCall';
    }

    public function castToArray(): array
    {
        return [
            'name'      => $this->name,
            'class'     => $this->class,
            'arguments' => $this->arguments->toArray(),
        ];
    }
}
