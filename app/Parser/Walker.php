<?php

namespace App\Parser;

use App\Contexts\Base;
use App\Support\Debugs;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
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
        $this->document = $document;
        $this->sourceFile = (new Parser)->parseSourceFile(trim($this->document));
        $this->context = new Context;
    }

    /**
     * If a last character is a double quote, for example:
     *
     * {{ config("
     *
     * then Microsoft\PhpParser\Parser::parseSourceFile returns autocompletingIndex: 1
     * instead 0. Probably the parser turns the string into something like this:
     *
     * "{{ config(";"
     *
     * and returns ";" as an argument.
     *
     * This function parse source file again if last character is a double quote.
     */
    private function setSourceFileAgainIfLastCharacterIsDoubleQuote(string $text): void
    {
        if (substr($text, -1) === '"') {
            $this->sourceFile = (new Parser)->parseSourceFile(trim(substr($text, 0, -1) . "'"));
        }
    }

    protected function documentSkipsClosingQuote()
    {
        if (count($this->sourceFile->statementList) === 1 && $this->sourceFile->statementList[0] instanceof InlineHtml) {
            // Probably Blade...
            $lastChar = substr($this->sourceFile->getFullText(), -1);
            $closesWithQuote = in_array($lastChar, ['"', "'"]);

            return $closesWithQuote;
        }

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

        $this->setSourceFileAgainIfLastCharacterIsDoubleQuote($this->document);

        Parse::$debug = $this->debug;

        $parsed = Parse::parse($this->sourceFile);

        return $parsed;
    }
}
