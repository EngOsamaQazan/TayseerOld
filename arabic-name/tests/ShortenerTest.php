<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Tests;

use OsamaQazan\ArabicName\Shortener;
use PHPUnit\Framework\TestCase;

class ShortenerTest extends TestCase
{
    // ─── Single / two-word names → return as-is ───

    /** @dataProvider shortNamesProvider */
    public function testShortNamesReturnedAsIs(string $input, string $expected): void
    {
        $this->assertSame($expected, Shortener::shorten($input));
    }

    public static function shortNamesProvider(): array
    {
        return [
            'single word'     => ['محمد', 'محمد'],
            'two words'       => ['أحمد محمد', 'أحمد محمد'],
            'empty string'    => ['', ''],
            'whitespace only' => ['   ', ''],
        ];
    }

    // ─── Prefix compounds (عبد، أبو، ابن…) ───

    /** @dataProvider prefixCompoundsProvider */
    public function testPrefixCompounds(string $input, string $expected): void
    {
        $this->assertSame($expected, Shortener::shorten($input));
    }

    public static function prefixCompoundsProvider(): array
    {
        return [
            'عبد الله first' => [
                'عبد الله محمد أحمد القاضي',
                'عبد الله القاضي',
            ],
            'عبد الرحمن first' => [
                'عبد الرحمن أحمد خالد',
                'عبد الرحمن خالد',
            ],
            'أبو last' => [
                'محمد أحمد أبو عليم',
                'محمد أبو عليم',
            ],
            'أبو البصل last' => [
                'محمد أحمد أبو البصل',
                'محمد أبو البصل',
            ],
            'عبد first + أبو last' => [
                'عبد الله محمد أبو عليم',
                'عبد الله أبو عليم',
            ],
            'ابن last' => [
                'محمد أحمد ابن خلدون',
                'محمد ابن خلدون',
            ],
            'بن middle (not trigger)' => [
                'محمد بن سعد الحارثي',
                'محمد الحارثي',
            ],
            'آل last' => [
                'محمد أحمد آل سعود',
                'محمد آل سعود',
            ],
            'ذو first' => [
                'ذو الفقار محمد أحمد',
                'ذو الفقار أحمد',
            ],
            'أم first' => [
                'أم كلثوم محمد أحمد',
                'أم كلثوم أحمد',
            ],
        ];
    }

    // ─── Suffix compounds (… الدين، … الله، … الإسلام…) ───

    /** @dataProvider suffixCompoundsProvider */
    public function testSuffixCompounds(string $input, string $expected): void
    {
        $this->assertSame($expected, Shortener::shorten($input));
    }

    public static function suffixCompoundsProvider(): array
    {
        return [
            'نور الدين first' => [
                'نور الدين أحمد خالد',
                'نور الدين خالد',
            ],
            'صلاح الدين last' => [
                'محمد أحمد صلاح الدين',
                'محمد صلاح الدين',
            ],
            'سيف الدين first' => [
                'سيف الدين أحمد خالد العلي',
                'سيف الدين العلي',
            ],
            'نصر الله last' => [
                'محمد أحمد نصر الله',
                'محمد نصر الله',
            ],
            'فتح الله last' => [
                'خالد محمد فتح الله',
                'خالد فتح الله',
            ],
            'سيف الإسلام last' => [
                'محمد أحمد سيف الإسلام',
                'محمد سيف الإسلام',
            ],
            'زين العابدين first' => [
                'زين العابدين محمد أحمد',
                'زين العابدين أحمد',
            ],
            'عماد الدين first' => [
                'عماد الدين محمد القاسم',
                'عماد الدين القاسم',
            ],
            'حسام الدين first' => [
                'حسام الدين علي محمد',
                'حسام الدين محمد',
            ],
            'بهاء الدين first' => [
                'بهاء الدين أحمد خالد',
                'بهاء الدين خالد',
            ],
            'ناصر الحق last' => [
                'محمد أحمد ناصر الحق',
                'محمد ناصر الحق',
            ],
            'سيف الدولة last' => [
                'أحمد محمد سيف الدولة',
                'أحمد سيف الدولة',
            ],
            'هبة الله last' => [
                'أحمد محمد هبة الله',
                'أحمد هبة الله',
            ],
            'فضل الرحمن last' => [
                'محمد أحمد فضل الرحمن',
                'محمد فضل الرحمن',
            ],
        ];
    }

    // ─── Both prefix + suffix ───

    /** @dataProvider bothCompoundsProvider */
    public function testBothPrefixAndSuffix(string $input, string $expected): void
    {
        $this->assertSame($expected, Shortener::shorten($input));
    }

    public static function bothCompoundsProvider(): array
    {
        return [
            'عبد first + صلاح الدين last' => [
                'عبد الله محمد صلاح الدين',
                'عبد الله صلاح الدين',
            ],
            'سيف الدين first + أبو last' => [
                'سيف الدين أحمد أبو البصل',
                'سيف الدين أبو البصل',
            ],
            'عبد first + نصر الله last' => [
                'عبد الرحمن محمد نصر الله',
                'عبد الرحمن نصر الله',
            ],
        ];
    }

    // ─── Edge cases: compounds consume all words ───

    /** @dataProvider fullCompoundsProvider */
    public function testFullCompoundsReturnAsIs(string $input, string $expected): void
    {
        $this->assertSame($expected, Shortener::shorten($input));
    }

    public static function fullCompoundsProvider(): array
    {
        return [
            'عبد + أبو (4 words, all compound)' => [
                'عبد الرحمن أبو عليم',
                'عبد الرحمن أبو عليم',
            ],
            'suffix both sides (3 words)' => [
                'محمد صلاح الدين',
                'محمد صلاح الدين',
            ],
            'prefix + suffix (4 words fills)' => [
                'عبد الله نصر الله',
                'عبد الله نصر الله',
            ],
        ];
    }

    // ─── Normalization: stuck compounds ───

    public function testStuckCompoundsAreSplit(): void
    {
        $this->assertSame(
            'عبد الله خالد',
            Shortener::shorten('عبدالله محمد خالد')
        );
    }
}
