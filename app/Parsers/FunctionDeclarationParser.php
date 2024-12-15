<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodDefinition;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Microsoft\PhpParser\Token;

class FunctionDeclarationParser extends AbstractParser
{
    /**
     * @var MethodDefinition
     */
    protected AbstractContext $context;

    public function parse(FunctionDeclaration $node)
    {
        $this->context->methodName = collect($node->getNameParts())->map(
            fn(Token $part) => $part->getText($node->getRoot()->getFullText())
        )->join('\\');

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new MethodDefinition;
    }
}
