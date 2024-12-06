<?php

namespace App\Parser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;

class Parse
{
    public static function parse(Node $node, $depth = 0, $currentContext = null)
    {
        echo str_repeat('   ', $depth) . $node::class . PHP_EOL;

        $class = basename(str_replace('\\', '/', $node::class));
        $parser = 'App\\Parser\\Parsers\\' . $class . 'Parser';

        $context = $currentContext ?? new Context();

        if (class_exists($parser)) {
            echo str_repeat(' ', $depth) . '- Parsing: ' . $parser . PHP_EOL;
            echo PHP_EOL;

            $parser = app()->make($parser);

            if ($parser->shouldInitNewContext()) {
                // echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
                // echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
                // echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
                // echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
                $context = $context->initNew();
            }

            // echo $context->toJson(JSON_PRETTY_PRINT) . PHP_EOL;

            $parser->context($context)->depth($depth + 1)->parseNode($node);
        }

        foreach ($node->getChildNodes() as $child) {
            self::parse($child, $depth + 1, $context);
        }

        return $context;

        echo str_repeat(' ', $depth) . $node::class . PHP_EOL;
        echo str_repeat(' ', $depth) . '[code] ' . substr(str_replace("\n", ' ', $node->getText()), 0, 25) . '...' . PHP_EOL;

        if (!class_exists($parser)) {
            echo PHP_EOL;
            return $currentContext;
        }

        echo str_repeat(' ', $depth) . '- Parsing: ' . $parser . PHP_EOL;
        echo PHP_EOL;

        $parser = app()->make($parser);

        $context = $currentContext ?? new Context();

        if ($parser->shouldInitNewContext()) {
            echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
            echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
            echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
            echo '------------ INIT NEW CONTEXT ------------ ' . PHP_EOL;
            $context = $context->initNew();
        }

        echo $context->toJson(JSON_PRETTY_PRINT) . PHP_EOL;

        $result = $parser->context($context)->depth($depth + 1)->parse($node);

        if ($node instanceof SourceFileNode) {
            dd($context, 'sou   rce file');
        }

        return $result;
    }
}
