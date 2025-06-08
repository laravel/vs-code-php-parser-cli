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

    /**
     * Check if the node has a object operator and
     * is a last element in the string
     */
    private function hasObjectOperator(MemberAccessExpression $node): bool
    {
        $name = $node->memberName->getFullText($node->getRoot()->getFullText());

        return preg_match('/->' . $name . '->;$/s', $node->getFileContents());
    }

    public function parse(MemberAccessExpression $node)
    {
        if ($this->hasObjectOperator($node)) {
            $this->context->autocompleting = true;
        }

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

                $varName = $child->getName();

                $result = $this->context->searchForVar($varName);

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
