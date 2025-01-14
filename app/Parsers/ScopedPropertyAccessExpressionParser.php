<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Argument;
use App\Contexts\AssignmentValue;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;

class ScopedPropertyAccessExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(ScopedPropertyAccessExpression $node)
    {
        $this->context->methodName = $node->memberName->getFullText($node->getRoot()->getFullText());
        $this->context->className = $this->resolveClassName($node);

        return $this->context;
    }

    protected function resolveClassName(ScopedPropertyAccessExpression $node)
    {
        if (method_exists($node->scopeResolutionQualifier, 'getResolvedName')) {
            return (string) $node->scopeResolutionQualifier->getResolvedName();
        }

        if ($node->scopeResolutionQualifier instanceof Variable) {
            $result = $this->context->searchForVar($node->scopeResolutionQualifier->getName());

            if ($result instanceof AssignmentValue) {
                return $result->getValue()['name'] ?? null;
            }

            return $result;
        }

        if ($node->scopeResolutionQualifier instanceof MemberAccessExpression) {
            $parser = new MemberAccessExpressionParser;
            $context = new MethodCall;
            $context->parent = clone $this->context;
            $parser->context($context);
            $result = $parser->parseNode($node->scopeResolutionQualifier);

            return $result->className ?? null;
        }

        return $node->scopeResolutionQualifier->getText();
    }

    public function initNewContext(): ?AbstractContext
    {
        if (
            $this->context instanceof Argument
            || $this->context instanceof AssignmentValue
        ) {
            return new MethodCall;
        }

        return null;
    }
}
