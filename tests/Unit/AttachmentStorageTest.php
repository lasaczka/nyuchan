<?php

namespace Tests\Unit;

use App\Services\AttachmentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttachmentStorageTest extends TestCase
{
    public function test_store_uploaded_file_writes_attachment_and_thumb(): void
    {
        Storage::fake('local');
        config()->set('nyuchan.attachments_disk', 'local');
        config()->set('nyuchan.attachments_input_max_bytes', 5 * 1024 * 1024);
        config()->set('nyuchan.attachments_target_max_bytes', 5 * 1024 * 1024);

        $service = app(AttachmentStorage::class);
        $upload = UploadedFile::fake()->image('sample.jpg', 32, 32);

        $stored = $service->storeUploadedFile($upload);

        $this->assertArrayHasKey('path', $stored);
        $this->assertArrayHasKey('thumb_path', $stored);
        $this->assertNotNull($stored['thumb_path']);
        Storage::disk('local')->assertExists($stored['path']);
        Storage::disk('local')->assertExists($stored['thumb_path']);
    }

    public function test_store_uploaded_file_rejects_too_large_input(): void
    {
        Storage::fake('local');
        config()->set('nyuchan.attachments_disk', 'local');
        config()->set('nyuchan.attachments_input_max_bytes', 10 * 1024);
        config()->set('nyuchan.attachments_target_max_bytes', 10 * 1024);

        $service = app(AttachmentStorage::class);
        $upload = UploadedFile::fake()->image('huge.jpg', 300, 300)->size(512);

        $this->expectException(ValidationException::class);
        $service->storeUploadedFile($upload);
    }

    public function test_read_stream_uses_fallback_disks_when_primary_missing_file(): void
    {
        Storage::fake('primary');
        Storage::fake('backup');
        Storage::disk('backup')->put('attachments/a.txt', 'hello');

        config()->set('nyuchan.attachments_disk', 'primary');
        config()->set('nyuchan.attachments_fallback_disks', ['backup']);

        $service = app(AttachmentStorage::class);
        $stream = $service->readStream('attachments/a.txt');

        $this->assertIsResource($stream);
        $this->assertSame('hello', stream_get_contents($stream));
        fclose($stream);
    }

    public function test_store_uploaded_file_rejects_large_gif_without_compression(): void
    {
        Storage::fake('local');
        config()->set('nyuchan.attachments_disk', 'local');
        config()->set('nyuchan.attachments_input_max_bytes', 5 * 1024 * 1024);
        config()->set('nyuchan.attachments_target_max_bytes', 1024);
        config()->set('nyuchan.attachments_auto_compress_mimes', ['image/jpeg', 'image/png', 'image/webp']);

        $gifHeader = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF!\xF9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
        $upload = UploadedFile::fake()->createWithContent('anim.gif', str_repeat($gifHeader, 64));

        $service = app(AttachmentStorage::class);

        try {
            $service->storeUploadedFile($upload);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('image', $e->errors());
        }
    }

    public function test_store_uploaded_file_compresses_large_jpeg_to_target_size(): void
    {
        Storage::fake('local');
        config()->set('nyuchan.attachments_disk', 'local');
        config()->set('nyuchan.attachments_input_max_bytes', 5 * 1024 * 1024);
        config()->set('nyuchan.attachments_target_max_bytes', 100 * 1024);
        config()->set('nyuchan.attachments_auto_compress_mimes', ['image/jpeg', 'image/png', 'image/webp']);

        $service = app(AttachmentStorage::class);
        $upload = UploadedFile::fake()->image('big.jpg', 1800, 1800)->size(1800);

        $stored = $service->storeUploadedFile($upload);

        $this->assertSame('image/jpeg', $stored['mime']);
        $this->assertLessThanOrEqual(100 * 1024, $stored['size']);
        Storage::disk('local')->assertExists($stored['path']);
    }

    public function test_store_uploaded_file_can_strip_metadata_when_requested(): void
    {
        Storage::fake('local');
        config()->set('nyuchan.attachments_disk', 'local');
        config()->set('nyuchan.attachments_input_max_bytes', 5 * 1024 * 1024);
        config()->set('nyuchan.attachments_target_max_bytes', 5 * 1024 * 1024);

        $service = app(AttachmentStorage::class);
        $upload = UploadedFile::fake()->image('meta.jpg', 64, 64);

        $stored = $service->storeUploadedFile($upload, true);

        $this->assertSame('image/jpeg', $stored['mime']);
        Storage::disk('local')->assertExists($stored['path']);
    }
}
