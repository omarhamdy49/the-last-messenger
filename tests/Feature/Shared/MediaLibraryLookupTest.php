<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Domain\Shared\Contracts\MediaLibraryLookup;
use App\Domain\Shared\Contracts\MediaReference;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaLibraryLookupTest extends TestCase
{
    use RefreshDatabase;

    private function createMedia(string $name): Media
    {
        return Media::query()->create([
            'public_key' => (string) Str::ulid(),
            'filename' => $name,
            'original_filename' => $name,
            'path' => "media/{$name}",
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'disk' => 'public',
            'type' => 'image',
        ]);
    }

    public function test_empty_ids_return_nothing_without_querying(): void
    {
        DB::enableQueryLog();

        $this->assertSame([], app(MediaLibraryLookup::class)->byIds([]));
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_resolves_known_ids_keyed_by_id_in_one_query(): void
    {
        $first = $this->createMedia('first.jpg');
        $second = $this->createMedia('second.jpg');

        DB::enableQueryLog();
        $references = app(MediaLibraryLookup::class)->byIds([$first->id, $second->id, 999999]);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame([$first->id, $second->id], array_keys($references));
        $this->assertContainsOnlyInstancesOf(MediaReference::class, $references);
        $this->assertSame('first.jpg', $references[$first->id]->title);
        $this->assertSame(route('public.articles.media', ['media' => $second->public_key]), $references[$second->id]->url);
        $this->assertSame(1, $queryCount);
    }
}
