<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

use OsamaQazan\ArabicName\Data\Prefixes;
use OsamaQazan\ArabicName\Data\Suffixes;

/**
 * Detects whether a name (or part of a name) is a compound Arabic name.
 */
class CompoundDetector
{
    /**
     * Check if a name string is compound (consists of multiple bound words).
     *
     * "عبد الله" → true
     * "صلاح الدين" → true
     * "أبو بكر" → true
     * "محمد" → false
     * "أحمد خالد" → false (two separate names, not compound)
     */
    public static function isCompound(string $name): bool
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) < 2) {
            return false;
        }

        if (count($words) === 2) {
            return Prefixes::is($words[0]) || Suffixes::is($words[1]);
        }

        // 3+ words: check if they form a chain of compounds
        return Prefixes::is($words[0]) || Suffixes::is($words[count($words) - 1]);
    }

    /**
     * Determine the span (number of words) of the compound starting at position 0.
     *
     * Given words ["عبد", "الله", "محمد", "أحمد"]:
     *   → returns 2 (because "عبد الله" is the compound)
     *
     * Given words ["نور", "الدين", "محمد"]:
     *   → returns 2 (because "نور الدين" is the compound)
     *
     * Given words ["محمد", "أحمد"]:
     *   → returns 1 (no compound at start)
     */
    public static function leadingSpan(array $words): int
    {
        if (count($words) < 2) {
            return 1;
        }

        if (Prefixes::is($words[0])) {
            return 2;
        }

        if (Suffixes::is($words[1])) {
            return 2;
        }

        return 1;
    }

    /**
     * Determine the span (number of words) of the compound ending at the last position.
     *
     * Given words ["محمد", "أحمد", "أبو", "عليم"]:
     *   → returns 2 (because "أبو عليم" is the compound)
     *
     * Given words ["محمد", "صلاح", "الدين"]:
     *   → returns 2 (because "صلاح الدين" is the compound)
     *
     * Given words ["محمد", "أحمد"]:
     *   → returns 1 (no compound at end)
     */
    public static function trailingSpan(array $words): int
    {
        $count = count($words);

        if ($count < 2) {
            return 1;
        }

        if (Prefixes::is($words[$count - 2])) {
            return 2;
        }

        if (Suffixes::is($words[$count - 1])) {
            return 2;
        }

        return 1;
    }
}
