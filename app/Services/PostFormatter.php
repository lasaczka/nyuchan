<?php

namespace App\Services;

use App\Enums\PostMarkup;

class PostFormatter
{
    private const string QUOTE_EXTRACT_PATTERN = '/>>(\d{1,10})/';
    private const string QUOTE_RENDER_PATTERN = '/&gt;&gt;(\d{1,10})/';
    private const string EXTERNAL_URL_PATTERN = '/\bhttps?:\/\/[^\s<]+/iu';
    private const string EXTERNAL_URL_TRAILING_TRIM_CHARS = '.,!?:;)]}';
    private const string NON_URL_ENTITY_PATTERN = '/&(?!amp;)(?:[a-z]{2,16}|#\d{1,8}|#x[0-9a-f]{1,8});/iu';

    private const string CLASS_QUOTE_BROKEN = 'post-quote-broken';
    private const string CLASS_QUOTE_LINK = 'post-quote-link';
    private const string CLASS_GREENTEXT = 'greentext';
    private const string LEADING_NBSP_PATTERN = '/^([\x{00A0}\x{202F}\x{2007}\x{2060}\x{FEFF}]+)/u';

    public function format(string $body, ?callable $quoteResolver = null): string
    {
        $escaped = e(str_replace(["\r\n", "\r"], "\n", $body));
        $lines = explode("\n", $escaped);

        $rendered = array_map(function (string $line) use ($quoteResolver): string {
            $trimmed = ltrim($line);
            $isGreentext = str_starts_with($trimmed, '&gt;') && ! str_starts_with($trimmed, '&gt;&gt;');

            $line = $this->preserveLeadingNbsp($line);
            $line = $this->applyExternalLinks($line);
            $line = $this->applyQuoteLinks($line, $quoteResolver);

            if ($isGreentext) {
                return '<span class="'.self::CLASS_GREENTEXT.'">'.$line.'</span>';
            }

            return $line;
        }, $lines);

        $html = implode('<br>', $rendered);

        foreach (PostMarkup::cases() as $markup) {
            $html = $this->applyWrappedMarkup(
                $html,
                $markup->pattern(),
                $markup->delimiter(),
                $markup->tag(),
                $markup->cssClass()
            );
        }

        return $html;
    }

    private function preserveLeadingNbsp(string $line): string
    {
        return preg_replace_callback(self::LEADING_NBSP_PATTERN, static function (array $matches): string {
            $nbspRun = (string) ($matches[1] ?? '');
            if ($nbspRun === '') {
                return '';
            }

            $count = mb_strlen($nbspRun, 'UTF-8');

            return str_repeat('&nbsp;', $count);
        }, $line) ?? $line;
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

    private function applyQuoteLinks(string $line, ?callable $quoteResolver): string
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

        return $line;
    }

    private function applyExternalLinks(string $line): string
    {
        return preg_replace_callback(self::EXTERNAL_URL_PATTERN, static function (array $matches): string {
            $raw = (string) ($matches[0] ?? '');
            if ($raw === '') {
                return $raw;
            }

            $url = rtrim($raw, self::EXTERNAL_URL_TRAILING_TRIM_CHARS);
            $suffix = substr($raw, strlen($url));
            if (preg_match(self::NON_URL_ENTITY_PATTERN, $url, $entityMatch, PREG_OFFSET_CAPTURE) === 1) {
                $offset = (int) $entityMatch[0][1];
                $suffix = substr($url, $offset).$suffix;
                $url = substr($url, 0, $offset);
            }

            if ($url === '') {
                return $raw;
            }

            return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer nofollow">'.$url.'</a>'.$suffix;
        }, $line) ?? $line;
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
