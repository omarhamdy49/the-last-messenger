<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Section;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class EloquentSectionRepository implements SectionRepositoryInterface
{
    private const COLUMNS = ['id', 'type', 'number', 'surah_ruku_number', 'first_verse_id', 'last_verse_id', 'verses_count', 'verse_mapping'];

    public function ofType(SectionType $type): Collection
    {
        return Cache::remember(QuranCacheKeys::sections($type), 86400, static fn (): Collection => Section::query()
            ->select(self::COLUMNS)
            ->ofType($type)
            ->orderBy('number')
            ->get());
    }

    public function find(SectionType $type, int $number): ?Section
    {
        return $this->ofType($type)->firstWhere('number', $number);
    }

    public function counts(): array
    {
        return DB::table('quran_sections')->selectRaw('type, COUNT(*) as n')->groupBy('type')->pluck('n', 'type')
            ->map(static fn (mixed $v): int => (int) $v)->all();
    }
}
