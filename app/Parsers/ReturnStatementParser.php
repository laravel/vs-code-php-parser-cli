<?php

namespace App\Parsers;

use App\Contexts\BaseContext;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Statement\ReturnStatement;

class ReturnStatementParser extends AbstractParser
{
    public function initNewContext(): ?BaseContext
    {
        // TODO: ...This right?
        return new MethodCall;
    }
}
