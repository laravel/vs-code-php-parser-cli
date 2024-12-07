<?php

namespace App\Parsers;

use App\Contexts\ObjectValue;
use App\Contexts\AbstractContext;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;

class ObjectCreationExpressionParser extends AbstractParser
{
    /**
     * @var ObjectValue
     */
    protected AbstractContext $context;

    public function parse(ObjectCreationExpression $node)
    {
        $this->context->name = (string) $node->classTypeDesignator->getResolvedName();

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ObjectValue;
    }
}
