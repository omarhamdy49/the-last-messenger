<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ThemeRepository extends BaseRepository implements ThemeRepositoryInterface
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {
        parent::__construct();
    }

    protected function getModel(): Model
    {
        return new HadithTheme;
    }

    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithTheme
    {
        return Cache::remember("hadith_theme_slug_{$slug}", self::CACHE_TTL, fn () => $this->model
            ->where('slug', $slug)
            ->first());
    }

    public function getAllWithHadithCount(): Collection
    {
        return $this->model
            ->select(['id', 'slug', 'name'])
            ->withCount('hadiths')
            ->orderByDesc('hadiths_count')
            ->get();
    }

    public function getWithHadiths(int $id): ?HadithTheme
    {
        return $this->model
            ->with(['hadiths' => fn (Relation $relation) => $this->publicVisibility
                ->apply($relation->getQuery(), app()->getLocale())
                ->with(['translations', 'gradings', 'chapter.book.collection'])])
            ->find($id);
    }

    public function paginateHadithsByIdentifier(string $identifier, int $perPage = 20): ?LengthAwarePaginator
    {
        $theme = $this->model
            ->select(['id'])
            ->when(
                ctype_digit($identifier),
                fn ($query) => $query->whereKey((int) $identifier),
                fn ($query) => $query
                    ->where('slug', $identifier)
                    ->orWhere('name->ar', $identifier)
                    ->orWhere('name->en', $identifier),
            )
            ->first();

        if ($theme === null) {
            return null;
        }

        $query = $theme->hadiths();
        $this->publicVisibility->apply($query->getQuery(), app()->getLocale());

        return $query
            ->with(['translations', 'gradings', 'themes', 'chapter.book.collection'])
            ->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number')
            ->paginate($perPage, [
                'hadith_hadiths.id',
                'hadith_hadiths.chapter_id',
                'hadith_hadiths.primary_narrator_id',
                'hadith_hadiths.hadith_number',
                'hadith_hadiths.text_hash',
                'hadith_hadiths.created_at',
                'hadith_hadiths.updated_at',
            ]);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('name->ar', 'like', "%{$query}%")
            ->orWhere('name->en', 'like', "%{$query}%")
            ->paginate($perPage);
    }

    public function getTopByHadithCount(int $limit = 10): Collection
    {
        return $this->model
            ->select(['id', 'slug', 'name'])
            ->withCount('hadiths')
            ->orderByDesc('hadiths_count')
            ->limit($limit)
            ->get();
    }

    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator {
        return $this->model
            ->when($search !== '', fn ($query) => $query
                ->where('name->ar', 'like', "%{$search}%")
                ->orWhere('name->en', 'like', "%{$search}%"))
            ->withCount('hadiths')
            ->orderBy(in_array($sortBy, ['id', 'slug', 'review_status'], true) ? $sortBy : 'id', $filters['sort_direction'] ?? 'asc')
            ->paginate($perPage);
    }
}
