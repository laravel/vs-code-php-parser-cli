<?php

namespace App\Parser;

use App\Support\Debugs;
use Illuminate\Support\Facades\File;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ArrayElement;
use Microsoft\PhpParser\Node\ClassMembersNode;
use Microsoft\PhpParser\Node\DelimitedList\ParameterDeclarationList;
use Microsoft\PhpParser\Node\Expression\AnonymousFunctionCreationExpression;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;
use Microsoft\PhpParser\Node\Expression\ArrowFunctionCreationExpression;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\PropertyDeclaration;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Microsoft\PhpParser\Node\Statement\ReturnStatement;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\SkippedToken;
use Microsoft\PhpParser\Token;

class Walker
{
    use Debugs;

    protected Context $context;

    protected $depth = 0;

    protected SourceFileNode $sourceFile;

    protected $postArgumentParsingCallback = null;

    protected $nextNodeToWalk = null;

    protected $dontWalk = false;

    public function __construct(protected string $document, $debug = false)
    {
        $this->debug = $debug;
        $this->sourceFile = (new Parser())->parseSourceFile(trim($this->document));
        $this->context = new Context;

        $lastToken = null;
        $foundSkippedClosingQuote = false;

        foreach ($this->sourceFile->getDescendantNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                $this->debug('initial node check', $child::class, $child->getText());
            } else {
                $lastToken = $child;

                if ($lastToken instanceof SkippedToken && $lastToken->getText($this->sourceFile->getFullText()) === "'") {
                    $foundSkippedClosingQuote = true;
                }

                $this->debug('initial token check', $child::class, $child->getText($this->sourceFile->getFullText()));
            }
        }

