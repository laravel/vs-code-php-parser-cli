<?php

namespace App\Contexts;

use App\Contexts\Contracts\PossibleAutocompleting;

class ObjectValue extends AbstractContext implements PossibleAutocompleting
{
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
