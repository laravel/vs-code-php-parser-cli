<?php

namespace App\Parser;

use App\Support\Debugs;
use Illuminate\Support\Str;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\AnonymousFunctionCreationExpression;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;
use Microsoft\PhpParser\Node\Expression\ArrowFunctionCreationExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
use Microsoft\PhpParser\Node\Statement\ReturnStatement;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\PositionUtilities;
use Microsoft\PhpParser\SkippedToken;
use Microsoft\PhpParser\Token;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Nodes\EchoNode;

class DetectWalker
{
    use Debugs;

    protected DetectContext $context;

    protected $items = [];

    protected $depth = 0;

    protected SourceFileNode $sourceFile;

    protected $postArgumentParsingCallback = null;

    protected $nextNodeToWalk = null;

    public function __construct(protected string $document, $debug = false)
    {
        $this->debug = $debug;
        $this->sourceFile = (new Parser)->parseSourceFile(trim($this->document));
        $this->context = new DetectContext;
    }

    public function walk(?Node $node = null)
    {
        $node = $this->nextNodeToWalk ?? $node ?? $this->sourceFile;
        $this->nextNodeToWalk = null;

        foreach ($this->sourceFile->getDescendantNodes() as $child) {
            if ($child instanceof InlineHtml) {
                $this->parsePotentialBlade($child);
            }

            if ($child instanceof CallExpression) {
                $this->debug('CALL EXPRESSION', $child::class, $child->getText());
                $this->parseCallExpression($child);
            }
        }

        // TODO: These results are not unique maybe?
        return collect($this->items)->unique(fn ($item) => json_encode($item))->values();
    }

    protected function parsePotentialBlade(InlineHtml $node)
    {
        foreach (Document::fromText($node->getText())->getNodes() as $node) {
            if ($node instanceof DirectiveNode) {
                $this->parseBladeDirective($node);
            }

            // if ($node instanceof EchoNode) {
            //     $walker = new static('<?php ' . $node->innerContent);
            //     TODO: Parse this and re-calc the offsets
            //     var_dump($walker->walk());
            // }

            $this->debug('potential blade node', $node::class);
        }
    }

    protected function parseBladeDirective(DirectiveNode $node)
    {
        if ($node->isClosingDirective || !$node->hasArguments()) {
            return;
        }

        $item = new DetectedItem;

        $item->methodUsed = '@' . $node->content;

        $arguments = (string) $node->arguments;
        $position = 0;
        $line = 0;

        $argPositions = $node->arguments->getArgValues()->map(function ($arg) use (&$arguments, &$position, &$line) {
            $isString = Str::startsWith($arg, ['"', "'"]) && Str::endsWith($arg, ['"', "'"]);

            if (!$isString) {
                return [
                    'line'  => 0,
                    'start' => 0,
                    'end'   => 0,
                ];
            }

            $start = 0;
            $originalLineCount = $line;

            while (mb_strlen($arguments) && !Str::startsWith($arguments, $arg)) {
                $nextChar = mb_substr($arguments, 0, 1);

                if ($nextChar === "\n") {
                    $line++;
                    $start = 0;
                }

                $start += 1;
                $arguments = mb_substr($arguments, 1);
            }

            if (Str::startsWith($arguments, $arg)) {
                $arguments = mb_substr($arguments, mb_strlen($arg));
            }

            if ($originalLineCount !== $line) {
                $position = 0;
            }

            $actualStart = $start + $position;
            $actualEnd = $actualStart + mb_strlen($arg);

            $position = $actualEnd;

            return [
                'line'  => $line,
                'start' => $actualStart - 1,
                'end'   => $actualEnd - 3,
            ];
        });

        $item->params = $node->arguments->getArgValues()->map(function ($arg, $index) use ($node, $argPositions) {
            $isString = Str::startsWith($arg, ['"', "'"]) && Str::endsWith($arg, ['"', "'"]);

            if (!$isString) {
                return [
                    'type'  => 'unknown',
                    'value' => $arg,
                ];
            }

            $arg = mb_substr($arg, 1, -1);

            $directiveLength = mb_strlen('@' . $node->content);

            if ($argPositions[$index]['line'] === 0) {
                $offset = $node->position->startColumn + $directiveLength;
            } else {
                $offset = 0;
            }

            return [
                'type'  => 'string',
                'value' => $arg,
                'start' => [
                    'line'   => $node->position->startLine + $argPositions[$index]['line'] - 1,
                    'column' => $offset + $argPositions[$index]['start'],
                ],
                'end' => [
                    'line'   => $node->position->startLine + $argPositions[$index]['line'] - 1,
                    'column' => $offset + $argPositions[$index]['end'],
                ],
            ];
        });

        $this->items[] = $item->toArray();
    }

