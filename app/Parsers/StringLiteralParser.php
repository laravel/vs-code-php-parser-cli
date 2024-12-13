<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\StringValue;
use App\Parser\Settings;
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
            $range = PositionUtilities::getRangeFromPosition(
                $node->getStartPosition(),
                mb_strlen($node->getStringContentsText()),
                $node->getRoot()->getFullText(),
            );

            if (Settings::$calculatePosition !== null) {
                $range = Settings::adjustPosition($range);
            }

            $this->context->setPosition($range);
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        return new StringValue;
    }
}
