<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use App\Contexts\ObjectValue;

class ArgumentExpressionListParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse($node)
    {
        if ($this->context instanceof MethodCall || $this->context instanceof ObjectValue) {
            return $this->context->arguments;
        }

        return $this->context;
    }
}
