<?php

namespace App\Parser\Parsers;

use App\Parser\Context;
use App\Parser\Parse;
use Microsoft\PhpParser\Node;

abstract class AbstractParser
{
    protected int $depth = 0;

    protected Context $context;

    // public function __construct()
    // {
    //     $this->context = new Context();
    // }

    public function context(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    public function parseNode(Node $node): Context
    {
        if (method_exists($this, 'parse')) {
            return call_user_func([$this, 'parse'], $node);
        }

        return $this->context;
    }

    public function depth(int $depth)
    {
        $this->depth = $depth;

        return $this;
    }

    public function shouldInitNewContext()
    {
        return false;
    }

    public function indent($message)
    {
        return str_repeat('  ', $this->depth) . $message;
    }

    public function debug(...$messages)
    {
        return;
        foreach ($messages as $message) {
            echo $this->indent($message) . PHP_EOL;
        }
    }

    protected function loopChildren(Node $node)
    {
        foreach ($node->getChildNodes() as $child) {
            $this->debug('Child: ' . $child::class);
            $this->addChild(Parse::parse($child, $this->depth + 1, $this->context));
        }
    }

    protected function addChild($child)
    {
        // if ($child) {
        //     $this->context->child = new Context($child);
        //     $this->context = $this->context->child;
        // }

        // $filtered = $child->filter();

        // if ($filtered->isNotEmpty()) {
        //     $this->context->child = new Context($filtered->last());
        //     $this->context = $this->context->child;
        // }
    }
}
