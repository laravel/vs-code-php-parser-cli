<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ClassDefinition;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;

class ClassDeclarationParser extends AbstractParser
{
    /**
     * @var ClassDefinition
     */
    protected AbstractContext $context;

    public function parse(ClassDeclaration $node)
    {
        $this->context->name = (string) $node->getNamespacedName();

        if ($node->classBaseClause) {
            $this->context->extends = (string) $node->classBaseClause->baseClass->getNamespacedName();
        }

        if ($node->classInterfaceClause) {
            foreach ($node->classInterfaceClause->interfaceNameList->getElements() as $element) {
                $this->context->implements[] = (string) $element->getResolvedName();
            }
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ClassDefinition();
    }
}
