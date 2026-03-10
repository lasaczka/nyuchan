<?php

namespace App\Services;

use App\Enums\PostMarkup;

class PostMacroService
{
    private const string MACRO_REPLY = 'reply';
    private const string MACRO_GREENTEXT = 'greentext';

    public function resolveTemplate(string $key): ?string
    {
        return match ($key) {
            self::MACRO_REPLY => '>>123 ',
            self::MACRO_GREENTEXT => '>',
            default => PostMarkup::templateFor($key),
        };
    }

    public function appendToBody(string $body, string $macro): string
    {
        $separator = $body !== '' && ! str_ends_with($body, "\n") ? "\n" : '';

        return $body.$separator.$macro;
    }
}

