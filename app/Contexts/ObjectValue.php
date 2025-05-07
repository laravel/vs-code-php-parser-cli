<?php

namespace App\Contexts;

class ObjectValue extends AbstractContext
{
    public bool $findable = true;

    public ?string $className = null;

    public Arguments $arguments;

    public function init()
    {
        $this->arguments = new Arguments;
        $this->arguments->parent = $this;
    }

    public function type(): string
    {
        return 'object';
    }

    public function castToArray(): array
    {
        return [
            'className' => $this->className,
            'arguments' => $this->arguments->toArray(),
        ];
    }
}
