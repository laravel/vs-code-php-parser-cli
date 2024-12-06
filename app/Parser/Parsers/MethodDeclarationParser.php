<?php

namespace App\Parser\Parsers;

use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Token;

class MethodDeclarationParser extends AbstractParser
{
    use InitsNewContext;

    public function parse(MethodDeclaration $node)
    {
        $this->context->methodDefinition = $node->getName();

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

        // $this->loopChildren($node);

        return $this->context;
    }
}
