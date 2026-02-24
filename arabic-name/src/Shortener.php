<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

/**
 * Shortens Arabic names to first + last logical name,
 * respecting compound name boundaries.
 */
class Shortener
{
    /**
     * Shorten a full name to its first and last logical names.
     *
     * Examples:
     *   "عبد الله محمد أبو عليم"     → "عبد الله أبو عليم"
     *   "نور الدين أحمد خالد"        → "نور الدين خالد"
     *   "محمد أحمد صلاح الدين"       → "محمد صلاح الدين"
     *   "سيف الدين أحمد أبو البصل"   → "سيف الدين أبو البصل"
     *   "أحمد محمد"                   → "أحمد محمد" (already short)
     *
     * @param bool $normalize Whether to normalize input first (default: true)
     */
    public static function shorten(string $fullName, bool $normalize = true): string
    {
        if ($normalize) {
            $fullName = Normalizer::normalize($fullName);
        }

        $words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);

        if ($count <= 2) {
            return implode(' ', $words);
        }

        $firstSpan = CompoundDetector::leadingSpan($words);
        $lastSpan  = CompoundDetector::trailingSpan($words);

        if ($firstSpan + $lastSpan >= $count) {
            return implode(' ', $words);
        }

        $first = implode(' ', array_slice($words, 0, $firstSpan));
        $last  = implode(' ', array_slice($words, $count - $lastSpan));

        return $first . ' ' . $last;
    }
}
