<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

use OsamaQazan\ArabicName\Data\Prefixes;
use OsamaQazan\ArabicName\Data\Suffixes;

/**
 * ArabicName — Smart Arabic compound name handling.
 *
 * Main entry point (facade) for the library. Provides static methods
 * for parsing, shortening, normalizing, and detecting compound Arabic names.
 *
 * @example
 *   // Shorten a name
 *   ArabicName::shorten("عبد الله محمد أحمد صلاح الدين");
 *   // → "عبد الله صلاح الدين"
 *
 *   // Parse a name into parts
 *   $result = ArabicName::parse("عبد الله محمد أحمد صلاح الدين");
 *   $result->first();   // "عبد الله"
 *   $result->middle();  // ["محمد", "أحمد"]
 *   $result->last();    // "صلاح الدين"
 *
 *   // Detect compounds
 *   ArabicName::isCompound("عبد الله");    // true
 *   ArabicName::isCompound("محمد");          // false
 *
 *   // Normalize
 *   ArabicName::normalize("عبدالله");       // "عبد الله"
 *
 * @author Osama Qazan <eng.osamaqazan@gmail.com>
 * @license MIT
 */
class ArabicName
{
    /**
     * Parse a full name into logical parts (first, middle, last).
     */
    public static function parse(string $fullName): NameResult
    {
        return Parser::parse($fullName);
    }

    /**
     * Shorten a name to first + last logical name.
     */
    public static function shorten(string $fullName): string
    {
        return Shortener::shorten($fullName);
    }

    /**
     * Check if a name string is a compound name.
     */
    public static function isCompound(string $name): bool
    {
        return CompoundDetector::isCompound(
            Normalizer::normalize($name)
        );
    }

    /**
     * Normalize an Arabic name (remove tashkeel, fix spacing, split stuck compounds).
     */
    public static function normalize(string $name): string
    {
        return Normalizer::normalize($name);
    }

    /**
     * Register custom compound prefixes.
     *
     * @param string|string[] $prefixes
     */
    public static function addPrefixes($prefixes): void
    {
        Prefixes::add($prefixes);
    }

    /**
     * Register custom compound suffixes.
     *
     * @param string|string[] $suffixes
     */
    public static function addSuffixes($suffixes): void
    {
        Suffixes::add($suffixes);
    }

    /**
     * Get the first logical name from a full name string.
     */
    public static function firstName(string $fullName): string
    {
        return self::parse($fullName)->first();
    }

    /**
     * Get the last logical name from a full name string.
     */
    public static function lastName(string $fullName): string
    {
        return self::parse($fullName)->last();
    }

    /**
     * Get the middle name parts from a full name string.
     *
     * @return string[]
     */
    public static function middleNames(string $fullName): array
    {
        return self::parse($fullName)->middle();
    }

    /**
     * Get initials from a full name.
     */
    public static function initials(string $fullName): string
    {
        return self::parse($fullName)->initials();
    }
}
