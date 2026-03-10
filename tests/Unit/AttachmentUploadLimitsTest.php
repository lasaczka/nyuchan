<?php

namespace Tests\Unit;

use App\ValueObjects\AttachmentUploadLimits;
use Tests\TestCase;

class AttachmentUploadLimitsTest extends TestCase
{
    public function test_limits_are_built_from_runtime_config(): void
    {
        config()->set('nyuchan.attachments_input_max_bytes', 6 * 1024 * 1024);
        config()->set('nyuchan.attachments_max_files', 7);

        $limits = AttachmentUploadLimits::fromRuntime();

        $this->assertSame(7, $limits->maxFiles());
        $this->assertSame(6144, $limits->imageMaxKb());
        $this->assertSame(6 * 1024 * 1024, $limits->imageMaxBytes());
        $this->assertSame('6 MB', $limits->imageMaxLabel());
    }

    public function test_limits_format_small_values_as_kb(): void
    {
        $limits = new AttachmentUploadLimits(2, 512, 768 * 1024);

        $this->assertSame('512 KB', $limits->imageMaxLabel());
        $this->assertSame('768 KB', $limits->phpEffectiveMaxLabel());
    }
}