    protected function parseCallExpression(CallExpression $node)
    {
        if ($node->callableExpression instanceof QualifiedName) {
            $this->parseQualifiedCallExpression($node);
        } elseif ($node->callableExpression instanceof MemberAccessExpression || $node->callableExpression instanceof ScopedPropertyAccessExpression) {
            $this->parseMemberAccessCallExpression($node);
        } else {
            // dd($child->callableExpression, 'unknown');
        }
    }

    protected function parseQualifiedCallExpression(CallExpression $node)
    {
        $item = new DetectedItem;

        $item->methodUsed = (string) ($node->callableExpression->getResolvedName() ?? $node->callableExpression->getText());

        if ($node->argumentExpressionList) {
            foreach ($node->argumentExpressionList->getChildNodesAndTokens() as $el) {
                if ($el instanceof Token) {
                    continue;
                }

                $item->params[] = $this->parseArgument($el->expression ?? null);

                $this->increaseParamIndex($el);
            }
        }

        $this->items[] = $item->toArray();
    }

    protected function parseMemberAccessCallExpression(CallExpression $node)
    {
        $item = new DetectedItem;

        $item->methodUsed = (string) $node->callableExpression->memberName->getFullText($this->sourceFile->getFileContents());

        $parent = $node->getParent() instanceof ExpressionStatement ? $node->getParent() : $node;

        // foreach ($node->callableExpression->getChildNodes() as $child) {
        //     $this->debug('member access child', $child::class, $child->getText());
        //     if ($child instanceof QualifiedName) {
        //         $item->classUsed ??= (string) ($child->getResolvedName() ?? $child->getText());
        //     }
        // }
        foreach ($parent->getDescendantNodes() as $child) {
            $this->debug('member access child', $child::class, $child->getText());
            if ($child instanceof QualifiedName) {
                $item->classUsed ??= (string) ($child->getResolvedName() ?? $child->getText());
            }
        }

        // if ($item->classUsed === null) {
        //     dd($node->getParent(), $node->getText());
        // }

        if ($node->argumentExpressionList) {
            foreach ($node->argumentExpressionList->getChildNodesAndTokens() as $el) {
                if ($el instanceof Token) {
                    continue;
                }

                $lastMethodArg = $this->parseArgument($el->expression ?? null);
                $item->params[] = $lastMethodArg;

                $this->increaseParamIndex($el);
            }
        }

        $this->items[] = $item->toArray();
    }

