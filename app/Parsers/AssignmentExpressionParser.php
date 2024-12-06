<?php

namespace App\Parsers;

use App\Contexts\Assignment;
use App\Contexts\BaseContext;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;

class AssignmentExpressionParser extends AbstractParser
{
    /**
     * @var Assignment
     */
    protected BaseContext $context;

    public function parse(AssignmentExpression $node)
    {
        $this->context->name = ltrim($node->leftOperand->getText(), '$');

        return $this->context->value;
    }

    public function initNewContext(): ?BaseContext
    {
        return new Assignment;
    }
}
