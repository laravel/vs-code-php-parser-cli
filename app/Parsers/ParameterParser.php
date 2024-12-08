<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Parameter;
use App\Parser\SourceFile;
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

        if (!$node->typeDeclarationList) {
            return $this->context->value;
        }

        foreach ($node->typeDeclarationList->getValues() as $type) {
            if ($type instanceof Token) {
                $this->context->types[] = $type->getText(SourceFile::fullText());
            } elseif ($type instanceof QualifiedName) {
                $this->context->types[] = (string) $type->getResolvedName();
            }
        }

        return $this->context->value;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Parameter;
    }
}
