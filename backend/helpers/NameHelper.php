<?php

namespace backend\helpers;

class NameHelper
{
    /**
     * Prefixes: words that always bind FORWARD to the next word.
     * "عبد الله" → "عبد" alone is incomplete.
     */
    private static $prefixes = [
        'عبد', 'ابو', 'أبو', 'أبي', 'ابي', 'ابن', 'بن', 'بنت',
        'أم', 'ام', 'آل', 'ال', 'عوض', 'أبا', 'ابا', 'ذو', 'ذي', 'امرؤ',
    ];

    /**
     * Suffixes: words that always bind BACKWARD to the previous word.
     * "الدين" alone is never a standalone name — always "صلاح الدين", "نور الدين", etc.
     */
    private static $suffixes = [
        'الدين', 'الله', 'الرحمن', 'الرحيم',
        'الإسلام', 'الاسلام',
        'الحق', 'الدولة', 'الملك', 'الملوك',
        'الزمان', 'الهدى', 'اليقين',
        'الإيمان', 'الايمان', 'الحياة',
        'العابدين', 'الفقار', 'النورين',
        'النبي', 'المطلب', 'المسيح', 'القيس',
        'الفردوس', 'الجنة',
    ];

    /**
     * Shorten a full Arabic name to first + last logical name.
     *
     * Handles both prefix compounds (عبد الله, أبو عليم) and
     * suffix compounds (صلاح الدين, نصر الله, سيف الإسلام).
     *
     * Examples:
     *   "عبد الله محمد أبو عليم"    → "عبد الله أبو عليم"
     *   "نور الدين أحمد خالد"       → "نور الدين خالد"
     *   "محمد أحمد صلاح الدين"      → "محمد صلاح الدين"
     *   "سيف الدين أحمد أبو البصل"  → "سيف الدين أبو البصل"
     *   "محمد أحمد نصر الله"        → "محمد نصر الله"
     */
    public static function short(string $full): string
    {
        $words = preg_split('/\s+/', trim($full), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);

        if ($count <= 2) {
            return $full;
        }

        $firstLen = 1;
        if (in_array($words[0], self::$prefixes, true)) {
            $firstLen = 2;
        } elseif ($count > 1 && in_array($words[1], self::$suffixes, true)) {
            $firstLen = 2;
        }

        $lastLen = 1;
        if (in_array($words[$count - 2], self::$prefixes, true)) {
            $lastLen = 2;
        } elseif (in_array($words[$count - 1], self::$suffixes, true)) {
            $lastLen = 2;
        }

        if ($firstLen + $lastLen >= $count) {
            return $full;
        }

        $first = implode(' ', array_slice($words, 0, $firstLen));
        $last  = implode(' ', array_slice($words, $count - $lastLen));

        return $first . ' ' . $last;
    }
}
