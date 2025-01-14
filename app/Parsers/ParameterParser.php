<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Parameter;
use Microsoft\PhpParser\Node\Parameter as NodeParameter;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Token;

class ParameterParser extends AbstractParser
{
    /**
     * @var Parameter
     */
    protected AbstractContext $context;

    public function parse(NodeParameter $node)
    {
        $this->context->name = $node->getName();

        $constructorProperty = $node->visibilityToken !== null;

        if (!$node->typeDeclarationList) {
            if ($constructorProperty) {
                $this->context->addPropertyToNearestClassDefinition($this->context->name);
            }

            return $this->context->value;
        }

        foreach ($node->typeDeclarationList->getValues() as $type) {
            if ($type instanceof Token) {
                $this->context->types[] = $type->getText($node->getRoot()->getFullText());
            } elseif ($type instanceof QualifiedName) {
                $this->context->types[] = (string) $type->getResolvedName();
            }
        }

        if ($constructorProperty) {
            $this->context->addPropertyToNearestClassDefinition($this->context->name, $this->context->types);
        }

        return $this->context->value;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Parameter;
    }
}
