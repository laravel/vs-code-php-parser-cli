<?php

namespace App\Parser\Parsers;

use App\Parser\Parse;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;

class AssignmentExpressionParser extends AbstractParser
{
    public function parse(AssignmentExpression $node)
    {
        // parseArgument in walker
        // StringLiteral
        // ArrayCreationExpression
        // AnonymousFunctionCreationExpression
        // ArrowFunctionCreationExpression
        // ObjectCreationExpression
        $this->context->addVariable(
            $node->leftOperand->getText(),
            Parse::parse($node->rightOperand)?->toArray() ?? [
                'type' => 'unknown',
                'value' => $node->rightOperand->getText(),
            ],
        );

        return $this->context;
    }
}
