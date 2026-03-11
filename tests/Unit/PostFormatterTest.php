<?php

namespace Tests\Unit;

use App\Services\PostFormatter;
use Tests\TestCase;

class PostFormatterTest extends TestCase
{
    public function test_multiline_spoiler_is_rendered_as_single_spoiler_block(): void
    {
        $formatter = app(PostFormatter::class);

        $body = "before\n||line one\nline two||\nafter";

        $html = $formatter->format($body);

        $this->assertStringContainsString(
            'before<br><span class="spoiler">line one<br>line two</span><br>after',
            $html
        );
    }

    public function test_empty_spoiler_is_not_wrapped(): void
    {
        $formatter = app(PostFormatter::class);

        $html = $formatter->format('||||');

        $this->assertSame('||||', $html);
        $this->assertStringNotContainsString('class="spoiler"', $html);
    }

    public function test_multiline_wrapped_markup_is_supported_for_all_tags(): void
    {
        $formatter = app(PostFormatter::class);

        $body = "**bold\nline**\n*italic\nline*\n~~strike\nline~~\n__under\nline__";

        $html = $formatter->format($body);

        $this->assertStringContainsString('<strong>bold<br>line</strong>', $html);
        $this->assertStringContainsString('<em>italic<br>line</em>', $html);
        $this->assertStringContainsString('<s>strike<br>line</s>', $html);
        $this->assertStringContainsString('<u>under<br>line</u>', $html);
    }

    public function test_http_and_https_links_are_rendered_as_anchors(): void
    {
        $formatter = app(PostFormatter::class);

        $body = 'Check https://example.com and http://example.org/test?a=1&b=2';

        $html = $formatter->format($body);

        $this->assertStringContainsString(
            '<a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">https://example.com</a>',
            $html
        );
        $this->assertStringContainsString(
            '<a href="http://example.org/test?a=1&amp;b=2" target="_blank" rel="noopener noreferrer nofollow">http://example.org/test?a=1&amp;b=2</a>',
            $html
        );
    }

    public function test_link_trailing_punctuation_is_not_included_in_anchor(): void
    {
        $formatter = app(PostFormatter::class);

        $html = $formatter->format('See https://example.com/test.');

        $this->assertStringContainsString(
            '<a href="https://example.com/test" target="_blank" rel="noopener noreferrer nofollow">https://example.com/test</a>.',
            $html
        );
    }

    public function test_link_stops_before_non_url_html_entity_suffix(): void
    {
        $formatter = app(PostFormatter::class);

        $html = $formatter->format('src="https://example.com/embed?x=1&y=2"&gt;&lt;/iframe&gt;');

        $this->assertStringContainsString(
            '<a href="https://example.com/embed?x=1&amp;y=2" target="_blank" rel="noopener noreferrer nofollow">https://example.com/embed?x=1&amp;y=2</a>',
            $html
        );
        $this->assertStringNotContainsString(
            'href="https://example.com/embed?x=1&amp;y=2&quot;',
            $html
        );
    }
}
