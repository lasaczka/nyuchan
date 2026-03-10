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
}
