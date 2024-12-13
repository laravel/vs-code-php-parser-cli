<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ClassDefinition;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\PropertyDeclaration;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Token;

class PropertyDeclarationParser extends AbstractParser
{
    /**
     * @var ClassDefinition
     */
    protected AbstractContext $context;

    public function parse(PropertyDeclaration $node)
    {
        $property = [
            'types' => [],
        ];

        $name = null;

        if ($node->propertyElements) {
            foreach ($node->propertyElements->getElements() as $element) {
                if ($element instanceof Variable) {
                    $name = $element->getName();
                }
            }
        }

        if ($node->typeDeclarationList) {
            foreach ($node->typeDeclarationList->getValues() as $type) {
                if ($type instanceof Token) {
                    $property['types'][] = $type->getText($node->getRoot()->getFullText());
                } elseif ($type instanceof QualifiedName) {
                    $property['types'][] = (string) $type->getResolvedName();
                }
            }
        }

        if ($name !== null) {
            $property['name'] = $name;
            $this->context->properties[] = $property;
        }

        return $this->context;
    }
}
