<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\ResourceKind;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Flat rows for admin exports. Translations and tafsirs always export one resource
 * (the `resource_id` filter), never every resource at once.
 */
final class EloquentQuranExportRepository implements QuranExportRepositoryInterface
{
    public function rows(string $module, array $filters): Collection
    {
        $chapter = isset($filters['chapter']) && $filters['chapter'] !== '' ? (int) $filters['chapter'] : null;

        return match ($module) {
            'quran_chapters' => DB::table('quran_chapters as c')
                ->leftJoin('quran_chapter_translations as t', fn ($j) => $j->on('t.chapter_id', '=', 'c.id')->where('t.locale', (string) ($filters['locale'] ?? 'en')))
                ->select(['c.id', 'c.name_arabic', 'c.name_simple', 't.name as name_localized', 'c.revelation_place', 'c.revelation_order',
                    'c.verses_count', 'c.first_page', 'c.last_page', 'c.is_published'])
                ->orderBy('c.id')
                ->get(),

            'quran_verses' => DB::table('quran_verses')
                ->select(['id', 'verse_key', 'chapter_id', 'verse_number', 'juz_number', 'hizb_number', 'page_number',
                    'text_uthmani', 'text_imlaei_simple', 'sajdah_type', 'is_locked'])
                ->when($chapter !== null, fn (Builder $q) => $q->where('chapter_id', $chapter))
                ->orderBy('id')
                ->get(),

            'quran_translations', 'quran_tafsirs' => $this->contentRows($module === 'quran_translations' ? 'quran_translations' : 'quran_tafsirs', $filters, $chapter),

            'quran_reciters' => DB::table('quran_resources')
                ->select(['id', 'kind', 'name', 'style', 'qirat', 'language_iso', 'records_count', 'is_active', 'external_source', 'external_ref_id'])
                ->whereIn('kind', [ResourceKind::RECITATION->value, ResourceKind::CHAPTER_RECITER->value])
                ->orderBy('kind')
                ->orderBy('display_order')
                ->get(),

            default => throw new InvalidArgumentException("Unknown Quran export module {$module}"),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function contentRows(string $table, array $filters, ?int $chapter): Collection
    {
        $resourceId = isset($filters['resource_id']) ? (int) $filters['resource_id'] : 0;
        if ($resourceId <= 0) {
            throw new InvalidArgumentException('Choose a resource to export (resource_id filter).');
        }

        return DB::table($table.' as r')
            ->join('quran_verses as v', 'v.id', '=', 'r.verse_id')
            ->join('quran_resources as res', 'res.id', '=', 'r.resource_id')
            ->select(['v.verse_key', 'res.name as resource', 'r.text', 'r.is_locked'])
            ->where('r.resource_id', $resourceId)
            ->when($chapter !== null, fn (Builder $q) => $q->where('v.chapter_id', $chapter))
            ->orderBy('r.verse_id')
            ->get();
    }
}
