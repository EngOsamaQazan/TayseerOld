<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Tests;

use OsamaQazan\ArabicName\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function testRemoveTashkeel(): void
    {
        $this->assertSame('عبد الله', Normalizer::removeTashkeel('عَبْدُ اللَّهِ'));
        $this->assertSame('محمد', Normalizer::removeTashkeel('مُحَمَّد'));
    }

    public function testNormalizeWhitespace(): void
    {
        $this->assertSame('محمد أحمد', Normalizer::normalizeWhitespace('  محمد   أحمد  '));
    }

    /** @dataProvider stuckCompoundsProvider */
    public function testSplitStuckCompounds(string $input, string $expected): void
    {
        $this->assertSame($expected, Normalizer::splitStuckCompounds($input));
    }

    public static function stuckCompoundsProvider(): array
    {
        return [
            ['عبدالله', 'عبد الله'],
            ['عبدالرحمن', 'عبد الرحمن'],
            ['عبدالعزيز', 'عبد العزيز'],
            ['عبدالكريم', 'عبد الكريم'],
            ['عبدالمجيد', 'عبد المجيد'],
            ['أبوبكر', 'أبو بكر'],
            ['أبوالقاسم', 'أبو القاسم'],
            ['ابوالحسن', 'ابو الحسن'],
            ['محمد', 'محمد'],
            ['أحمد', 'أحمد'],
        ];
    }

    public function testFullNormalize(): void
    {
        $this->assertSame(
            'عبد الله محمد',
            Normalizer::normalize('  عبدالله   محمد  ')
        );
    }

    public function testTashkeelAndStuck(): void
    {
        $this->assertSame(
            'عبد الله',
            Normalizer::normalize('عَبْدُاللَّهِ')
        );
    }
}
