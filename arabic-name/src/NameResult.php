<?php

declare(strict_types=1);

namespace OsamaQazan\ArabicName;

/**
 * Immutable value object representing a parsed Arabic name.
 */
class NameResult
{
    private string $original;
    private string $first;
    /** @var string[] */
    private array $middle;
    private string $last;

    /**
     * @param string[] $middle
     */
    public function __construct(string $original, string $first, array $middle, string $last)
    {
        $this->original = $original;
        $this->first = $first;
        $this->middle = $middle;
        $this->last = $last;
    }

    public function original(): string
    {
        return $this->original;
    }

    /** First logical name (may be compound: "عبد الله") */
    public function first(): string
    {
        return $this->first;
    }

    /**
     * Middle name parts (each may be compound).
     * @return string[]
     */
    public function middle(): array
    {
        return $this->middle;
    }

    /** Last logical name (may be compound: "أبو عليم", "صلاح الدين") */
    public function last(): string
    {
        return $this->last;
    }

    /** Full name reconstructed from parsed parts. */
    public function full(): string
    {
        $parts = [$this->first];
        foreach ($this->middle as $m) {
            $parts[] = $m;
        }
        $parts[] = $this->last;

        return implode(' ', $parts);
    }

    /** Shortened form: first + last only. */
    public function short(): string
    {
        if (empty($this->middle) && $this->first === $this->last) {
            return $this->first;
        }

        if (empty($this->middle)) {
            return $this->first . ' ' . $this->last;
        }

        return $this->first . ' ' . $this->last;
    }

    /** Arabic initials using the first letter of first and last logical names. */
    public function initials(): string
    {
        $f = mb_substr($this->first, 0, 1);
        $l = mb_substr($this->last, 0, 1);

        if ($f === $l && empty($this->middle)) {
            return $f;
        }

        return $f . '.' . $l;
    }

    /** Formal greeting form. */
    public function greeting(string $prefix = 'السيد'): string
    {
        return $prefix . ' ' . $this->first;
    }

    /** Number of logical parts (first + middle[] + last). */
    public function partCount(): int
    {
        return 1 + count($this->middle) + (($this->first !== $this->last || !empty($this->middle)) ? 1 : 0);
    }

    /** Convert to associative array. */
    public function toArray(): array
    {
        return [
            'original' => $this->original,
            'first'    => $this->first,
            'middle'   => $this->middle,
            'last'     => $this->last,
            'short'    => $this->short(),
            'initials' => $this->initials(),
        ];
    }
}
