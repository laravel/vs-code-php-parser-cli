<?php

namespace App\Parser\Parsers;

use Microsoft\PhpParser\Node\Statement\ClassDeclaration;

class ClassDeclarationParser extends AbstractParser
{
    use InitsNewContext;

    public function parse(ClassDeclaration $node)
    {
        $this->context->classDefinition = (string) $node->getNamespacedName();

        if ($node->classBaseClause) {
            $this->context->extends = (string) $node->classBaseClause->baseClass->getNamespacedName();
        }

        if ($node->classInterfaceClause) {
            foreach ($node->classInterfaceClause->interfaceNameList->getElements() as $element) {
                $this->context->implements[] = (string) $element->getResolvedName();
            }
        }

        // $this->loopChildren($node);

        return $this->context;
    }
}
