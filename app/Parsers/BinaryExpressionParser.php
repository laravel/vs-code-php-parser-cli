<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Binary;
use Microsoft\PhpParser\Node\Expression\BinaryExpression;

class BinaryExpressionParser extends AbstractParser
{
    /**
     * @var Binary
     */
    protected AbstractContext $context;

    public function parse(BinaryExpression $node)
    {
        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Binary;
    }
}
