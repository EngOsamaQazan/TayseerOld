<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Tests;

use OsamaQazan\ArabicName\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /** @dataProvider parseProvider */
    public function testParse(
        string $input,
        string $expectedFirst,
        array $expectedMiddle,
        string $expectedLast
    ): void {
        $result = Parser::parse($input);
        $this->assertSame($expectedFirst, $result->first(), "first() for: $input");
        $this->assertSame($expectedMiddle, $result->middle(), "middle() for: $input");
        $this->assertSame($expectedLast, $result->last(), "last() for: $input");
    }

    public static function parseProvider(): array
    {
        return [
            'simple 4-word' => [
                'محمد أحمد خالد العلي',
                'محمد', ['أحمد', 'خالد'], 'العلي',
            ],
            'prefix first' => [
                'عبد الله محمد أحمد القاضي',
                'عبد الله', ['محمد', 'أحمد'], 'القاضي',
            ],
            'prefix last' => [
                'محمد أحمد خالد أبو عليم',
                'محمد', ['أحمد', 'خالد'], 'أبو عليم',
            ],
            'suffix first (الدين)' => [
                'نور الدين محمد أحمد خالد',
                'نور الدين', ['محمد', 'أحمد'], 'خالد',
            ],
            'suffix last (الدين)' => [
                'محمد أحمد خالد صلاح الدين',
                'محمد', ['أحمد', 'خالد'], 'صلاح الدين',
            ],
            'both prefix + suffix' => [
                'عبد الله محمد أحمد صلاح الدين',
                'عبد الله', ['محمد', 'أحمد'], 'صلاح الدين',
            ],
            'compound in middle' => [
                'محمد عبد الكريم أحمد العلي',
                'محمد', ['عبد الكريم', 'أحمد'], 'العلي',
            ],
            'single word' => [
                'محمد',
                'محمد', [], 'محمد',
            ],
            'two words' => [
                'محمد أحمد',
                'محمد', [], 'أحمد',
            ],
            'two-word compound' => [
                'عبد الله',
                'عبد الله', [], 'عبد الله',
            ],
            'three words simple' => [
                'محمد أحمد خالد',
                'محمد', ['أحمد'], 'خالد',
            ],
            'suffix last (الله)' => [
                'محمد أحمد نصر الله',
                'محمد', ['أحمد'], 'نصر الله',
            ],
            'suffix last (الإسلام)' => [
                'أحمد محمد سيف الإسلام',
                'أحمد', ['محمد'], 'سيف الإسلام',
            ],
        ];
    }

    public function testShortFromResult(): void
    {
        $result = Parser::parse('عبد الله محمد أحمد صلاح الدين');
        $this->assertSame('عبد الله صلاح الدين', $result->short());
    }

    public function testInitialsFromResult(): void
    {
        $result = Parser::parse('عبد الله محمد أحمد صلاح الدين');
        $this->assertSame('ع.ص', $result->initials());
    }

    public function testGreetingFromResult(): void
    {
        $result = Parser::parse('عبد الله محمد أحمد');
        $this->assertSame('السيد عبد الله', $result->greeting());
    }

    public function testToArray(): void
    {
        $result = Parser::parse('نور الدين محمد خالد');
        $arr = $result->toArray();
        $this->assertSame('نور الدين', $arr['first']);
        $this->assertSame(['محمد'], $arr['middle']);
        $this->assertSame('خالد', $arr['last']);
        $this->assertSame('نور الدين خالد', $arr['short']);
    }
}
