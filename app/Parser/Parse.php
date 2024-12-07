<?php

namespace App\Parser;

use App\Contexts\AbstractContext;
use App\Contexts\Base;
use App\Contexts\StringValue;
use Microsoft\PhpParser\Node;

class Parse
{
    public static $lastNode = null;

    public static function parse(Node $node, $depth = 0, ?AbstractContext $currentContext = null)
    {
        if ($currentContext === null) {
            self::debugBreak();
            self::debug($depth, str_repeat('=', 80));
            self::debug($depth, str_repeat('=', 80));
            self::debug($depth, str_repeat(' ', 30) . 'STARTING TO PARSE');
            self::debug($depth, str_repeat('=', 80));
            self::debug($depth, str_repeat('=', 80));
            self::debugBreak();
        }

        self::debug($depth, $node::class, "\e[2m" . self::getCodeSnippet($node) . "\e[0m");

        $class = basename(str_replace('\\', '/', $node::class));
        $parserClass = 'App\\Parsers\\' . $class . 'Parser';

        $context = $currentContext ?? new Base();

        if (class_exists($parserClass)) {
            /** @var \App\Parsers\AbstractParser */
            $parser = app()->make($parserClass);
            $parser->context($context)->depth($depth + 1);

            if ($newContext = $parser->initNewContext()) {
                $context = $context->initNew($newContext);
                $parser->context($context);
            }

            self::debug($depth, '+ Context:', $context::class);
            self::debug($depth, '* Parsing: ' . $parserClass);
            self::debugBreak();

            $context = $parser->parseNode($node);
        }

        foreach ($node->getChildNodes() as $child) {
            self::parse($child, $depth + 1, $context);
        }

        return $context;
    }

    public static function tree(Node $node, $depth = 0)
    {
        echo str_repeat('   ', $depth) . $node::class . ' `' . substr(str_replace("\n", ' ', $node->getText()), 0, 25) . '`' . PHP_EOL;

        foreach ($node->getChildNodes() as $child) {
            self::tree($child, $depth + 1);
        }
    }

    protected static function getCodeSnippet(Node $node)
    {
        $stripped = preg_replace(
            '/\s+/',
            ' ',
            str_replace("\n", ' ', $node->getText())
        );

        return substr($stripped, 0, 50);
    }

    protected static function debug($depth, ...$messages)
    {
        echo str_repeat(' ', $depth) . implode(' ', $messages) . PHP_EOL;
    }

    protected static function debugBreak()
    {
        echo PHP_EOL;
    }
}
