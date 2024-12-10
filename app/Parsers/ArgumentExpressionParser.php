<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Argument;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;

class ArgumentExpressionParser extends AbstractParser
{
    /**
     * @var Argument
     */
    protected AbstractContext $context;

    public function parse(ArgumentExpression $node)
    {
        $this->context->name = $node->name?->getText($node->getFileContents());

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Argument;
    }
}
