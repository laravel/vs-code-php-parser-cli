<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Argument;
use App\Contexts\MethodCall;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;

class ArgumentExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(ArgumentExpression $node)
    {
        return $this->context;

        // $array = [];
        // $lastValue = null;

        // if ($node->arrayElements) {
        //     foreach ($node->arrayElements->getElements() as $element) {
        //         $array[] = [
        //             'key' => $this->parseArgument($element->elementKey),
        //             'value' => $this->parseArgument($element->elementValue),
        //         ];

        //         $lastValue = $element->elementValue;
        //     }
        // }

        // if ($node->closeParenOrBracket instanceof MissingToken) {
        //     $this->handleMissingArrayCloseToken($array, $lastValue);
        // }

        // return [
        //     'type' => 'array',
        //     'value' => $array,
        // ];

        // return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new Argument;
    }
}
