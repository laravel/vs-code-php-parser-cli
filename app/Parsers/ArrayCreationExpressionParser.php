<?php

namespace App\Parsers;

use App\Contexts\ArrayValue;
use App\Contexts\AbstractContext;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;

class ArrayCreationExpressionParser extends AbstractParser
{
    /**
     * @var ArrayValue
     */
    protected AbstractContext $context;

    public function parse(ArrayCreationExpression $node)
    {
        $this->context->autocompleting = $node->closeParenOrBracket instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ArrayValue;
    }
}
