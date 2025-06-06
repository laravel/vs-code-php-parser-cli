<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ObjectValue;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\QualifiedName;

class ObjectCreationExpressionParser extends AbstractParser
{
    /**
     * @var ObjectValue
     */
    protected AbstractContext $context;

    public function parse(ObjectCreationExpression $node)
    {
        if ($node->classTypeDesignator instanceof QualifiedName) {
            $this->context->className = (string) $node->classTypeDesignator->getResolvedName();
        }

        $this->context->autocompleting = $node->closeParen instanceof MissingToken;

        if ($node->argumentExpressionList === null) {
            return $this->context;
        }

        return $this->context->arguments;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ObjectValue;
    }
}
