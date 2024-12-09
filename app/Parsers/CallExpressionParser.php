<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;

class CallExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(CallExpression $node)
    {
        if ($this->context->methodName) {
            return $this->context;
        }

        if ($node->callableExpression instanceof QualifiedName) {
            $this->context->methodName = (string) ($node->callableExpression->getResolvedName() ?? $node->callableExpression->getText());
        }

        $this->context->autocompleting = $node->closeParen instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        // TODO: Unclear if this is correct
        if (!($this->context instanceof MethodCall) || $this->context->touched()) {
            return new MethodCall;
        }

        return null;
    }
}
