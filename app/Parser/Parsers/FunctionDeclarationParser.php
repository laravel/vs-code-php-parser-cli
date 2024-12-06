<?php

namespace App\Parser\Parsers;

use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Microsoft\PhpParser\Token;

class FunctionDeclarationParser extends AbstractParser
{
    use InitsNewContext;

    public function parse(FunctionDeclaration $node)
    {
        $this->context->methodDefinition = array_map(
            fn(Token $part) => $part->getText(SourceFile::fullText()),
            $node->getNameParts(),
        );

        if ($node->parameters) {
            foreach ($node->parameters->getElements() as $element) {
                $param = [
                    'types' => [],
                    'name' => $element->getName(),
                ];

                if ($element->typeDeclarationList) {
                    foreach ($element->typeDeclarationList->getValues() as $type) {
                        if ($type instanceof Token) {
                            $param['types'][] = $type->getText(SourceFile::fullText());
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

        return $this->context;
    }
}
