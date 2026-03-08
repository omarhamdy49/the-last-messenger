<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_view_upload_update_and_delete_media(): void
    {
        Storage::fake('local');

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->get(route('admin.media-library.index'))
            ->assertOk();

        $uploadResponse = $this->post(
            route('admin.media-library.store'),
            [
                'file' => UploadedFile::fake()->image('cover.png', 800, 600),
                'collection_name' => 'images',
                'alt_text' => 'Initial alt text',
                'caption' => 'Initial caption',
                'tags' => ['seerah', 'cover'],
                'optimize' => false,
                'generate_sizes' => false,
            ],
            ['Accept' => 'application/json']
        );

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt_text', 'Initial alt text')
            ->assertJsonPath('data.collection_name', 'images');

        $media = Media::query()->firstOrFail();

        $this->assertSame('Initial alt text', $media->alt_text);
        $this->assertSame('Initial caption', $media->caption);
        $this->assertSame(['seerah', 'cover'], $media->tags);
        $this->assertSame('local', $media->disk);
        $this->assertNotNull($media->public_key);
        $this->assertNotSame((string) $media->id, $media->public_key);
        Storage::disk('local')->assertExists($media->path);
        $this->assertSame('MLIB1', substr((string) file_get_contents(Storage::disk('local')->path($media->path)), 0, 5));
        $this->assertTrue((bool) data_get($media->metadata, 'storage.encrypted_at_rest'));
        $this->assertSame([], $media->metadata['variants'] ?? []);
        $this->assertStringContainsString("/files/{$media->public_key}", $media->url);
        $this->assertStringNotContainsString("/files/{$media->id}", $media->url);
        $originalPath = $media->path;

        $streamResponse = $this->get(route('admin.media-library.file', ['media' => $media->public_key]));
        $streamResponse->assertOk();
        $this->assertStringStartsWith('image/', (string) $streamResponse->headers->get('content-type'));

        $this->getJson(route('admin.media-library.show', $media->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonPath('data.original_filename', 'cover.png');

        $this->putJson(route('admin.media-library.update', $media->id), [
            'title' => 'Updated title',
            'alt_text' => 'Updated alt text',
            'caption' => 'Updated caption',
            'tags' => ['updated', 'media'],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt_text', 'Updated alt text')
            ->assertJsonPath('data.title', 'Updated title');

        $media->refresh();

        $this->assertSame('Updated alt text', $media->alt_text);
        $this->assertSame('Updated caption', $media->caption);
        $this->assertSame(['updated', 'media'], $media->tags);
        $this->assertSame('Updated title', $media->metadata['display_title']);

        $this->deleteJson(route('admin.media-library.destroy', $media->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('media', ['id' => $media->id]);
        Storage::disk('local')->assertMissing($originalPath);
    }

    public function test_admin_can_bulk_upload_with_generated_variants(): void
    {
        Storage::fake('local');

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->post(
            route('admin.media-library.bulk-upload'),
            [
                'files' => [
                    UploadedFile::fake()->image('first.png', 1400, 900),
                    UploadedFile::fake()->image('second.png', 1200, 800),
                ],
                'collection_name' => 'gallery',
                'tags' => ['bulk', 'images'],
                'optimize' => false,
                'generate_sizes' => true,
            ],
            ['Accept' => 'application/json']
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $mediaItems = Media::query()->orderBy('id')->get();

        $this->assertCount(2, $mediaItems);
        $storedPaths = [];
        $variantPaths = [];

        foreach ($mediaItems as $media) {
            Storage::disk('local')->assertExists($media->path);
            $this->assertSame('MLIB1', substr((string) file_get_contents(Storage::disk('local')->path($media->path)), 0, 5));
            $variants = $media->metadata['variants'] ?? [];
            $this->assertArrayHasKey('thumbnail', $variants);
            $this->assertArrayHasKey('medium', $variants);
            $this->assertArrayHasKey('large', $variants);
            $this->assertTrue((bool) ($variants['thumbnail']['encrypted'] ?? false));
            Storage::disk('local')->assertExists($variants['thumbnail']['path']);
            $this->assertSame('MLIB1', substr((string) file_get_contents(Storage::disk('local')->path($variants['thumbnail']['path'])), 0, 5));
            $this->assertStringContainsString("/files/{$media->public_key}/variants/medium", $media->preview_url);
            $this->assertStringNotContainsString("/files/{$media->id}/variants/medium", $media->preview_url);
            $storedPaths[] = $media->path;
            $variantPaths = array_merge($variantPaths, array_column($variants, 'path'));
        }

        $this->postJson(route('admin.media-library.bulk-delete'), [
            'ids' => $mediaItems->pluck('id')->all(),
        ])->assertOk()
            ->assertJsonPath('success', true);

        foreach ($storedPaths as $path) {
            Storage::disk('local')->assertMissing($path);
        }

        foreach ($variantPaths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
    }

    public function test_crop_preserves_original_and_updates_generated_variants_only(): void
    {
        Storage::fake('local');

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->post(
            route('admin.media-library.store'),
            [
                'file' => UploadedFile::fake()->image('crop-target.png', 1400, 900),
                'collection_name' => 'gallery',
                'optimize' => false,
                'generate_sizes' => true,
            ],
            ['Accept' => 'application/json']
        );

        $response->assertCreated();

        $media = Media::query()->firstOrFail();
        $originalChecksumBeforeCrop = sha1((string) file_get_contents(Storage::disk('local')->path($media->path)));

        $this->postJson(route('admin.media-library.crop', $media->id), [
            'x' => 200,
            'y' => 100,
            'width' => 600,
            'height' => 600,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $media->refresh();

        $this->assertSame(1400, $media->width);
        $this->assertSame(900, $media->height);
        $this->assertSame($originalChecksumBeforeCrop, sha1((string) file_get_contents(Storage::disk('local')->path($media->path))));
        $this->assertSame(600.0, (float) data_get($media->metadata, 'crop.width'));
        $this->assertSame(600.0, (float) data_get($media->metadata, 'crop.height'));

        $variants = $media->metadata['variants'] ?? [];
        $this->assertSame(150, $variants['thumbnail']['width'] ?? null);
        $this->assertSame(150, $variants['thumbnail']['height'] ?? null);
        $this->assertSame(300, $variants['medium']['width'] ?? null);
        $this->assertSame(300, $variants['medium']['height'] ?? null);
        $this->assertSame(600, $variants['large']['width'] ?? null);
        $this->assertSame(600, $variants['large']['height'] ?? null);

        $originalResponse = $this->get(route('admin.media-library.file', ['media' => $media->public_key]));
        $originalResponse->assertOk();
        [$streamedWidth, $streamedHeight] = getimagesize($originalResponse->baseResponse->getFile()->getPathname()) ?: [null, null];
        $this->assertSame(1400, $streamedWidth);
        $this->assertSame(900, $streamedHeight);
    }
}
