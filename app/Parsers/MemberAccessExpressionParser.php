<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\AssignmentValue;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;

class MemberAccessExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(MemberAccessExpression $node)
    {
        $this->context->methodName = $node->memberName->getFullText($node->getRoot()->getFullText());

        foreach ($node->getDescendantNodes() as $child) {
            if ($child instanceof QualifiedName) {
                $this->context->className ??= (string) ($child->getResolvedName() ?? $child->getText());

                return $this->context;
            }

            if ($child instanceof Variable) {
                if ($child->getName() === 'this') {
                    $parent = $child->getParent();

                    if ($parent?->getParent() instanceof CallExpression) {
                        // They are calling a method on the current class
                        $result = $this->context->nearestClassDefinition();

                        if ($result) {
                            $this->context->className = $result->className;
                        }

                        continue;
                    }

                    if ($parent instanceof MemberAccessExpression) {
                        $propName = $parent->memberName->getFullText($node->getRoot()->getFullText());

                        $result = $this->context->searchForProperty($propName);

                        if ($result) {
                            $this->context->className = $result['types'][0] ?? null;
                        }
                    }

                    continue;
                }

                /** @var VariableParser $variableParser */
                $variableParser = app()->make(VariableParser::class);
                $variableParser->context($variableParser->initNewContext());

                $variableContext = $variableParser->parse($child);

                $result = $variableContext->className;

                if (!$result) {
                    return $this->context;
                }

                if ($result instanceof AssignmentValue) {
                    $this->context->className = $result->getValue()['name'] ?? null;
                } else {
                    $this->context->className = $result;
                }
            }
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        if (!($this->context instanceof MethodCall) || $this->context->methodName !== null) {
            return new MethodCall;
        }

        return null;
    }
}
