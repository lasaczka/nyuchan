<?php

namespace App\Services;

use App\Enums\PostMarkup;

class PostFormatter
{
    private const string QUOTE_EXTRACT_PATTERN = '/>>(\d{1,10})/';
    private const string QUOTE_RENDER_PATTERN = '/&gt;&gt;(\d{1,10})/';

    private const string CLASS_QUOTE_BROKEN = 'post-quote-broken';
    private const string CLASS_QUOTE_LINK = 'post-quote-link';
    private const string CLASS_GREENTEXT = 'greentext';

    public function format(string $body, ?callable $quoteResolver = null): string
    {
        $escaped = e(str_replace(["\r\n", "\r"], "\n", $body));
        $lines = explode("\n", $escaped);

        $rendered = array_map(function (string $line) use ($quoteResolver): string {
            $trimmed = ltrim($line);
            $isGreentext = str_starts_with($trimmed, '&gt;') && ! str_starts_with($trimmed, '&gt;&gt;');

            $line = $this->applyInlineMarkup($line, $quoteResolver);

            if ($isGreentext) {
                return '<span class="'.self::CLASS_GREENTEXT.'">'.$line.'</span>';
            }

            return $line;
        }, $lines);

        return implode('<br>', $rendered);
    }

    public function extractQuoteIds(array $bodies): array
    {
        $ids = [];

        foreach ($bodies as $body) {
            if (! is_string($body) || $body === '') {
                continue;
            }

            if (preg_match_all(self::QUOTE_EXTRACT_PATTERN, $body, $matches)) {
                foreach ($matches[1] as $id) {
                    $ids[(int) $id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function applyInlineMarkup(string $line, ?callable $quoteResolver): string
    {
        $line = preg_replace_callback(self::QUOTE_RENDER_PATTERN, function (array $m) use ($quoteResolver): string {
            $postId = (int) $m[1];
            $resolved = $quoteResolver ? $quoteResolver($postId) : null;

            if (! is_array($resolved) || empty($resolved['href'])) {
                return '<span class="'.self::CLASS_QUOTE_BROKEN.'">&gt;&gt;'.$postId.'</span>';
            }

            $target = ! empty($resolved['new_tab']) ? ' target="_blank" rel="noopener"' : '';
            $suffix = '';
            if (! empty($resolved['label'])) {
                $suffix = ' ('.e((string) $resolved['label']).')';
            }

            return '<a class="'.self::CLASS_QUOTE_LINK.'" href="'.e((string) $resolved['href']).'"'.$target.'>&gt;&gt;'.$postId.$suffix.'</a>';
        }, $line) ?? $line;

        foreach (PostMarkup::cases() as $markup) {
            $line = $this->applyWrappedMarkup(
                $line,
                $markup->pattern(),
                $markup->delimiter(),
                $markup->tag(),
                $markup->cssClass()
            );
        }

        return $line;
    }

    private function applyWrappedMarkup(string $line, string $pattern, string $delimiter, string $tag, ?string $class = null): string
    {
        return preg_replace_callback($pattern, function (array $m) use ($delimiter, $tag, $class): string {
            $inner = $m[1] ?? '';

            if (trim($inner) === '' || trim(str_replace($delimiter, '', $inner)) === '') {
                return $m[0];
            }

            $classAttr = $class ? ' class="'.$class.'"' : '';

            return '<'.$tag.$classAttr.'>'.$inner.'</'.$tag.'>';
        }, $line) ?? $line;
    }
}
