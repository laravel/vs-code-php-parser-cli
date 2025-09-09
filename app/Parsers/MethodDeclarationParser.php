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
        $this->context->methodName = $node->getName();

        // Every method is a new context, so we need to clear
        // the previous variable contexts
        // @see https://github.com/laravel/vs-code-php-parser-cli/pull/14
        VariableParser::$previousContexts = [];

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new MethodDefinition;
    }
}
