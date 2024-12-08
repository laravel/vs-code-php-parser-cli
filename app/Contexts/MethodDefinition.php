<?php

namespace App\Contexts;

use App\Contexts\Contracts\HasParameters;

class MethodDefinition extends AbstractContext implements HasParameters
{
    public Parameters $parameters;

    public ?string $name = null;

    public function init()
    {
        $this->parameters = new Parameters;
    }

    public function type(): string
    {
        return 'methodDefinition';
    }

    public function castToArray(): array
    {
        return [
            'name'       => $this->name,
            'parameters' => $this->parameters->toArray(),
        ];
    }
}
