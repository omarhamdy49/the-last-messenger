<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\VerseEditData;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Verse administration (the corpus itself is never created or deleted here).
 */
final class VerseService
{
    public function __construct(
        private readonly VerseRepositoryInterface $verses,
        private readonly ChapterRepositoryInterface $chapters,
    ) {}

    /**
     * @param  array{chapter?: int|null, juz?: int|null, page?: int|null, sajdah?: bool|null, locked?: bool|null, q?: string|null}  $filters
     * @return array{verses: LengthAwarePaginator, chapters: Collection<int, Chapter>}
     */
    public function adminIndex(array $filters, int $perPage): array
    {
        return ['verses' => $this->verses->paginateAdmin($filters, $perPage), 'chapters' => $this->chapters->all(false)];
    }

    public function findForAdmin(int $id): ?Verse
    {
        return $this->verses->findForAdmin($id);
    }

    public function update(int $id, VerseEditData $data): Verse
    {
        $verse = $this->verses->update($id, $data);
        QuranCacheKeys::flushContent();

        return $verse;
    }

    /**
     * @param  array<int, string|null>  $glosses  resource id => text
     */
    public function updateWordGlosses(int $wordId, array $glosses): int
    {
        $changed = $this->verses->updateWordGlosses($wordId, $glosses);
        QuranCacheKeys::flushContent();

        return $changed;
    }

    /**
     * @param  list<int>  $ids
     */
    public function setLocked(array $ids, bool $locked): int
    {
        return $this->verses->setLocked($ids, $locked);
    }
}
