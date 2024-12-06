<?php

namespace App\Parsers;

use App\Contexts\BaseContext;
use Microsoft\PhpParser\Node;

abstract class AbstractParser
{
    protected int $depth = 0;

    protected BaseContext $context;

    public function context(BaseContext $context)
    {
        $this->context = $context;

        return $this;
    }

    public function parseNode(Node $node): BaseContext
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

    public function initNewContext(): ?BaseContext
    {
        return null;
    }

    public function indent($message)
    {
        return str_repeat('  ', $this->depth) . $message;
    }

    public function loopChildren(): bool
    {
        return true;
    }

    public function debug(...$messages)
    {
        foreach ($messages as $message) {
            echo $this->indent($message) . PHP_EOL;
        }
    }
}
