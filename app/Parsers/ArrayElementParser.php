<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\ArrayItem;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\ArrayElement;

class ArrayElementParser extends AbstractParser
{
    /**
     * @var ArrayItem
     */
    protected AbstractContext $context;

    public function parse(ArrayElement $node)
    {
        $this->context->hasKey = $node->elementKey !== null;
        $this->context->autocompletingValue = $node->elementValue instanceof MissingToken;

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new ArrayItem;
    }
}
