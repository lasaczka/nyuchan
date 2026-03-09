<?php /** @noinspection ALL */

namespace App\Enums;

enum SiteTheme: string
{
    case Sugar = 'sugar';
    case Makaba = 'makaba';
    case Rel = 're-l';
    case Nyu = 'nyu';
    case Futaba = 'futaba';
    case Yotsuba = 'yotsuba';
    case Lelouch = 'lelouch';

    public static function default(): self
    {
        return self::Sugar;
    }

    public static function fromNullable(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::default();
    }

    public function labelKey(): string
    {
        return match ($this) {
            self::Rel => 'ui.theme_rel',
            default => 'ui.theme_'.$this->value,
        };
    }
}

