<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ClassDefinition;
use Microsoft\PhpParser\Node\ClassInterfaceClause;

class ClassInterfaceClauseParser extends AbstractParser
{
    /**
     * @var ClassDefinition
     */
    protected AbstractContext $context;

    public function parse(ClassInterfaceClause $node)
    {
        if (!$node->interfaceNameList) {
            return $this->context;
        }

        foreach ($node->interfaceNameList->getElements() as $element) {
            $this->context->implements[] = (string) $element->getResolvedName();
        }

        return $this->context;
    }
}
