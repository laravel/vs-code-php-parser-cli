<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ClosureValue;

class AnonymousFunctionCreationExpressionParser extends AbstractParser
{
    /**
     * @var ClosureValue
     */
    protected AbstractContext $context;

    public function initNewContext(): ?AbstractContext
    {
        return new ClosureValue;
    }
}
