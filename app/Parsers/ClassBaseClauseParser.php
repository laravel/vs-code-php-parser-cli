<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ClassDefinition;
use Microsoft\PhpParser\Node\ClassBaseClause;

class ClassBaseClauseParser extends AbstractParser
{
    /**
     * @var ClassDefinition
     */
    protected AbstractContext $context;

    public function parse(ClassBaseClause $node)
    {
        $this->context->extends = (string) $node->baseClass->getResolvedName();

        return $this->context;
    }
}
