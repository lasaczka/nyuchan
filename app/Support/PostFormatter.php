<?php

namespace App\Support;

final class PostFormatter
{
    #[\Deprecated('Use App\Services\PostFormatter::format() via DI instead.')]
    public static function format(string $body, ?callable $quoteResolver = null): string
    {
        return app(\App\Services\PostFormatter::class)->format($body, $quoteResolver);
    }

    #[\Deprecated('Use App\Services\PostFormatter::extractQuoteIds() via DI instead.')]
    public static function extractQuoteIds(array $bodies): array
    {
        return app(\App\Services\PostFormatter::class)->extractQuoteIds($bodies);
    }
}
