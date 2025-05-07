<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ArrayValue;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;

class ArrayCreationExpressionParser extends AbstractParser
{
    /**
     * @var ArrayValue
     */
    protected AbstractContext $context;

    public function parse(ArrayCreationExpression $node)
    {
        // If array is inside a method, for example Validator::validate(['
        // then we need to ignore isAbleToAutocomplete for ArrayValue because
        // priority is given to App\Contexts\MethodCall or App\Contexts\ObjectValue
        if (!$this->parentNodeIs($node, [CallExpression::class, ObjectCreationExpression::class])) {
            $this->context->isAbleToAutocomplete = true;
        }

        $this->context->autocompleting = $node->closeParenOrBracket instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ArrayValue;
    }
}
