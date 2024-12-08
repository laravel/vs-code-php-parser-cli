<?php

namespace App\Contexts;

use App\Contexts\Contracts\HasParameters;

class ClosureValue extends AbstractContext implements HasParameters
{
    public Parameters $parameters;

    public function init()
    {
        $this->parameters = new Parameters;
    }

    public function type(): string
    {
        return 'closure';
    }

    public function castToArray(): array
    {
        return [
            'parameters' => $this->parameters->toArray(),
        ];
    }
}
