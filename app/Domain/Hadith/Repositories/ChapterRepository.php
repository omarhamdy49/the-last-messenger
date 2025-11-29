<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Chapter Repository Implementation
 *
 * Data access layer for Hadith Chapter entities.
 */
class ChapterRepository extends BaseRepository implements ChapterRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new HadithChapter();
    }

    /**
     * {@inheritdoc}
     */
    public function getByBook(int $bookId): Collection
    {
        return Cache::remember(
            "hadith_chapters_book_{$bookId}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('book_id', $bookId)
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithHadiths(int $id): ?HadithChapter
    {
        return $this->model
            ->with(['hadiths' => fn ($q) => $q->orderBy('hadith_number'), 'hadiths.translations'])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getByBookAndNumber(int $bookId, int $chapterNumber): ?HadithChapter
    {
        return $this->model
            ->where('book_id', $bookId)
            ->where('chapter_number', $chapterNumber)
            ->first();
    }
}
