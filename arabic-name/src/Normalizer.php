<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

/**
 * Arabic text normalizer for name processing.
 *
 * Handles tashkeel removal, hamza normalization, and
 * splitting stuck-together compound names like "عبدالله" → "عبد الله".
 */
class Normalizer
{
    /**
     * Known no-space compounds mapped to their spaced form.
     * "عبدالله" → "عبد الله"
     */
    private static array $stuckPatterns = [
        'عبدال' => 'عبد ال',
        'ابوال' => 'ابو ال',
        'أبوال' => 'أبو ال',
        'ابنال' => 'ابن ال',
    ];

    /**
     * Known stuck two-word names (no ال in second part).
     * Mapped as full stuck form → spaced form.
     */
    private static array $knownStuck = [
        'أبوبكر' => 'أبو بكر',
        'ابوبكر' => 'ابو بكر',
        'أبوذر'  => 'أبو ذر',
        'ابوذر'  => 'ابو ذر',
        'أبوهريرة' => 'أبو هريرة',
        'ابوهريرة' => 'ابو هريرة',
        'أبوعلي' => 'أبو علي',
        'ابوعلي' => 'ابو علي',
        'أبوعمر' => 'أبو عمر',
        'ابوعمر' => 'ابو عمر',
        'أبوعمار' => 'أبو عمار',
        'ابوعمار' => 'ابو عمار',
        'أبومحمد' => 'أبو محمد',
        'ابومحمد' => 'ابو محمد',
        'أبوأحمد' => 'أبو أحمد',
        'ابواحمد' => 'ابو احمد',
    ];

    /**
     * Fully normalize an Arabic name string.
     */
    public static function normalize(string $name): string
    {
        $name = self::removeTashkeel($name);
        $name = self::normalizeWhitespace($name);
        $name = self::splitStuckCompounds($name);
        $name = self::normalizeWhitespace($name);

        return $name;
    }

    /**
     * Remove Arabic diacritical marks (tashkeel).
     *
     * Removes: فتحة، ضمة، كسرة، سكون، شدة، تنوين
     */
    public static function removeTashkeel(string $text): string
    {
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
    }

    /**
     * Collapse multiple spaces, trim.
     */
    public static function normalizeWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Split stuck-together compounds: "عبدالله" → "عبد الله".
     *
     * Only splits known safe patterns to avoid false positives.
     */
    public static function splitStuckCompounds(string $name): string
    {
        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];

        foreach ($words as $word) {
            $expanded = self::tryExpand($word);
            if ($expanded !== null) {
                $result[] = $expanded;
            } else {
                $result[] = $word;
            }
        }

        return implode(' ', $result);
    }

    /**
     * Try to expand a stuck-together word into its compound parts.
     * Returns null if the word is not a known stuck compound.
     */
    private static function tryExpand(string $word): ?string
    {
        if (mb_strlen($word) < 4) {
            return null;
        }

        if (isset(self::$knownStuck[$word])) {
            return self::$knownStuck[$word];
        }

        foreach (self::$stuckPatterns as $stuck => $spaced) {
            $stuckLen = mb_strlen($stuck);
            if (mb_substr($word, 0, $stuckLen) === $stuck && mb_strlen($word) > $stuckLen) {
                return $spaced . mb_substr($word, $stuckLen);
            }
        }

        return null;
    }
}
