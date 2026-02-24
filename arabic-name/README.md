# ArabicName

Smart Arabic compound name detection, parsing, shortening, and normalization for PHP.

Handles **200+ compound Arabic name patterns** including:
- Prefix compounds: عبد الله، أبو بكر، ابن خلدون، ذو الفقار
- Suffix compounds: صلاح الدين، نصر الله، سيف الإسلام، قمر الزمان
- Stuck compounds: عبدالله → عبد الله

## Installation

```bash
composer require osamaqazan/arabic-name
```

## Quick Start

```php
use OsamaQazan\ArabicName\ArabicName;

// Shorten a name (first + last logical name)
ArabicName::shorten("عبد الله محمد أحمد صلاح الدين");
// → "عبد الله صلاح الدين"

// Parse into parts
$name = ArabicName::parse("عبد الله محمد أحمد صلاح الدين");
$name->first();    // "عبد الله"
$name->middle();   // ["محمد", "أحمد"]
$name->last();     // "صلاح الدين"
$name->short();    // "عبد الله صلاح الدين"
$name->initials(); // "ع.ص"

// Detect compound names
ArabicName::isCompound("عبد الله");     // true
ArabicName::isCompound("صلاح الدين");   // true
ArabicName::isCompound("محمد");          // false

// Normalize (fix spacing, remove tashkeel)
ArabicName::normalize("عبدالله");        // "عبد الله"
ArabicName::normalize("عَبْدُ اللَّهِ"); // "عبد الله"
```

## The Problem

Arabic names frequently contain **compound constructs** — two or more words that form a single logical name:

| Pattern | Example | Type |
|---------|---------|------|
| عبد + اسم | عبد الله، عبد الرحمن | Prefix |
| أبو + اسم | أبو بكر، أبو القاسم | Prefix |
| اسم + الدين | صلاح الدين، نور الدين | Suffix |
| اسم + الله | نصر الله، فتح الله | Suffix |
| اسم + الإسلام | سيف الإسلام، نصير الإسلام | Suffix |

Naive name splitting breaks these compounds:

```
"عبد الله محمد أبو عليم"
  ❌ Naive:  first="عبد"  last="عليم"
  ✅ Smart:  first="عبد الله"  last="أبو عليم"

"نور الدين أحمد صلاح الدين"  
  ❌ Naive:  first="نور"  last="الدين"
  ✅ Smart:  first="نور الدين"  last="صلاح الدين"
```

## API Reference

### `ArabicName::shorten(string $name): string`

Shortens a full name to first + last logical name.

```php
ArabicName::shorten("محمد أحمد خالد أبو عليم");  // "محمد أبو عليم"
ArabicName::shorten("سيف الدين أحمد أبو البصل"); // "سيف الدين أبو البصل"
ArabicName::shorten("محمد أحمد نصر الله");        // "محمد نصر الله"
ArabicName::shorten("عبدالله محمد خالد");          // "عبد الله خالد"
```

### `ArabicName::parse(string $name): NameResult`

Parses a full name into logical parts.

```php
$r = ArabicName::parse("عبد الله محمد أحمد أبو عليم");
$r->first();     // "عبد الله"
$r->middle();    // ["محمد", "أحمد"]
$r->last();      // "أبو عليم"
$r->full();      // "عبد الله محمد أحمد أبو عليم"
$r->short();     // "عبد الله أبو عليم"
$r->initials();  // "ع.أ"
$r->greeting();  // "السيد عبد الله"
$r->toArray();   // ['first' => ..., 'middle' => ..., 'last' => ..., ...]
```

### `ArabicName::isCompound(string $name): bool`

Checks if a name string is a compound name.

```php
ArabicName::isCompound("عبد الله");    // true
ArabicName::isCompound("أبو بكر");     // true
ArabicName::isCompound("صلاح الدين");  // true
ArabicName::isCompound("نصر الله");    // true
ArabicName::isCompound("محمد");         // false
ArabicName::isCompound("أحمد خالد");   // false
```

### `ArabicName::normalize(string $name): string`

Normalizes Arabic name text.

```php
ArabicName::normalize("عبدالله");           // "عبد الله"
ArabicName::normalize("عبدالرحمن");         // "عبد الرحمن"
ArabicName::normalize("أبوالقاسم");          // "أبو القاسم"
ArabicName::normalize("عَبْدُ اللَّهِ");    // "عبد الله"
ArabicName::normalize("  محمد   أحمد  ");  // "محمد أحمد"
```

