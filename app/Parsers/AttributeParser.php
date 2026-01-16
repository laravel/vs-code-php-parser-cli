<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ObjectValue;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Attribute as AttributeNode;
use Microsoft\PhpParser\Node\QualifiedName;

class AttributeParser extends AbstractParser
{
    /**
     * @var ObjectValue
     */
    protected AbstractContext $context;

    public function parse(AttributeNode $node)
    {
        $this->context->className = $node->name instanceof QualifiedName
            ? $node->name->getResolvedName()
            : $node->name->getText();

        $this->context->autocompleting = $node->closeParen instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ObjectValue();
    }
}
