<?php

namespace App\Parsers;

use App\Contexts\ClosureValue;
use App\Contexts\AbstractContext;

class ArrowFunctionCreationExpressionParser extends AbstractParser
{
    /**
     * @var ClosureValue
     */
    protected AbstractContext $context;

    public function initNewContext(): ?AbstractContext
    {
        return new ClosureValue();
    }
}
