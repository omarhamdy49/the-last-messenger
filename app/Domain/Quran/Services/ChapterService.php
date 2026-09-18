<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ChapterData;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ChapterService
{
    public function __construct(private readonly ChapterRepositoryInterface $chapters) {}

    /**
     * @return Collection<int, Chapter>
     */
    public function all(bool $publishedOnly = true): Collection
    {
        return $this->chapters->all($publishedOnly);
    }

    public function find(int $id): ?Chapter
    {
        return $this->chapters->find($id);
    }

    /**
     * @param  array{search?: string|null, revelation_place?: string|null, published?: string|null}  $filters
     * @return array{chapters: LengthAwarePaginator, stats: array{total: int, makkah: int, madinah: int, verses: int, published: int}}
     */
    public function adminIndex(array $filters, int $perPage): array
    {
        return ['chapters' => $this->chapters->paginateAdmin($filters, $perPage), 'stats' => $this->chapters->stats()];
    }

    public function findForAdmin(int $id): ?Chapter
    {
        return $this->chapters->findForAdmin($id);
    }

    public function update(int $id, ChapterData $data): Chapter
    {
        $chapter = $this->chapters->update($id, $data);
        QuranCacheKeys::flushContent();

        return $chapter;
    }

    /**
     * @return Collection<int, Chapter>
     */
    public function search(string $term, int $limit = 20): Collection
    {
        return $this->chapters->search($term, $limit);
    }
}
