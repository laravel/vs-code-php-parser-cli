<?php

namespace App\Parsers;

use Microsoft\PhpParser\Node\SourceFileNode;

class SourceFileNodeParser extends AbstractParser
{
    public function parse(SourceFileNode $node)
    {
        // $this->loopChildren($node);

        return $this->context;
    }
}
