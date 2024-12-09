<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Argument;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;

class ArgumentExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(ArgumentExpression $node)
    {
        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Argument;
    }
}
