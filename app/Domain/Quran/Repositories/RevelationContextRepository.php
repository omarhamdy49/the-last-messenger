<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Revelation Context (Asbab al-Nuzul) Repository
 */
class RevelationContextRepository implements RevelationContextRepositoryInterface
{
    /**
     * Columns selected for list/detail queries.
     *
     * @var array<string>
     */
    private const CONTEXT_COLUMNS = [
        'id',
        'surah_id',
        'verse_from',
        'verse_to',
        'period',
        'event_key',
        'display_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Eager loads with explicit columns (FK surah_id is in the parent select).
     *
     * @var array<string, mixed>
     */
    private const CONTEXT_RELATIONS = [
        'surah:id,revelation_order,name_ar,name_en,revelation_place',
        'translations:id,revelation_context_id,locale,title,narrative,source_reference,is_ready',
    ];

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RevelationContext::query()
            ->select(self::CONTEXT_COLUMNS)
            ->with(self::CONTEXT_RELATIONS);

        if ($filters['active_only'] ?? false) {
            $query->active();
        }

        if (! empty($filters['period']) && in_array($filters['period'], RevelationContext::periods(), true)) {
            $query->byPeriod($filters['period']);
        }

        if (! empty($filters['surah_id'])) {
            $query->where('surah_id', (int) $filters['surah_id']);
        }

        if (! empty($filters['search'])) {
            $this->applySearch($query, (string) $filters['search']);
        }

        // 'chronological' orders by the curated timeline position (display_order);
        // 'mushaf' (default) follows surah order then verse position.
        if (($filters['sort'] ?? 'mushaf') === 'chronological') {
            $query->orderBy('display_order')->orderBy('surah_id')->orderBy('verse_from');
        } else {
            $query->orderBy('surah_id')->orderBy('verse_from');
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): ?RevelationContext
    {
        return RevelationContext::query()
            ->select(self::CONTEXT_COLUMNS)
            ->with(self::CONTEXT_RELATIONS)
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function createWithTranslations(array $attributes, array $translations): RevelationContext
    {
        return DB::transaction(function () use ($attributes, $translations): RevelationContext {
            /** @var RevelationContext $context */
            $context = RevelationContext::query()->create($attributes);
            $context->syncTranslations($translations);

            return $context->load(self::CONTEXT_RELATIONS);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateWithTranslations(RevelationContext $context, array $attributes, array $translations): bool
    {
        return DB::transaction(function () use ($context, $attributes, $translations): bool {
            $updated = $context->update($attributes);
            $context->syncTranslations($translations);

            return $updated;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteContext(RevelationContext $context): bool
    {
        return (bool) $context->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getArabicExcerptsForContexts(array $verseRefs): array
    {
        if ($verseRefs === []) {
            return [];
        }

        $query = VerseTranslation::query()
            ->select([
                'quran_verses.surah_id',
                'quran_verses.verse_number',
                'quran_verse_translations.text',
            ])
            ->join('quran_verses', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->where('quran_verse_translations.lang', 'ar')
            ->where(function (Builder $outer) use ($verseRefs): void {
                foreach ($verseRefs as $ref) {
                    $outer->orWhere(function (Builder $inner) use ($ref): void {
                        $inner->where('quran_verses.surah_id', $ref['surah_id'])
                            ->where('quran_verses.verse_number', $ref['verse_number']);
                    });
                }
            });

        return $query->get()
            ->mapWithKeys(fn ($row) => [$row->surah_id.':'.$row->verse_number => (string) $row->text])
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        $row = RevelationContext::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN period = 'makki' THEN 1 ELSE 0 END) as makki")
            ->selectRaw("SUM(CASE WHEN period = 'madani' THEN 1 ELSE 0 END) as madani")
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'makki' => (int) ($row->makki ?? 0),
            'madani' => (int) ($row->madani ?? 0),
            'active' => (int) ($row->active ?? 0),
        ];
    }

    /**
     * Search translated title/narrative plus the surah name.
     */
    private function applySearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term): void {
            $q->whereHas('translations', function (Builder $t) use ($term): void {
                $t->where('title', 'LIKE', "%{$term}%")
                    ->orWhere('narrative', 'LIKE', "%{$term}%");
            })->orWhereHas('surah', function (Builder $s) use ($term): void {
                $s->where('name_ar', 'LIKE', "%{$term}%")
                    ->orWhere('name_en', 'LIKE', "%{$term}%");
            });
        });
    }
}
