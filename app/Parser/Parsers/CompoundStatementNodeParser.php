<?php

namespace App\Parser\Parsers;

use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;

class CompoundStatementNodeParser extends AbstractParser
{
    public function parse(CompoundStatementNode $node)
    {
        // $this->loopChildren($node);

        return $this->context;
    }
}