    protected function parseExpressionStatement(ExpressionStatement|ReturnStatement|CallExpression $node)
    {
        $callable = $node instanceof CallExpression ? $node->callableExpression : $node->expression->callableExpression ?? null;

        if ($callable instanceof QualifiedName) {
            // TODO: This foolproof?
            $this->context->methodUsed = (string) ($callable->getResolvedName() ?? $callable->getText());
        } elseif ($callable instanceof MemberAccessExpression || $callable instanceof ScopedPropertyAccessExpression) {
            $this->context->methodUsed = $callable->memberName->getFullText($this->sourceFile->getFileContents());
        }

        $lastChild = null;

        foreach ($node->getDescendantNodes() as $child) {
            $this->debug('expression child tho', $child::class, $child->getText());
            if ($child instanceof QualifiedName) {
                if ($lastChild instanceof ScopedPropertyAccessExpression || $lastChild instanceof MemberAccessExpression) {
                    $this->context->classUsed ??= (string) $child->getResolvedName();
                }
            } elseif ($child instanceof ScopedPropertyAccessExpression || $child instanceof MemberAccessExpression) {
                $this->initNewContext();
                $this->context->methodUsed = $callable->memberName->getFullText($this->sourceFile->getFileContents());
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

        $this->items[] = [
            'method'     => $this->context->methodUsed,
            'value'      => $this->context->methodExistingArgs,
            'class'      => $this->context->classUsed,
            'paramIndex' => $this->context->paramIndex,
        ];

        $this->context->child = $context ?? new DetectContext($this->context);
    }

    protected function parseArgument($argument)
    {
        if ($argument === null) {
            return [
                'type'  => 'null',
                'value' => null,
            ];
        }

        $this->debug('parsing argument', $argument::class);

        if ($argument instanceof StringLiteral) {
            $range = PositionUtilities::getRangeFromPosition(
                $argument->getStartPosition(),
                strlen($argument->getStringContentsText()),
                $this->sourceFile->getFullText()
            );

            return [
                'type'  => 'string',
                'value' => $argument->getStringContentsText(),
                // 'index' => $this->context->paramIndex,
                'start' => [
                    'line'   => $range->start->line,
                    'column' => $range->start->character,
                ],
                'end' => [
                    'line'   => $range->end->line,
                    'column' => $range->end->character,
                ],
            ];
        }

        if ($argument instanceof ArrayCreationExpression) {
            $array = [];
            $lastValue = null;

            if ($argument->arrayElements) {
                foreach ($argument->arrayElements->getElements() as $element) {
                    $array[] = [
                        'key'   => $this->parseArgument($element->elementKey),
                        'value' => $this->parseArgument($element->elementValue),
                    ];

                    $lastValue = $element->elementValue;
                }
            }

            if ($argument->closeParenOrBracket instanceof MissingToken) {
                $this->handleMissingArrayCloseToken($array, $lastValue);
            }

            return [
                'type'  => 'array',
                'value' => $array,
            ];
        }

        if ($argument instanceof MissingToken || $argument instanceof SkippedToken) {
            return [
                'type'  => 'missing',
                'value' => $argument->getText($this->sourceFile->getFullText()),
            ];
        }

        if ($argument instanceof AnonymousFunctionCreationExpression || $argument instanceof ArrowFunctionCreationExpression) {
            $args = [];

            if ($argument->parameters) {
                foreach ($argument->parameters->getElements() as $element) {
                    $param = [
                        'types' => [],
                        'name'  => $element->getName(),
                    ];

                    if ($element->typeDeclarationList) {
                        foreach ($element->typeDeclarationList->getValues() as $type) {
                            if ($type instanceof Token) {
                                $param['types'][] = $type->getText($this->sourceFile->getFullText());
                            } elseif ($type instanceof QualifiedName) {
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
                'type'      => 'closure',
                'arguments' => $args,
            ];
        }

        if ($argument instanceof ObjectCreationExpression) {
            $result = [
                'type'  => 'object',
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
            'type'  => 'unknown',
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
        } elseif ($lastEl['value']['type'] === 'missing') {
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
                } elseif ($lastValue->compoundStatementOrSemicolon instanceof CompoundStatementNode) {
                    $this->postArgumentParsingCallback = function () use ($lastValue) {
                        // Dive into the closure
                        $this->initNewContext();
                        $this->parseExpressionStatement($lastValue->compoundStatementOrSemicolon->getFirstChildNode(ExpressionStatement::class));
                    };
                }
            }
        }
    }
}
