<?php

namespace App\Parser;

use App\Contexts\BaseContext;
use App\Contexts\Generic;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;

class Parse
{
    public static function parse(Node $node, $depth = 0, ?BaseContext $currentContext = null)
    {
        // echo str_repeat('   ', $depth) . $node::class . ' `' . substr(str_replace("\n", ' ', $node->getText()), 0, 25) . '`' . PHP_EOL;

        // foreach ($node->getChildNodes() as $child) {
        //     self::parse($child, $depth + 1);
        // }

        // return;

        echo str_repeat('   ', $depth) . $node::class . PHP_EOL;

        $class = basename(str_replace('\\', '/', $node::class));
        $parserClass = 'App\\Parsers\\' . $class . 'Parser';

        $context = $currentContext ?? new Generic();

        if (class_exists($parserClass)) {
            echo str_repeat(' ', $depth) . '- Parsing: ' . $parserClass . PHP_EOL;
            echo PHP_EOL;

            /** @var \App\Parsers\AbstractParser */
            $parser = app()->make($parserClass);
            $parser->context($context)->depth($depth + 1);

            if ($newContext = $parser->initNewContext()) {
                $context = $context->initNew($newContext);
                $parser->context($context);
            }

            $context = $parser->parseNode($node);
        }

        foreach ($node->getChildNodes() as $child) {
            self::parse($child, $depth + 1, $context);
        }

        return $context;
    }
}
