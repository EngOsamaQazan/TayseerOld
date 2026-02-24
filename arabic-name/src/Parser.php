<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

/**
 * Parses a full Arabic name string into logical parts (first, middle, last),
 * respecting compound name boundaries.
 */
class Parser
{
    /**
     * Parse a full name into a NameResult.
     *
     * @param bool $normalize Whether to normalize the input first (default: true)
     */
    public static function parse(string $fullName, bool $normalize = true): NameResult
    {
        if ($normalize) {
            $fullName = Normalizer::normalize($fullName);
        }

        $words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);

        if ($count === 0) {
            return new NameResult($fullName, '', [], '');
        }

        if ($count === 1) {
            return new NameResult($fullName, $words[0], [], $words[0]);
        }

        if ($count === 2) {
            $isCompound = CompoundDetector::isCompound(implode(' ', $words));
            if ($isCompound) {
                $joined = implode(' ', $words);
                return new NameResult($fullName, $joined, [], $joined);
            }
            return new NameResult($fullName, $words[0], [], $words[1]);
        }

        $firstSpan = CompoundDetector::leadingSpan($words);
        $lastSpan  = CompoundDetector::trailingSpan($words);

        if ($firstSpan + $lastSpan > $count) {
            $firstSpan = min($firstSpan, $count - 1);
            $lastSpan = 1;
        }

        $first = implode(' ', array_slice($words, 0, $firstSpan));
        $last  = implode(' ', array_slice($words, $count - $lastSpan));

        // Middle parts: everything between first compound and last compound
        $middleWords = array_slice($words, $firstSpan, $count - $firstSpan - $lastSpan);
        $middleParts = self::groupMiddle($middleWords);

        return new NameResult($fullName, $first, $middleParts, $last);
    }

    /**
     * Group middle words into logical names, respecting compounds.
     *
     * @param string[] $words
     * @return string[]
     */
    private static function groupMiddle(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $parts = [];
        $i = 0;
        $count = count($words);

        while ($i < $count) {
            $remaining = array_slice($words, $i);
            $span = CompoundDetector::leadingSpan($remaining);

            if ($span > 1 && $i + $span <= $count) {
                $parts[] = implode(' ', array_slice($words, $i, $span));
                $i += $span;
            } else {
                $parts[] = $words[$i];
                $i++;
            }
        }

        return $parts;
    }
}
