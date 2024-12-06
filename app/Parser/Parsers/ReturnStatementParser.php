<?php

namespace App\Parser\Parsers;

use Microsoft\PhpParser\Node\Statement\ReturnStatement;

class ReturnStatementParser extends AbstractParser
{
    use InitsNewContext;

    public function parse(ReturnStatement $node)
    {
        return $this->context;
    }
}
