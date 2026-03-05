<?php

namespace App\Support;

class PostFormatter
{
    public static function format(string $body, ?callable $quoteResolver = null): string
    {
        $escaped = e(str_replace(["\r\n", "\r"], "\n", $body));
        $lines = explode("\n", $escaped);

        $rendered = array_map(function (string $line) use ($quoteResolver): string {
            $trimmed = ltrim($line);
            $isGreentext = str_starts_with($trimmed, '&gt;') && ! str_starts_with($trimmed, '&gt;&gt;');

            $line = self::applyInlineMarkup($line, $quoteResolver);

            if ($isGreentext) {
                return '<span class="greentext">'.$line.'</span>';
            }

            return $line;
        }, $lines);

        return implode('<br>', $rendered);
    }

    public static function extractQuoteIds(array $bodies): array
    {
        $ids = [];

        foreach ($bodies as $body) {
            if (! is_string($body) || $body === '') {
                continue;
            }

            if (preg_match_all('/>>(\d{1,10})/', $body, $matches)) {
                foreach ($matches[1] as $id) {
                    $ids[(int) $id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private static function applyInlineMarkup(string $line, ?callable $quoteResolver): string
    {
        $line = preg_replace_callback('/&gt;&gt;(\d{1,10})/', function (array $m) use ($quoteResolver): string {
            $postId = (int) $m[1];
            $resolved = $quoteResolver ? $quoteResolver($postId) : null;

            if (! is_array($resolved) || empty($resolved['href'])) {
                return '<span class="post-quote-broken">&gt;&gt;'.$postId.'</span>';
            }

            $target = ! empty($resolved['new_tab']) ? ' target="_blank" rel="noopener"' : '';

            return '<a class="post-quote-link" href="'.e((string) $resolved['href']).'"'.$target.'>&gt;&gt;'.$postId.'</a>';
        }, $line) ?? $line;

        $rules = [
            '/\*\*(.+?)\*\*/s' => '<strong>$1</strong>',
            '/\*(.+?)\*/s' => '<em>$1</em>',
            '/~~(.+?)~~/s' => '<s>$1</s>',
            '/__(.+?)__/s' => '<u>$1</u>',
            '/\|\|(.+?)\|\|/s' => '<span class="spoiler">$1</span>',
        ];

        foreach ($rules as $pattern => $replacement) {
            $line = preg_replace($pattern, $replacement, $line) ?? $line;
        }

        return $line;
    }
}
