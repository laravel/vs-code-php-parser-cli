<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodDefinition;
use Microsoft\PhpParser\Node\MethodDeclaration;

class MethodDeclarationParser extends AbstractParser
{
    /**
     * @var MethodDefinition
     */
    protected AbstractContext $context;

    public function parse(MethodDeclaration $node)
    {
        $this->context->name = $node->getName();

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new MethodDefinition;
    }
}
