<?php

namespace App\Parsers;

use App\Contexts\BaseContext;
use App\Contexts\StringValue;
use Microsoft\PhpParser\Node\StringLiteral;

class StringLiteralParser extends AbstractParser
{
    /**
     * @var StringValue
     */
    protected BaseContext $context;

    public function parse(StringLiteral $node)
    {
        $this->context->value = $node->getStringContentsText();

        return $this->context;
    }

    public function initNewContext(): ?BaseContext
    {
        return new StringValue;
    }
}
