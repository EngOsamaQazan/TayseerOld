<?php

namespace backend\helpers;

class NameHelper
{
    /**
     * Arabic compound prefixes that always bind to the next word.
     * e.g. "عبد الله" = one logical name, not "عبد" alone.
     */
    private static $compoundPrefixes = [
        'عبد', 'ابو', 'أبو', 'أبي', 'ابي', 'ابن', 'بن', 'بنت',
        'أم', 'ام', 'آل', 'ال', 'عوض', 'أبا', 'ابا',
    ];

    /**
     * Shorten a full Arabic name to first + last logical name.
     *
     * Handles compound prefixes so "عبد الله محمد أبو عليم" → "عبد الله أبو عليم"
     * instead of the naive "عبد عليم".
     */
    public static function short(string $full): string
    {
        $words = preg_split('/\s+/', trim($full), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);

        if ($count <= 2) {
            return $full;
        }

        $firstEnd = 1;
        if (in_array($words[0], self::$compoundPrefixes, true) && $count > 2) {
            $firstEnd = 2;
        }

        $lastStart = $count - 1;
        if ($count >= 2 && in_array($words[$count - 2], self::$compoundPrefixes, true)) {
            $lastStart = $count - 2;
        }

        if ($firstEnd > $lastStart) {
            return $full;
        }

        $first = implode(' ', array_slice($words, 0, $firstEnd));
        $last  = implode(' ', array_slice($words, $lastStart));

        if ($first === $last) {
            return $full;
        }

        return $first . ' ' . $last;
    }
}
