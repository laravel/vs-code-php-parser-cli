<?php

namespace App\Parsers;

use App\Contexts\ArrayItem;
use App\Contexts\BaseContext;

class ArrayElementParser extends AbstractParser
{
    public function initNewContext(): ?BaseContext
    {
        return new ArrayItem;
    }
}
