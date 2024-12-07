<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\StringValue;
use Microsoft\PhpParser\Node\StringLiteral;

class StringLiteralParser extends AbstractParser
{
    /**
     * @var StringValue
     */
    protected AbstractContext $context;

    public function parse(StringLiteral $node)
    {
        $this->context->value = $node->getStringContentsText();

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new StringValue;
    }
}
