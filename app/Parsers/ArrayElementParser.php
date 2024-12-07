<?php

namespace App\Parsers;

use App\Contexts\ArrayItem;
use App\Contexts\AbstractContext;

class ArrayElementParser extends AbstractParser
{
    public function initNewContext(): ?AbstractContext
    {
        return new ArrayItem;
    }
}
