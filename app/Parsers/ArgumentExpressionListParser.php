<?php

namespace App\Parsers;

use App\Contexts\MethodCall;
use App\Contexts\AbstractContext;

class ArgumentExpressionListParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse($node)
    {
        if ($this->context instanceof MethodCall) {
            return $this->context->arguments;
        }

        return $this->context;
    }
}
