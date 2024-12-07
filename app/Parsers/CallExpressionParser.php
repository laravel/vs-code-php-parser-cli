<?php

namespace App\Parsers;

use App\Contexts\AssignmentValue;
use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
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
        if ($this->context->name) {
            return $this->context;
        }

        if ($node->callableExpression instanceof QualifiedName) {
            $this->context->name = (string) ($node->callableExpression->getResolvedName() ?? $node->callableExpression->getText());
        }

        return $this->context;

        // $lastChild = null;

        // foreach ($node->getDescendantNodes() as $child) {
        //     if ($child instanceof QualifiedName) {
        //         if ($lastChild instanceof ScopedPropertyAccessExpression || $lastChild instanceof MemberAccessExpression) {
        //             $this->context->classUsed ??= (string) $child->getResolvedName();
        //         }
        //     }

        //     if ($child instanceof Variable) {
        //         if ($this->context->classUsed) {
        //             continue;
        //         }

        //         if ($child->getName() === 'this') {
        //             $propName = $child->getParent()->memberName->getFullText($this->sourceFile->getFileContents());

        //             $result = $this->context->searchForProperty($propName);

        //             if ($result) {
        //                 $this->context->classUsed = $result['types'][0] ?? null;
        //             }

        //             continue;
        //         }

        //         $varName = $child->getName();

        //         $result = $this->context->searchForVar($varName);

        //         if ($result) {
        //             $this->context->classUsed = $result['value'] ?? $result['types'][0] ?? null;
        //         }
        //     }

        //     $lastChild = $child;
        // }

        // if ($node && property_exists($node, 'expression') && property_exists($node->expression, 'argumentExpressionList') && $node?->expression?->argumentExpressionList) {
        //     $lastArgExpression = null;
        //     $lastMethodArg = null;

        //     foreach ($node->expression->argumentExpressionList->getChildNodesAndTokens() as $el) {
        //         if ($el instanceof Token) {
        //             continue;
        //         }

        //         $lastMethodArg = $this->parseArgument($el->expression ?? null);
        //         $this->context->methodExistingArgs[] = $lastMethodArg;
        //         $lastArgExpression = $el;

        //         $this->increaseParamIndex($el);
        //     }

        //     if ($lastMethodArg['type'] === 'closure') {
        //         $this->debug('closure as last arg', $lastArgExpression::class);
        //         $this->nextNodeToWalk = $lastArgExpression->expression;
        //     }

        //     if ($this->postArgumentParsingCallback) {
        //         ($this->postArgumentParsingCallback)();
        //         $this->postArgumentParsingCallback = null;
        //     }
        // }

        // return $this->context;
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
