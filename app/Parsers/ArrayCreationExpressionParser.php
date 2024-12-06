<?php

namespace App\Parsers;

use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node\Expression\ArrayCreationExpression;

class ArrayCreationExpressionParser extends AbstractParser
{
    public function parse(ArrayCreationExpression $node)
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
}
