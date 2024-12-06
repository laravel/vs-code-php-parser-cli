<?php

namespace App\Parser\Parsers;

use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;

class ScopedPropertyAccessExpressionParser extends AbstractParser
{
    public function parse(ScopedPropertyAccessExpression $node)
    {
        $this->context->classUsed = (string) $node->scopeResolutionQualifier->getResolvedName();
        $this->context->methodUsed = $node->memberName->getFullText(SourceFile::fullText());

        // Loop this???
        // Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList
        // $this->loopChildren($node);

        return $this->context;
    }
}
