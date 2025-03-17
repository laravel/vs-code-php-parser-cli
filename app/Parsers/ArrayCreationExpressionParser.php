<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ArrayValue;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;

class ArrayCreationExpressionParser extends AbstractParser
{
    /**
     * @var ArrayValue
     */
    protected AbstractContext $context;

    private function isParentNode(Node $node, string $nodeClass): bool
    {
        if ($node->getParent() !== null) {
            if ($node->getParent() instanceof $nodeClass) {
                return true;
            }

            return $this->isParentNode($node->getParent(), $nodeClass);
        }

        return false;
    }

    public function parse(ArrayCreationExpression $node)
    {
        // If array is inside a method, for example Validator::validate(['
        // then we need to ignore autocompleting for ArrayValue because
        // priority is given to App\Contexts\MethodCall
        if (!$this->isParentNode($node, CallExpression::class)) {
            $this->context->autocompleting = $node->closeParenOrBracket instanceof MissingToken;
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ArrayValue;
    }
}
