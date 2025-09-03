<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use Microsoft\PhpParser\Node;

abstract class AbstractParser
{
    protected int $depth = 0;

    protected AbstractContext $context;

    public function context(AbstractContext $context)
    {
        $this->context = $context;

        return $this;
    }

    public function parseNode(Node $node): AbstractContext
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

    public function initNewContext(): ?AbstractContext
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

    protected function parentNodeIs(Node $node, array $nodeClasses): bool
    {
        if ($node->getParent() === null) {
            return false;
        }

        return in_array(get_class($node->getParent()), $nodeClasses)
            || $this->parentNodeIs($node->getParent(), $nodeClasses);
    }
}
