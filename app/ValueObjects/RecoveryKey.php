<?php

namespace App\ValueObjects;

use Illuminate\Support\Str;

class RecoveryKey
{
    private const int RAW_LENGTH = 32;
    private string $key;

    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function generate(): self
    {
        $key = Str::upper(Str::random(self::RAW_LENGTH));

        return new self(implode(separator: '-', array: str_split($key, 4)));
    }

    public static function fromInput(string $key): self
    {
        return new self($key);
    }

    public function value(): string
    {
        return $this->key;
    }

    public function normalized(): string
    {
        return Str::upper((string)preg_replace(pattern: '/[^A-Za-z0-9]/', replacement: '', subject: $this->key));
    }

    public function hash(): string
    {
        return hash(algo: 'sha256', data: $this->normalized());
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
