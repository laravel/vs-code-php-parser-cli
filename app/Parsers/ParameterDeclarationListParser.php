<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Contracts\HasParameters;

class ParameterDeclarationListParser extends AbstractParser
{
    /**
     * @var HasParameters
     */
    protected AbstractContext $context;

    public function parse($node)
    {
        return $this->context->parameters;
    }
}