        $this->dontWalk = !$foundSkippedClosingQuote;
    }

    public function walk(?Node $node = null): Context
    {
        $node = $this->nextNodeToWalk ?? $node ?? $this->sourceFile;

        SourceFile::$sourceFile = $this->sourceFile;

        $parsed = Parse::parse($node);

        // if ($parsed->pristine() && $parsed->parent) {
        //     $parsed = $parsed->parent;
        // }

        $arr = $parsed->toArray();

        File::ensureDirectoryExists(storage_path('new-parsed'));

        file_put_contents(storage_path('new-parsed/' . now() . '.json'), json_encode($arr, JSON_PRETTY_PRINT));

        dd($arr, 'end');

        if ($this->dontWalk) {
            return $this->context;
        }

        $node = $this->nextNodeToWalk ?? $node ?? $this->sourceFile;
        $this->nextNodeToWalk = null;

        $this->debugSpacer();
        $this->debug('walking node', $node::class, $node->getText());

        $newContextCreators = [
            ClassDeclaration::class,
            ExpressionStatement::class,
            FunctionDeclaration::class,
            MethodDeclaration::class,
            ClassMembersNode::class,
            AnonymousFunctionCreationExpression::class,
            ArrowFunctionCreationExpression::class,
            ReturnStatement::class,
            CallExpression::class,
        ];

        $shouldWalk = array_merge($newContextCreators, [
            CompoundStatementNode::class,
        ]);

        $lastChild = null;

        foreach ($node->getChildNodesAndTokens() as $child) {
            if (in_array($child::class, $shouldWalk)) {
                if ($child instanceof CallExpression && !($node instanceof ArrowFunctionCreationExpression)) {
                    continue;
                }

                $this->debug('should walk', $child::class, $child->getText());
                $lastChild = $child;

                if ($childNode = $child->getFirstChildNode(AssignmentExpression::class)) {
                    $this->parse($childNode);
                }
            } else {
                $this->debug('child', $child::class, $child->getText());
                $this->parse($child);
            }
        }

        $this->depth++;

        $nextToWalk = $lastChild;

        if ($nextToWalk) {
            $this->debug('last child', $nextToWalk::class, $nextToWalk->getText());

            if (in_array($nextToWalk::class, $newContextCreators)) {
                $this->initNewContext();
            }

            $this->parse($nextToWalk);

            return $this->walk($nextToWalk);
        }

        if ($this->context->pristine() && $this->context->parent) {
            $this->context = $this->context->parent;
        }

        return $this->context;
    }

    protected function parse(Node | Token $node)
    {
        if ($node instanceof Node) {
            $this->parseNode($node);
        } else {
            $this->parseToken($node);
        }
    }

    protected function parseNode(Node $node)
    {
        // if ($this->debug) {
        // echo str_repeat(' ', $this->depth) . $node::class;
        // echo PHP_EOL;
        // echo str_repeat(' ', $this->depth) . $node->getText();
        // echo PHP_EOL;
        // echo PHP_EOL;
        // echo str_repeat(' ', $this->depth) . str_repeat('-', 80) . PHP_EOL;
        // echo PHP_EOL;
        // }

        match ($node::class) {
            ClassDeclaration::class => $this->parseClassDeclaration($node),
            FunctionDeclaration::class,
            MethodDeclaration::class => $this->parseFunctionDeclaration($node),
            ExpressionStatement::class, CallExpression::class => $this->parseExpressionStatement($node),
            AssignmentExpression::class => $this->parseAssignmentExpression($node),
            PropertyDeclaration::class => $this->parsePropertyDeclaration($node),
            ReturnStatement::class => $this->parseExpressionStatement($node),
            ParameterDeclarationList::class => $this->parseParameterDeclarationList($node),
            default => null,
        };
    }

    protected function parseParameterDeclarationList(ParameterDeclarationList $node)
    {
        foreach ($node->getElements() as $element) {
            $param = [
                'types' => [],
                'name' => $element->getName(),
            ];

            if ($element->typeDeclarationList) {
                foreach ($element->typeDeclarationList->getValues() as $type) {
                    if ($type instanceof Token) {
                        $param['types'][] = $type->getText($this->sourceFile->getFullText());
                    } else if ($type instanceof QualifiedName) {
                        $param['types'][] = (string) $type->getResolvedName();
                    } else {
                        $this->debug('unknown type', $type::class);
                    }
                }
            }

            $this->context->addVariable($param['name'], $param);
        }
    }

    protected function parsePropertyDeclaration(PropertyDeclaration $node)
    {
        $property = [
            'types' => [],
        ];

        $name = null;

        if ($node->propertyElements) {
            foreach ($node->propertyElements->getElements() as $element) {
                if ($element instanceof Variable) {
                    $name = $element->getName();
                }
            }
        }

        if ($node->typeDeclarationList) {
            foreach ($node->typeDeclarationList->getValues() as $type) {
                if ($type instanceof Token) {
                    $property['types'][] = $type->getText($this->sourceFile->getFullText());
                } else if ($type instanceof QualifiedName) {
                    $property['types'][] = (string) $type->getResolvedName();
                } else {
                    $this->debug('unknown type', $type::class);
                }
            }
        }

        if ($name !== null) {
            $this->context->definedProperties[$name] = $property;
        }
    }

    protected function parseAssignmentExpression(AssignmentExpression $node)
    {
        $this->context->addVariable($node->leftOperand->getText(), $this->parseArgument($node->rightOperand));
    }

    protected function parseClassDeclaration(ClassDeclaration $node)
    {
        $this->context->classDefinition = (string) $node->getNamespacedName();

        if ($node->classBaseClause) {
            $this->context->extends = (string) $node->classBaseClause->baseClass->getNamespacedName();
        }

        if ($node->classInterfaceClause) {
            foreach ($node->classInterfaceClause->interfaceNameList->getElements() as $element) {
                $this->context->implements[] = (string) $element->getResolvedName();
            }
        }
    }

    protected function parseFunctionDeclaration(FunctionDeclaration | MethodDeclaration $node)
    {
        if ($node instanceof MethodDeclaration) {
            $this->context->methodDefinition = $node->getName();
        } else {
            $this->context->methodDefinition = array_map(
                fn(Token $part) => $part->getText($this->sourceFile->getFullText()),
                $node->getNameParts(),
            );
        }

        if ($node->parameters) {
            foreach ($node->parameters->getElements() as $element) {
                $param = [
                    'types' => [],
                    'name' => $element->getName(),
                ];

                if ($element->typeDeclarationList) {
                    foreach ($element->typeDeclarationList->getValues() as $type) {
                        if ($type instanceof Token) {
                            $param['types'][] = $type->getText($this->sourceFile->getFullText());
                        } else if ($type instanceof QualifiedName) {
                            $param['types'][] = (string) $type->getResolvedName();
                        } else {
                            $this->debug('unknown type', $type::class);
                        }
                    }
                }

                $this->context->methodDefinitionParams[] = $param;
            }
        }
    }

    protected function parseExpressionStatement(ExpressionStatement | ReturnStatement | CallExpression $node)
    {
        $callable = $node instanceof CallExpression ? $node->callableExpression : $node->expression->callableExpression ?? null;

        if ($callable instanceof QualifiedName) {
            // TODO: This foolproof?
            $this->context->methodUsed = (string) ($callable->getResolvedName() ?? $callable->getText());
        } else if ($callable instanceof MemberAccessExpression || $callable instanceof ScopedPropertyAccessExpression) {
            $this->context->methodUsed = $callable->memberName->getFullText($this->sourceFile->getFileContents());
        }

        $lastChild = null;

        foreach ($node->getDescendantNodes() as $child) {
            if ($child instanceof QualifiedName) {
                if ($lastChild instanceof ScopedPropertyAccessExpression || $lastChild instanceof MemberAccessExpression) {
                    $this->context->classUsed ??= (string) $child->getResolvedName();
                }
            }

            if ($child instanceof Variable) {
                if ($this->context->classUsed) {
                    continue;
                }

                if ($child->getName() === 'this') {
                    $propName = $child->getParent()->memberName->getFullText($this->sourceFile->getFileContents());

                    $result = $this->context->searchForProperty($propName);

                    if ($result) {
                        $this->context->classUsed = $result['types'][0] ?? null;
                    }

                    continue;
                }

                $varName = $child->getName();

                $result = $this->context->searchForVar($varName);

                if ($result) {
                    $this->context->classUsed = $result['value'] ?? $result['types'][0] ?? null;
                }
            }

            $lastChild = $child;
        }

        if ($node && property_exists($node, 'expression') && property_exists($node->expression, 'argumentExpressionList') && $node?->expression?->argumentExpressionList) {
            $lastArgExpression = null;
            $lastMethodArg = null;

            foreach ($node->expression->argumentExpressionList->getChildNodesAndTokens() as $el) {
                if ($el instanceof Token) {
                    continue;
                }

                $lastMethodArg = $this->parseArgument($el->expression ?? null);
                $this->context->methodExistingArgs[] = $lastMethodArg;
                $lastArgExpression = $el;

                $this->increaseParamIndex($el);
            }

            if ($lastMethodArg['type'] === 'closure') {
                $this->debug('closure as last arg', $lastArgExpression::class);
                $this->nextNodeToWalk = $lastArgExpression->expression;
            }

            if ($this->postArgumentParsingCallback) {
                ($this->postArgumentParsingCallback)();
                $this->postArgumentParsingCallback = null;
            }
        }
    }

    protected function increaseParamIndex($el)
    {
        if ($el instanceof Token) {
            return;
        }

        $this->debug('increasing param index', $el::class, $el->expression::class);

        if ($el->expression instanceof ArrayCreationExpression && $el->expression->closeParenOrBracket instanceof MissingToken) {
            return;
        }

        if ($el->expression instanceof AnonymousFunctionCreationExpression || $el->expression instanceof ArrowFunctionCreationExpression) {
            return;
        }

        $this->debug('actually increasing param index', $el::class);

        $this->context->paramIndex++;
    }

    protected function initNewContext($context = null)
    {
        if ($this->context->pristine()) {
            return;
        }

        $this->debug('init new context');

        $this->context->child = $context ?? new Context($this->context);
        $this->context = $this->context->child;
        // dd($this->context);
    }

    protected function parseArgument($argument)
    {
        if ($argument === null) {
            return  [
                'type' => 'null',
                'value' => null,
            ];
        }

        $this->debug('parsing argument', $argument::class);

        if ($argument instanceof StringLiteral) {
            return [
                'type' => 'string',
                'value' => $argument->getStringContentsText(),
            ];
        }

        if ($argument instanceof ArrayCreationExpression) {
            $array = [];
            $lastValue = null;

            if ($argument->arrayElements) {
                foreach ($argument->arrayElements->getElements() as $element) {
                    $array[] = [
                        'key' => $this->parseArgument($element->elementKey),
                        'value' => $this->parseArgument($element->elementValue),
                    ];

                    $lastValue = $element->elementValue;
                }
            }

            if ($argument->closeParenOrBracket instanceof MissingToken) {
                $this->handleMissingArrayCloseToken($array, $lastValue);
            }

            return [
                'type' => 'array',
                'value' => $array,
            ];
        }

        if ($argument instanceof MissingToken || $argument instanceof SkippedToken) {
            return [
                'type' => 'missing',
                'value' => $argument->getText($this->sourceFile->getFullText()),
            ];
        }

        if ($argument instanceof AnonymousFunctionCreationExpression || $argument instanceof ArrowFunctionCreationExpression) {
            $args = [];

            if ($argument->parameters) {
                foreach ($argument->parameters->getElements() as $element) {
                    $param = [
                        'types' => [],
                        'name' => $element->getName(),
                    ];

                    if ($element->typeDeclarationList) {
                        foreach ($element->typeDeclarationList->getValues() as $type) {
                            if ($type instanceof Token) {
                                $param['types'][] = $type->getText($this->sourceFile->getFullText());
                            } else if ($type instanceof QualifiedName) {
                                $param['types'][] = (string) $type->getResolvedName();
                            } else {
                                $this->debug('unknown type', $type::class);
                            }
                        }
                    }

                    $args[] = $param;
                }
            }

            return [
                'type' => 'closure',
                'arguments' => $args,
            ];
        }

        if ($argument instanceof ObjectCreationExpression) {
            $result = [
                'type' => 'object',
                'value' => (string) $argument->classTypeDesignator->getResolvedName(),
            ];

            if ($argument->argumentExpressionList) {
                foreach ($argument->argumentExpressionList->getElements() as $child) {
                    foreach ($child->getChildNodes() as $argument) {
                        $result['arguments'][] = $this->parseArgument($argument);
                    }
                }
            }

            return $result;
        }

        if ($argument instanceof CallExpression) {
            $result = [
                'type' => 'call',
                // 'value' => (string) ($argument->callableExpression->getResolvedName() ?? $argument->callableExpression->getText()),
                'arguments' => [],
                ...$this->parseArgument($argument->callableExpression),
            ];

            if ($argument->argumentExpressionList) {
                foreach ($argument->argumentExpressionList->getElements() as $child) {
                    foreach ($child->getChildNodes() as $argument) {
                        $result['arguments'][] = $this->parseArgument($argument);
                    }
                }
            }

            return $result;
        }

        return [
            'type' => 'unknown',
            'value' => $argument->getText(),
        ];

        $this->debug('unknown argument type', $argument);
    }

    protected function handleMissingArrayCloseToken($array, $lastValue)
    {
        // We are filling in something in the array right now
        $this->debug('missing array close token');

        $lastEl = $array[count($array) - 1] ?? null;

        if ($lastEl === null) {
            // We don't know, they could be filling in either, looks like this:
            // ['
            $this->context->fillingInArrayKey = true;
            $this->context->fillingInArrayValue = true;
        } else if ($lastEl['value']['type'] === 'missing') {
            // We have a key, but no value, looks like this:
            // ['key' => '
            $this->context->fillingInArrayValue = true;
        } else {
            $this->context->fillingInArrayKey = $lastEl['key']['type'] !== 'null';
            $this->context->fillingInArrayValue = $lastEl['key']['type'] === 'null';
        }

        if ($lastEl !== null && $lastEl['value']['type'] === 'closure') {

            $parent = $lastValue->parent;

            while ($parent && !($parent instanceof ArrayCreationExpression)) {
                $parent = $parent->parent;
            }

            if ($parent && $parent->closeParenOrBracket->getEndPosition() !== $lastValue->getEndPosition()) {
                // We are probably filling out the next key
                $this->context->fillingInArrayKey = true;
                $this->context->fillingInArrayValue = false;
            } else {

                $this->context->fillingInArrayKey = false;
                $this->context->fillingInArrayValue = true;

                if ($lastValue instanceof ArrowFunctionCreationExpression) {
                    $this->postArgumentParsingCallback = function () use ($lastValue) {
                        // Dive into the closure
                        $this->initNewContext();
                        $this->parseExpressionStatement($lastValue->resultExpression);
                    };
                } else if ($lastValue->compoundStatementOrSemicolon instanceof CompoundStatementNode) {
                    $this->postArgumentParsingCallback = function () use ($lastValue) {
                        // Dive into the closure
                        $this->initNewContext();
                        $this->parseExpressionStatement($lastValue->compoundStatementOrSemicolon->getFirstChildNode(ExpressionStatement::class));
                    };
                }
            }
        }
    }

    protected function parseToken(Token $token)
    {
        //
    }
}
