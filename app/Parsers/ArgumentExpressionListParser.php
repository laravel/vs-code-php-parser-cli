<?php

namespace App\Parsers;

use App\Contexts\Argument;
use App\Contexts\MethodCall;
use App\Contexts\BaseContext;

class ArgumentExpressionListParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected BaseContext $context;

    public function parse()
    {
        if ($this->context instanceof Argument) {
            return $this->context;
        }

        return $this->context->arguments;
    }
}
