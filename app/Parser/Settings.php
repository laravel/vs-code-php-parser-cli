<?php

namespace App\Parser;

use Microsoft\PhpParser\Range;

class Settings
{
    public static bool $capturePosition = false;

    public static $calculatePosition = null;

    public static function adjustPosition($range): Range
    {
        if (self::$calculatePosition !== null) {
            return (self::$calculatePosition)($range);
        }

        return $range;
    }
}
