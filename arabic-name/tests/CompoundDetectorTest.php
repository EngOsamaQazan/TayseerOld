<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Tests;

use OsamaQazan\ArabicName\CompoundDetector;
use PHPUnit\Framework\TestCase;

class CompoundDetectorTest extends TestCase
{
    /** @dataProvider compoundProvider */
    public function testIsCompound(string $name, bool $expected): void
    {
        $this->assertSame($expected, CompoundDetector::isCompound($name), "isCompound('$name')");
    }

    public static function compoundProvider(): array
    {
        return [
            // Prefix compounds
            ['عبد الله', true],
            ['عبد الرحمن', true],
            ['عبد العزيز', true],
            ['عبد الكريم', true],
            ['عبد المطلب', true],
            ['عبد المسيح', true],
            ['عبد النبي', true],
            ['أبو بكر', true],
            ['أبو القاسم', true],
            ['أبو الحسن', true],
            ['أبو ذر', true],
            ['ابن سينا', true],
            ['ابن خلدون', true],
            ['ابن رشد', true],
            ['بن علي', true],
            ['بنت الهدى', true],
            ['أم كلثوم', true],
            ['آل سعود', true],
            ['آل ثاني', true],
            ['ذو الفقار', true],
            ['ذو النورين', true],
            ['امرؤ القيس', true],

            // Suffix compounds
            ['نور الدين', true],
            ['صلاح الدين', true],
            ['سيف الدين', true],
            ['عماد الدين', true],
            ['حسام الدين', true],
            ['شمس الدين', true],
            ['بهاء الدين', true],
            ['جمال الدين', true],
            ['كمال الدين', true],
            ['زين العابدين', true],
            ['نصر الله', true],
            ['فتح الله', true],
            ['حبيب الله', true],
            ['هبة الله', true],
            ['نعمة الله', true],
            ['سيف الإسلام', true],
            ['نصير الإسلام', true],
            ['ناصر الحق', true],
            ['سيف الدولة', true],
            ['قمر الزمان', true],
            ['فضل الرحمن', true],
            ['نور الهدى', true],
            ['نور اليقين', true],
            ['نور الإيمان', true],

            // Non-compound
            ['محمد', false],
            ['أحمد', false],
            ['خالد', false],
            ['أحمد محمد', false],
            ['محمد العلي', false],
        ];
    }

    public function testLeadingSpanPrefix(): void
    {
        $this->assertSame(2, CompoundDetector::leadingSpan(['عبد', 'الله', 'محمد']));
    }

    public function testLeadingSpanSuffix(): void
    {
        $this->assertSame(2, CompoundDetector::leadingSpan(['نور', 'الدين', 'محمد']));
    }

    public function testLeadingSpanNone(): void
    {
        $this->assertSame(1, CompoundDetector::leadingSpan(['محمد', 'أحمد', 'خالد']));
    }

    public function testTrailingSpanPrefix(): void
    {
        $this->assertSame(2, CompoundDetector::trailingSpan(['محمد', 'أبو', 'عليم']));
    }

    public function testTrailingSpanSuffix(): void
    {
        $this->assertSame(2, CompoundDetector::trailingSpan(['محمد', 'صلاح', 'الدين']));
    }

    public function testTrailingSpanNone(): void
    {
        $this->assertSame(1, CompoundDetector::trailingSpan(['محمد', 'أحمد', 'خالد']));
    }
}
