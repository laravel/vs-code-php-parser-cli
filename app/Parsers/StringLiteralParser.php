<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\StringValue;
use App\Parser\Settings;
use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\PositionUtilities;

class StringLiteralParser extends AbstractParser
{
    /**
     * @var StringValue
     */
    protected AbstractContext $context;

    public function parse(StringLiteral $node)
    {
        $this->context->value = $node->getStringContentsText();

        if (Settings::$capturePosition) {
            $this->context->setPosition(
                PositionUtilities::getRangeFromPosition(
                    $node->getStartPosition(),
                    mb_strlen($node->getStringContentsText()),
                    SourceFile::fullText(),
                ),
            );
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new StringValue;
    }
}