### `ArabicName::firstName(string $name): string`

Extract the first logical name.

### `ArabicName::lastName(string $name): string`

Extract the last logical name.

### `ArabicName::middleNames(string $name): string[]`

Extract middle name parts.

### `ArabicName::initials(string $name): string`

Get Arabic initials.

### Custom Patterns

Register additional compound patterns at runtime:

```php
ArabicName::addPrefixes('شيخ');      // "شيخ محمد" → compound
ArabicName::addSuffixes('الأول');    // "محمد الأول" → compound
ArabicName::addPrefixes(['سيدي', 'مولاي']); // Multiple at once
```

## Supported Compound Patterns

### Prefixes (bind forward)

| Prefix | Examples |
|--------|----------|
| عبد | عبد الله، عبد الرحمن، عبد العزيز، عبد الكريم... |
| أبو/ابو | أبو بكر، أبو القاسم، أبو الحسن، أبو ذر... |
| أم/ام | أم كلثوم، أم حبيبة... |
| ابن/بن | ابن سينا، ابن خلدون، بن علي... |
| بنت | بنت الهدى، بنت الإسلام... |
| ذو/ذي | ذو الفقار، ذو النورين... |
| آل | آل سعود، آل ثاني... |
| امرؤ | امرؤ القيس |

### Suffixes (bind backward)

| Suffix | Examples |
|--------|----------|
| الدين | صلاح الدين، نور الدين، سيف الدين، عماد الدين، حسام الدين، شمس الدين، جمال الدين، بهاء الدين، فخر الدين، محيي الدين، برهان الدين، شرف الدين، سراج الدين... |
| الله | نصر الله، فتح الله، حبيب الله، هبة الله، نعمة الله، عطية الله، فضل الله، رزق الله، كرم الله... |
| الرحمن | فضل الرحمن، عطاء الرحمن، رزق الرحمن، نصر الرحمن... |
| الإسلام | سيف الإسلام، نصير الإسلام، ناصر الإسلام، فخر الإسلام... |
| الحق | ناصر الحق، نصير الحق، سيف الحق، مؤيد الحق... |
| الدولة | سيف الدولة، ناصر الدولة، عماد الدولة... |
| الزمان | قمر الزمان، فخر الزمان، بدر الزمان... |
| الهدى | نور الهدى، بنت الهدى... |
| العابدين | زين العابدين |
| الملك/الملوك | تاج الملوك، ناصر الملك... |

## Requirements

- PHP >= 7.4
- `mbstring` extension

## Testing

```bash
composer install
composer test
```

## License

MIT License - see [LICENSE](LICENSE) file.

## Author

**Osama Qazan** — [eng.osamaqazan@gmail.com](mailto:eng.osamaqazan@gmail.com)

---

<div dir="rtl">

## بالعربي

مكتبة PHP ذكية للتعامل مع الأسماء العربية المركبة. تكتشف وتعالج أكثر من 200 نمط تركيب في الأسماء العربية.

### المشكلة

عند اختصار الأسماء العربية، التقسيم البسيط يكسر الأسماء المركبة:

```
"عبد الله محمد أبو عليم"
  ❌ الطريقة الخاطئة: "عبد عليم"
  ✅ الطريقة الصحيحة: "عبد الله أبو عليم"

"نور الدين أحمد صلاح الدين"
  ❌ الطريقة الخاطئة: "نور الدين"
  ✅ الطريقة الصحيحة: "نور الدين صلاح الدين"
```

### الاستخدام

```php
use OsamaQazan\ArabicName\ArabicName;

// اختصار الاسم
ArabicName::shorten("عبد الله محمد أحمد صلاح الدين");
// ← "عبد الله صلاح الدين"

// تقسيم الاسم
$name = ArabicName::parse("عبد الله محمد أحمد صلاح الدين");
$name->first();    // "عبد الله"
$name->middle();   // ["محمد", "أحمد"]
$name->last();     // "صلاح الدين"

// كشف التركيب
ArabicName::isCompound("صلاح الدين"); // true

// تطبيع الكتابة
ArabicName::normalize("عبدالله"); // "عبد الله"
```

</div>
