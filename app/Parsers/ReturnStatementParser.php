<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Statement\ReturnStatement;

class ReturnStatementParser extends AbstractParser
{
    public function initNewContext(): ?AbstractContext
    {
        // TODO: ...This right?
        return new MethodCall;
    }
}
