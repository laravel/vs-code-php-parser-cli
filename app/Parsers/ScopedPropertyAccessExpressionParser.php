<?php

namespace App\Parsers;

use App\Contexts\Argument;
use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;

class ScopedPropertyAccessExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(ScopedPropertyAccessExpression $node)
    {
        $this->context->name = $node->memberName->getFullText(SourceFile::fullText());
        $this->context->class = (string) $node->scopeResolutionQualifier->getResolvedName();

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        if ($this->context instanceof Argument) {
            return new MethodCall;
        }

        return null;
    }
}
