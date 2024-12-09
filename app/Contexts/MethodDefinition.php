<?php

namespace App\Contexts;

use App\Contexts\Contracts\HasParameters;

class MethodDefinition extends AbstractContext implements HasParameters
{
    public Parameters $parameters;

    public ?string $methodName = null;

    public function init()
    {
        $this->parameters = new Parameters;
        $this->parameters->parent = $this;
    }

    public function type(): string
    {
        return 'methodDefinition';
    }

    public function castToArray(): array
    {
        return [
            'methodName'       => $this->methodName,
            'parameters'       => $this->parameters->toArray(),
        ];
    }
}
