<?php

namespace App\Contexts;

class MethodCall extends AbstractContext
{
    public bool $isAbleToAutocomplete = true;

    public ?string $methodName = null;

    public ?string $className = null;

    public Arguments $arguments;

    public function init()
    {
        $this->arguments = new Arguments;
        $this->arguments->parent = $this;
    }

    public function type(): string
    {
        return 'methodCall';
    }

    public function castToArray(): array
    {
        return [
            'methodName' => $this->methodName,
            'className'  => $this->className,
            'arguments'  => $this->arguments->toArray(),
        ];
    }

    public function name()
    {
        return $this->methodName;
    }
}
