<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;

class ExpressionStatementParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(ExpressionStatement $node)
    {
        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return null;

        if (!($this->context instanceof MethodCall)) {
            return new MethodCall;
        }

        return null;
    }
}
