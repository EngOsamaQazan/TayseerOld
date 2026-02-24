<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Data;

/**
 * Arabic compound name prefixes — words that always bind FORWARD to the next word.
 *
 * "عبد" alone is never a complete name; it must be followed by another word
 * to form a compound like "عبد الله" or "عبد الرحمن".
 */
final class Prefixes
{
    /**
     * Core prefixes that appear across all Arabic dialects.
     * Each entry may appear with or without hamza/alef variations.
     */
    private static array $core = [
        // عبد + أسماء الله الحسنى
        'عبد',

        // أبو/أبي — كنية أو اسم عائلة مركب
        'أبو', 'ابو', 'أبي', 'ابي', 'أبا', 'ابا',

        // أم — كنية
        'أم', 'ام',

        // ابن/بن/بنت — نسب
        'ابن', 'بن', 'بنت',

        // ذو/ذي — صاحب
        'ذو', 'ذي',

        // آل — عائلة (آل سعود، آل ثاني)
        'آل',

        // امرؤ — (امرؤ القيس)
        'امرؤ', 'امرء',

        // عوض — (عوض الله)
        'عوض',
    ];

    /** @var string[] Additional user-registered prefixes */
    private static array $custom = [];

    /**
     * Get all registered prefixes.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return array_merge(self::$core, self::$custom);
    }

    /**
     * Check if a word is a known prefix.
     */
    public static function is(string $word): bool
    {
        return in_array($word, self::$core, true)
            || in_array($word, self::$custom, true);
    }

    /**
     * Register additional prefixes at runtime.
     *
     * @param string|string[] $prefixes
     */
    public static function add($prefixes): void
    {
        foreach ((array) $prefixes as $p) {
            $p = trim($p);
            if ($p !== '' && !self::is($p)) {
                self::$custom[] = $p;
            }
        }
    }

    /**
     * Reset custom prefixes (useful in tests).
     */
    public static function resetCustom(): void
    {
        self::$custom = [];
    }
}
