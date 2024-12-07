<?php

namespace App\Parsers;

use App\Contexts\Assignment;
use App\Contexts\AbstractContext;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;

class AssignmentExpressionParser extends AbstractParser
{
    /**
     * @var Assignment
     */
    protected AbstractContext $context;

    public function parse(AssignmentExpression $node)
    {
        $this->context->name = ltrim($node->leftOperand->getText(), '$');

        return $this->context->value;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Assignment;
    }
}
