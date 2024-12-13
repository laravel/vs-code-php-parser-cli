<?php

namespace App\Parser;

use App\Contexts\Base;
use App\Support\Debugs;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\SkippedToken;

class Walker
{
    use Debugs;

    protected Context $context;

    protected $depth = 0;

    protected SourceFileNode $sourceFile;

    protected $postArgumentParsingCallback = null;

    protected $nextNodeToWalk = null;

    public function __construct(protected string $document, $debug = false)
    {
        $this->debug = $debug;
        $this->sourceFile = (new Parser)->parseSourceFile(trim($this->document));
        $this->context = new Context;
    }

    protected function documentSkipsClosingQuote()
    {
        foreach ($this->sourceFile->getDescendantNodesAndTokens() as $child) {
            if ($child instanceof SkippedToken && $child->getText($this->sourceFile->getFullText()) === "'") {
                return true;
            }
        }

        return false;
    }

    public function walk()
    {
        if (!$this->documentSkipsClosingQuote()) {
            return new Base;
        }

        Parse::$debug = $this->debug;

        $parsed = Parse::parse($this->sourceFile);

        return $parsed;
    }
}
