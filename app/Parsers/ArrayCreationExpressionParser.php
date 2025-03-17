<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ArrayValue;
use App\Contexts\ObjectValue;
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

    private function isParentNode(Node $node, array $nodeClasses): bool
    {
        if ($node->getParent() !== null) {
            if (in_array(get_class($node->getParent()), $nodeClasses)) {
                return true;
            }

            return $this->isParentNode($node->getParent(), $nodeClasses);
        }

        return false;
    }

    public function parse(ArrayCreationExpression $node)
    {
        // If array is inside a method, for example Validator::validate(['
        // then we need to ignore findable for ArrayValue because
        // priority is given to App\Contexts\MethodCall or App\Contexts\ObjectValue
        if (!$this->isParentNode($node, [CallExpression::class, ObjectValue::class])) {
            $this->context->findable = true;
        }

        $this->context->autocompleting = $node->closeParenOrBracket instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ArrayValue;
    }
}
