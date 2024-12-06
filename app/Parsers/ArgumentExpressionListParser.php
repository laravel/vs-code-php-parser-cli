<?php

namespace App\Parsers;

use App\Contexts\Argument;
use App\Contexts\AssignmentValue;
use App\Contexts\MethodCall;
use App\Contexts\BaseContext;

class ArgumentExpressionListParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected BaseContext $context;

    public function parse($node)
    {
        if ($this->context instanceof MethodCall) {
            return $this->context->arguments;
        }
        // if (!property_exists($this->context, 'arguments')) {
        //     dd($this->context, $node->getText());
        // }

        return $this->context;
    }
}
