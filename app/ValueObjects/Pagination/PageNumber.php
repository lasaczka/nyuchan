<?php

namespace App\ValueObjects\Pagination;

use InvalidArgumentException;

final readonly class PageNumber
{
    public function __construct(
        private int $value
    ) {
        if ($value < 1) {
            throw new InvalidArgumentException('Page number must be >= 1.');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function plus(int $delta): self
    {
        return new self($this->value + $delta);
    }

    public function minus(int $delta): self
    {
        return new self($this->value - $delta);
    }

    public function clamp(self $min, self $max): self
    {
        return new self(max($min->value(), min($max->value(), $this->value)));
    }
}

