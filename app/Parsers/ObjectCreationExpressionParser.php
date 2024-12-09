<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ObjectValue;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;

class ObjectCreationExpressionParser extends AbstractParser
{
    /**
     * @var ObjectValue
     */
    protected AbstractContext $context;

    public function parse(ObjectCreationExpression $node)
    {
        $this->context->className = (string) $node->classTypeDesignator->getResolvedName();
        $this->context->autocompleting = $node->closeParen instanceof MissingToken;

        return $this->context->arguments;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ObjectValue;
    }
}
