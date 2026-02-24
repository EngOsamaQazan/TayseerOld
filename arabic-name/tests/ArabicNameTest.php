<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName\Tests;

use OsamaQazan\ArabicName\ArabicName;
use OsamaQazan\ArabicName\Data\Prefixes;
use OsamaQazan\ArabicName\Data\Suffixes;
use PHPUnit\Framework\TestCase;

class ArabicNameTest extends TestCase
{
    protected function tearDown(): void
    {
        Prefixes::resetCustom();
        Suffixes::resetCustom();
    }

    public function testShorten(): void
    {
        $this->assertSame(
            'عبد الله صلاح الدين',
            ArabicName::shorten('عبد الله محمد أحمد صلاح الدين')
        );
    }

    public function testShortenWithStuckCompound(): void
    {
        $this->assertSame(
            'عبد الله خالد',
            ArabicName::shorten('عبدالله محمد خالد')
        );
    }

    public function testParse(): void
    {
        $result = ArabicName::parse('عبد الله محمد أحمد صلاح الدين');
        $this->assertSame('عبد الله', $result->first());
        $this->assertSame(['محمد', 'أحمد'], $result->middle());
        $this->assertSame('صلاح الدين', $result->last());
    }

    public function testIsCompound(): void
    {
        $this->assertTrue(ArabicName::isCompound('عبد الله'));
        $this->assertTrue(ArabicName::isCompound('صلاح الدين'));
        $this->assertTrue(ArabicName::isCompound('أبو بكر'));
        $this->assertFalse(ArabicName::isCompound('محمد'));
        $this->assertFalse(ArabicName::isCompound('أحمد خالد'));
    }

    public function testIsCompoundWithStuck(): void
    {
        $this->assertTrue(ArabicName::isCompound('عبدالله'));
    }

    public function testNormalize(): void
    {
        $this->assertSame('عبد الله', ArabicName::normalize('عبدالله'));
        $this->assertSame('عبد الرحمن', ArabicName::normalize('عبدالرحمن'));
        $this->assertSame('أبو القاسم', ArabicName::normalize('أبوالقاسم'));
    }

    public function testFirstName(): void
    {
        $this->assertSame('عبد الله', ArabicName::firstName('عبد الله محمد أحمد'));
        $this->assertSame('نور الدين', ArabicName::firstName('نور الدين أحمد خالد'));
        $this->assertSame('محمد', ArabicName::firstName('محمد أحمد خالد'));
    }

    public function testLastName(): void
    {
        $this->assertSame('أبو عليم', ArabicName::lastName('محمد أحمد أبو عليم'));
        $this->assertSame('صلاح الدين', ArabicName::lastName('محمد أحمد صلاح الدين'));
        $this->assertSame('خالد', ArabicName::lastName('محمد أحمد خالد'));
    }

    public function testMiddleNames(): void
    {
        $this->assertSame(
            ['محمد', 'أحمد'],
            ArabicName::middleNames('عبد الله محمد أحمد القاضي')
        );
    }

    public function testInitials(): void
    {
        $this->assertSame('ع.ص', ArabicName::initials('عبد الله محمد صلاح الدين'));
        $this->assertSame('م.خ', ArabicName::initials('محمد أحمد خالد'));
        $this->assertSame('ن.أ', ArabicName::initials('نور الدين محمد أحمد'));
    }

    public function testCustomPrefix(): void
    {
        $this->assertSame('شيخ خالد', ArabicName::shorten('شيخ محمد أحمد خالد'));
        // "شيخ" is not built-in, so "شيخ" alone is first name → result is "شيخ خالد"
        // After adding it:
        ArabicName::addPrefixes('شيخ');
        $this->assertSame('شيخ محمد خالد', ArabicName::shorten('شيخ محمد أحمد خالد'));
    }

    public function testCustomSuffix(): void
    {
        ArabicName::addSuffixes('الأول');
        $this->assertTrue(ArabicName::isCompound('محمد الأول'));
    }

    /** @dataProvider realWorldNamesProvider */
    public function testRealWorldNames(string $input, string $expectedShort): void
    {
        $this->assertSame($expectedShort, ArabicName::shorten($input), "shorten('$input')");
    }

