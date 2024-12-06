<?php

namespace App\Parser\Parsers;

use Microsoft\PhpParser\Node\StringLiteral;

class StringLiteralParser extends AbstractParser
{
    public function parse(StringLiteral $node)
    {
        // dd($node);

        return $this->context;
    }
}
