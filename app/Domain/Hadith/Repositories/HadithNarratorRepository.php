<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\NarratorGrade;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Hadith Narrator Repository Implementation
 * Handles all database operations for hadith narrators with caching
 */
class HadithNarratorRepository extends BaseRepository implements HadithNarratorRepositoryInterface
{
    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new HadithNarrator;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Apply filters
        if (!empty($filters['grade'])) {
            $query->where('reliability_grade', $filters['grade']);
        }

        if (!empty($filters['era'])) {
            $query->byEra($filters['era']);
        }

        if (!empty($filters['country'])) {
            $query->byCountry($filters['country']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Load hadith count
        $query->withCount('hadiths');

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): ?HadithNarrator
    {
        $cacheKey = $this->getCacheKey('narrator', ['id' => $id]);

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->withCount('hadiths')->find($id)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model->search($query)
            ->active()
            ->withCount('hadiths')
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByGrade(NarratorGrade $grade): Collection
    {
        $cacheKey = $this->getCacheKey('byGrade', ['grade' => $grade->value]);

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->byGrade($grade)
                ->active()
                ->withCount('hadiths')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByEra(string $era): Collection
    {
        $cacheKey = $this->getCacheKey('byEra', ['era' => $era]);

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->byEra($era)
                ->active()
                ->withCount('hadiths')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByCountry(string $country): Collection
    {
        $cacheKey = $this->getCacheKey('byCountry', ['country' => $country]);

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->byCountry($country)
                ->active()
                ->withCount('hadiths')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTeachers(int $narratorId): Collection
    {
        $narrator = $this->getById($narratorId);

        if (!$narrator || empty($narrator->teachers)) {
            return collect([]);
        }

        $teacherIds = is_array($narrator->teachers) ? $narrator->teachers : json_decode($narrator->teachers, true);

        if (empty($teacherIds)) {
            return collect([]);
        }

        return $this->model->whereIn('id', $teacherIds)
            ->withCount('hadiths')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getStudents(int $narratorId): Collection
    {
        $narrator = $this->getById($narratorId);

        if (!$narrator || empty($narrator->students)) {
            return collect([]);
        }

        $studentIds = is_array($narrator->students) ? $narrator->students : json_decode($narrator->students, true);

        if (empty($studentIds)) {
            return collect([]);
        }

        return $this->model->whereIn('id', $studentIds)
            ->withCount('hadiths')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getHadithCount(int $narratorId): int
    {
        $cacheKey = $this->getCacheKey('hadithCount', ['id' => $narratorId]);

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->find($narratorId)?->hadiths()->count() ?? 0
        );
    }

    /**
     * {@inheritdoc}
     */
    public function countByGrade(): array
    {
        $cacheKey = $this->getCacheKey('countByGrade');

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            function () {
                $counts = [];
                foreach (NarratorGrade::cases() as $grade) {
                    $counts[$grade->value] = $this->model->byGrade($grade)->count();
                }
                return $counts;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function countByEra(): array
    {
        $cacheKey = $this->getCacheKey('countByEra');

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            function () {
                $eras = [
                    'Sahabah',
                    'Tabiun',
                    'Taba_al_Tabiun',
                    'Taba_Taba_al_Tabiun',
                    'Later_Scholars',
                ];

                $counts = [];
                foreach ($eras as $era) {
                    $counts[$era] = $this->model->byEra($era)->count();
                }
                return $counts;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): HadithNarrator
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']['ar'] ?? $data['name']['en']);
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }

        $narrator = $this->model->create($data);

        $this->clearCache();

        return $narrator;
    }

    /**
     * {@inheritdoc}
     */
    public function update(HadithNarrator $narrator, array $data): HadithNarrator
    {
        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $narrator->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name']['ar'] ?? $data['name']['en'], $narrator->id);
        }

        $narrator->update($data);
        $narrator->refresh();

        $this->clearCache();

        return $narrator;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(HadithNarrator $narrator): bool
    {
        $result = $narrator->delete();

        $this->clearCache();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->model->whereIn('id', $ids)->delete();

        $this->clearCache();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive(): Collection
    {
        $cacheKey = $this->getCacheKey('active');

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->active()
                ->withCount('hadiths')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTrustworthy(): Collection
    {
        $cacheKey = $this->getCacheKey('trustworthy');

        return Cache::tags(['hadith_narrators'])->remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->trustworthy()
                ->active()
                ->withCount('hadiths')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        Cache::tags(['hadith_narrators'])->flush();
    }

    /**
     * Generate unique slug from name
     */
    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