    public static function realWorldNamesProvider(): array
    {
        return [
            // ─── Prefix: عبد ───
            ['عبد الله محمد أحمد القاضي', 'عبد الله القاضي'],
            ['عبد الرحمن أحمد خالد سعيد', 'عبد الرحمن سعيد'],
            ['عبد العزيز محمد حسن', 'عبد العزيز حسن'],

            // ─── Prefix: أبو ───
            ['محمد أحمد خالد أبو عليم', 'محمد أبو عليم'],
            ['أحمد محمد أبو البصل', 'أحمد أبو البصل'],
            ['أبو بكر محمد أحمد خالد', 'أبو بكر خالد'],

            // ─── Suffix: الدين ───
            ['نور الدين أحمد خالد العلي', 'نور الدين العلي'],
            ['صلاح الدين محمد أحمد', 'صلاح الدين أحمد'],
            ['سيف الدين أحمد أبو البصل', 'سيف الدين أبو البصل'],
            ['عماد الدين محمد القاسم', 'عماد الدين القاسم'],
            ['حسام الدين علي محمد خالد', 'حسام الدين خالد'],
            ['بهاء الدين أحمد محمد', 'بهاء الدين محمد'],
            ['شمس الدين محمد أحمد العلي', 'شمس الدين العلي'],
            ['جمال الدين محمد أحمد', 'جمال الدين أحمد'],
            ['فخر الدين أحمد محمد', 'فخر الدين محمد'],
            ['محيي الدين أحمد خالد', 'محيي الدين خالد'],
            ['برهان الدين محمد أحمد', 'برهان الدين أحمد'],
            ['شرف الدين أحمد خالد', 'شرف الدين خالد'],
            ['سراج الدين محمد العلي', 'سراج الدين العلي'],

            // ─── Suffix: الله ───
            ['محمد أحمد نصر الله', 'محمد نصر الله'],
            ['أحمد محمد فتح الله', 'أحمد فتح الله'],
            ['خالد محمد حبيب الله', 'خالد حبيب الله'],
            ['محمد أحمد هبة الله', 'محمد هبة الله'],
            ['أحمد خالد عطية الله', 'أحمد عطية الله'],

            // ─── Suffix: الإسلام ───
            ['محمد أحمد سيف الإسلام', 'محمد سيف الإسلام'],
            ['أحمد محمد نصير الإسلام', 'أحمد نصير الإسلام'],

            // ─── Suffix: الحق ───
            ['محمد أحمد ناصر الحق', 'محمد ناصر الحق'],

            // ─── Suffix: الدولة / الزمان ───
            ['أحمد محمد سيف الدولة', 'أحمد سيف الدولة'],
            ['محمد أحمد قمر الزمان', 'محمد قمر الزمان'],

            // ─── Suffix: الهدى / اليقين ───
            ['نور الهدى محمد أحمد', 'نور الهدى أحمد'],
            ['نور اليقين محمد خالد', 'نور اليقين خالد'],

            // ─── Suffix: الرحمن ───
            ['محمد أحمد فضل الرحمن', 'محمد فضل الرحمن'],

            // ─── Special: ذو ───
            ['ذو الفقار محمد أحمد', 'ذو الفقار أحمد'],

            // ─── Special: زين العابدين ───
            ['زين العابدين محمد أحمد خالد', 'زين العابدين خالد'],

            // ─── Special: ابن ───
            ['محمد أحمد ابن خلدون', 'محمد ابن خلدون'],

            // ─── Edge: stuck compounds ───
            ['عبدالله محمد أحمد القاضي', 'عبد الله القاضي'],
            ['عبدالرحمن أحمد خالد', 'عبد الرحمن خالد'],

            // ─── Edge: no shortening needed ───
            ['عبد الرحمن أبو عليم', 'عبد الرحمن أبو عليم'],
            ['محمد صلاح الدين', 'محمد صلاح الدين'],
            ['عبد الله نصر الله', 'عبد الله نصر الله'],
        ];
    }
}
