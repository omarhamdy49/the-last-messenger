<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ImportRepositoryInterface $repository;

    private QuranResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ImportRepositoryInterface::class);
        Chapter::factory()->number(1)->withVerses(3)->create();
        $this->resource = QuranResource::factory()->translation()->qf(20)->create();
    }

    public function test_upsert_counts_new_changed_and_unchanged_rows(): void
    {
        $rows = $this->rows(['one', 'two', 'three']);

        $first = $this->repository->upsert('quran_translations', $rows, ['resource_id', 'verse_id'], ['text', 'foot_notes']);
        $this->assertSame(['written' => 3, 'updated' => 0, 'unchanged' => 0, 'skipped_locked' => 0], $first);

        $again = $this->repository->upsert('quran_translations', $rows, ['resource_id', 'verse_id'], ['text', 'foot_notes']);
        $this->assertSame(['written' => 0, 'updated' => 0, 'unchanged' => 3, 'skipped_locked' => 0], $again);

        $changed = $this->repository->upsert('quran_translations', $this->rows(['one', 'TWO', 'three']), ['resource_id', 'verse_id'], ['text', 'foot_notes']);
        $this->assertSame(['written' => 0, 'updated' => 1, 'unchanged' => 2, 'skipped_locked' => 0], $changed);
        $this->assertSame('TWO', Translation::query()->where('verse_id', 2)->value('text'));
    }

    public function test_json_columns_are_compared_structurally(): void
    {
        $rows = $this->rows(['one', 'two', 'three'], ['12' => 'note']);
        $this->repository->upsert('quran_translations', $rows, ['resource_id', 'verse_id'], ['text', 'foot_notes']);

        $again = $this->repository->upsert('quran_translations', $rows, ['resource_id', 'verse_id'], ['text', 'foot_notes']);

        $this->assertSame(3, $again['unchanged']);
    }

    public function test_locked_rows_are_skipped_and_counted(): void
    {
        $this->repository->upsert('quran_translations', $this->rows(['one', 'two', 'three']), ['resource_id', 'verse_id'], ['text']);
        Translation::query()->where('verse_id', 2)->update(['text' => 'edited by editor', 'is_locked' => true]);

        $counts = $this->repository->upsert('quran_translations', $this->rows(['one', 'from qf', 'three']), ['resource_id', 'verse_id'], ['text'], 'is_locked');

        $this->assertSame(1, $counts['skipped_locked']);
        $this->assertSame('edited by editor', Translation::query()->where('verse_id', 2)->value('text'));
    }

    public function test_replace_for_owner_deletes_stale_unlocked_rows_only(): void
    {
        $this->repository->upsert('quran_translations', $this->rows(['one', 'two', 'three']), ['resource_id', 'verse_id'], ['text']);
        Translation::query()->where('verse_id', 3)->update(['is_locked' => true]);

        $snapshot = array_slice($this->rows(['one-new', 'two', 'three']), 0, 1); // snapshot now only has verse 1

        $counts = $this->repository->replaceForOwner('quran_translations', 'resource_id', $this->resource->id, $snapshot,
            ['resource_id', 'verse_id'], ['text'], 'is_locked');

        $this->assertSame(1, $counts['deleted']);   // verse 2 removed
        $this->assertSame(1, $counts['updated']);   // verse 1 changed
        $this->assertSame([1, 3], Translation::query()->orderBy('verse_id')->pluck('verse_id')->all()); // locked verse 3 kept
    }

    /**
     * @param  list<string>  $texts
     * @param  array<string, string>|null  $footNotes
     * @return list<array<string, mixed>>
     */
    private function rows(array $texts, ?array $footNotes = null): array
    {
        $verseIds = Verse::query()->orderBy('id')->pluck('id')->all();

        return array_map(fn (string $text, int $verseId): array => [
            'resource_id' => $this->resource->id,
            'verse_id' => $verseId,
            'text' => $text,
            'foot_notes' => $footNotes === null ? null : json_encode($footNotes),
        ], $texts, $verseIds);
    }
}
