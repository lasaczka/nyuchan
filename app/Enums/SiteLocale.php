<?php

namespace App\Enums;

enum SiteLocale: string
{
    case Be = 'be';
    case Ru = 'ru';
    case En = 'en';

    public static function default(): self
    {
        return self::Be;
    }

    public static function fromNullable(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::default();
    }

    public function labelKey(): string
    {
        return 'ui.lang_'.$this->value;
    }
}

