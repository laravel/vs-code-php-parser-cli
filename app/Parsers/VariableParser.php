<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Variable as VariableContext;
use App\Parser\Settings;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\NamespaceUseClause;
use Microsoft\PhpParser\Node\Statement\NamespaceUseDeclaration;
use Microsoft\PhpParser\PositionUtilities;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

class VariableParser extends AbstractParser
{
    /**
     * @var VariableContext
     */
    protected AbstractContext $context;

    public static array $previousContexts = [];

    private function getLatestDocComment(Node $node): ?string
    {
        $docComment = $node->getDocCommentText();

        if ($docComment === null && $node->getParent() !== null) {
            return $this->getLatestDocComment($node->getParent());
        }

        return $docComment;
    }

    public function parse(Variable $node)
    {
        $this->context->name = $node->getName();

        $result = $this->context->searchForVar($this->context->name);

        // Firstly, we try to find the className from the method parameter
        if (is_string($result)) {
            $this->context->className = $result;
        }

        // If the className is still not found, we try to find the className
        // from the doc comment, for example:
        //
        // /** @var User $user */
        // Gate::allows('edit', $user);
        if ($this->context->className === null) {
            $docComment = $this->getLatestDocComment($node);

            if ($docComment !== null) {
                $config = new ParserConfig([]);
                $lexer = new Lexer($config);
                $constExprParser = new ConstExprParser($config);
                $typeParser = new TypeParser($config, $constExprParser);
                $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);

                $tokens = new TokenIterator($lexer->tokenize($docComment));
                $phpDocNode = $phpDocParser->parse($tokens);
                $varTagValues = $phpDocNode->getVarTagValues();

                /** @var VarTagValueNode|null $tagValue */
                $tagValue = collect($varTagValues)
                    // We need to remove first character because it's always $
                    ->first(fn(VarTagValueNode $valueNode) => substr($valueNode->variableName, 1) === $this->context->name);

                if ($tagValue?->type instanceof IdentifierTypeNode) {
                    // If the class name starts with a backslash, it's a fully qualified name
                    if (str_starts_with($tagValue->type->name, '\\')) {
                        $this->context->className = substr($tagValue->type->name, 1);
                    // Otherwise, it's a short name and we need to find the fully qualified name from
                    // the imported namespaces
                    } else {
                        $uses = [];

                        foreach ($node->getRoot()->getDescendantNodes() as $node) {
                            if (! $node instanceof NamespaceUseDeclaration) {
                                continue;
                            }

                            foreach ($node->useClauses->children ?? [] as $clause) {
                                if (! $clause instanceof NamespaceUseClause) {
                                    continue;
                                }

                                $fqcn = $clause->namespaceName->getText();

                                // If the namespace has an alias, we need to use the alias as the short name
                                $alias = $clause->namespaceAliasingClause
                                    ? str($clause->namespaceAliasingClause->getText())
                                        ->after('as')
                                        ->trim()
                                        ->toString()
                                    : str($fqcn)->explode('\\')->last();

                                // Finally, we add the short and fully qualified name to the uses array
                                $uses[$alias] = $fqcn;
                            }
                        }

                        $this->context->className = $uses[$tagValue->type->name] ?? null;
                    }
                }
            }
        }

        // If the className is still not found, we try to find the className
        // from the previous variable contexts, for example:
        //
        // /** @var \App\Models\User $user */
        // $user = $request->user;
        //
        // Gate::allows('edit', $user);
        if ($this->context->className === null) {
            /** @var VariableContext|null $previousVariableContext */
            $previousVariableContext = collect(self::$previousContexts)
                ->first(fn(VariableContext $context) => $context->name === $this->context->name);

            if ($previousVariableContext !== null) {
                $this->context->className = $previousVariableContext->className;
            }
        }

        if (Settings::$capturePosition) {
            $range = PositionUtilities::getRangeFromPosition(
                $node->getStartPosition(),
                mb_strlen($node->getText()),
                $node->getRoot()->getFullText(),
            );

            if (Settings::$calculatePosition !== null) {
                $range = Settings::adjustPosition($range);
            }

            $this->context->setPosition($range);
        }

        array_push(self::$previousContexts, $this->context);

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new VariableContext;
    }
}
