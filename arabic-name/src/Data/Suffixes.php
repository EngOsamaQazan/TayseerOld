<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Data;

/**
 * Arabic compound name suffixes — words that always bind BACKWARD to the previous word.
 *
 * "الدين" alone is never a standalone name; it always follows another word
 * to form a compound like "صلاح الدين" or "نور الدين".
 */
final class Suffixes
{
    /**
     * Comprehensive suffix database organized by category.
     * These words are NEVER standalone personal names.
     */
    private static array $core = [
        // ═══ … الدين ═══
        'الدين',

        // ═══ … الله ═══
        'الله',

        // ═══ … الرحمن / الرحيم ═══
        'الرحمن', 'الرحيم',

        // ═══ … الإسلام ═══
        'الإسلام', 'الاسلام',

        // ═══ … الحق ═══
        'الحق',

        // ═══ … الدولة ═══
        'الدولة',

        // ═══ … الملك / الملوك ═══
        'الملك', 'الملوك',

        // ═══ … الزمان ═══
        'الزمان',

        // ═══ … الهدى / اليقين / الإيمان ═══
        'الهدى', 'اليقين', 'الإيمان', 'الايمان',

        // ═══ … الحياة ═══
        'الحياة',

        // ═══ … العابدين ═══
        'العابدين',

        // ═══ … الفقار / النورين ═══
        'الفقار', 'النورين',

        // ═══ … النبي ═══
        'النبي',

        // ═══ … المطلب / المسيح ═══
        'المطلب', 'المسيح',

        // ═══ … القيس ═══
        'القيس',

        // ═══ … الفردوس / الجنة ═══
        'الفردوس', 'الجنة',

        // ═══ … الدنيا / الآخرة ═══
        'الدنيا', 'الآخرة', 'الاخرة',

        // ═══ … العرب / الشام / الحجاز / النيل ═══
        'العرب', 'الشام', 'الحجاز', 'النيل',

        // ═══ … الكريم / العظيم / الحكيم (أسماء الله مع اسم قبلها) ═══
        // لا نضيفها هنا لأنها قد تكون أسماء عائلات مستقلة
    ];

    /** @var string[] Additional user-registered suffixes */
    private static array $custom = [];

    /**
     * Get all registered suffixes.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return array_merge(self::$core, self::$custom);
    }

    /**
     * Check if a word is a known suffix.
     */
    public static function is(string $word): bool
    {
        return in_array($word, self::$core, true)
            || in_array($word, self::$custom, true);
    }

    /**
     * Register additional suffixes at runtime.
     *
     * @param string|string[] $suffixes
     */
    public static function add($suffixes): void
    {
        foreach ((array) $suffixes as $s) {
            $s = trim($s);
            if ($s !== '' && !self::is($s)) {
                self::$custom[] = $s;
            }
        }
    }

    /**
     * Reset custom suffixes (useful in tests).
     */
    public static function resetCustom(): void
    {
        self::$custom = [];
    }
}
