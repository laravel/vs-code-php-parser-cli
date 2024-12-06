<?php

namespace App\Parser;

use Microsoft\PhpParser\Node\SourceFileNode;

class SourceFile
{
    public static SourceFileNode $sourceFile;

    public static function fullText()
    {
        return self::$sourceFile->getFullText();
    }
}
