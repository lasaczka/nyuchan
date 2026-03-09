<?php

namespace App\Enums;

enum PostMarkup: string
{
    case Bold = 'bold';
    case Italic = 'italic';
    case Strike = 'strike';
    case Underline = 'underline';
    case Spoiler = 'spoiler';

    public static function templateFor(string $key): ?string
    {
        return self::tryFrom($key)?->template();
    }

    public function pattern(): string
    {
        return match ($this) {
            self::Bold => '/\*\*(.*?)\*\*/s',
            self::Italic => '/\*(.*?)\*/s',
            self::Strike => '/~~(.*?)~~/s',
            self::Underline => '/__(.*?)__/s',
            self::Spoiler => '/\|\|(.*?)\|\|/s',
        };
    }

    public function delimiter(): string
    {
        return match ($this) {
            self::Bold, self::Italic => '*',
            self::Strike => '~',
            self::Underline => '_',
            self::Spoiler => '|',
        };
    }

    public function tag(): string
    {
        return match ($this) {
            self::Bold => 'strong',
            self::Italic => 'em',
            self::Strike => 's',
            self::Underline => 'u',
            self::Spoiler => 'span',
        };
    }

    public function cssClass(): ?string
    {
        return match ($this) {
            self::Spoiler => 'spoiler',
            default => null,
        };
    }

    public function template(): string
    {
        return match ($this) {
            self::Bold => '**text**',
            self::Italic => '*text*',
            self::Strike => '~~text~~',
            self::Underline => '__text__',
            self::Spoiler => '||spoiler||',
        };
    }
}
