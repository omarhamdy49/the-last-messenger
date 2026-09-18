<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\MushafPage;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class EloquentMushafRepository implements MushafRepositoryInterface
{
    private const COLUMNS = [
        'id', 'name', 'description', 'pages_count', 'lines_per_page', 'default_font_name', 'mapping_mode', 'qirat_name',
        'font_version', 'is_default', 'is_active', 'fonts_downloaded_at', 'records_count', 'last_synced_at',
    ];

    public function all(): Collection
    {
        return Mushaf::query()->select(self::COLUMNS)->orderByDesc('is_default')->orderBy('id')->get();
    }

    public function active(): Collection
    {
        return Mushaf::query()->select(self::COLUMNS)->active()->orderByDesc('is_default')->orderBy('id')->get();
    }

    public function default(): ?Mushaf
    {
        return Mushaf::query()->select(self::COLUMNS)->where('is_default', true)->first()
            ?? Mushaf::query()->select(self::COLUMNS)->find((int) config('quran.mushaf.default_id', 1));
    }

    public function find(int $id): ?Mushaf
    {
        return Mushaf::query()->select(self::COLUMNS)->find($id);
    }

    public function page(int $mushafId, int $pageNumber): ?MushafPage
    {
        return MushafPage::query()
            ->select(['id', 'mushaf_id', 'page_number', 'first_verse_id', 'last_verse_id', 'first_word_id', 'last_word_id', 'verses_count', 'verse_mapping'])
            ->where('mushaf_id', $mushafId)
            ->where('page_number', $pageNumber)
            ->first();
    }

    public function pageOfVerse(int $mushafId, int $verseId): ?int
    {
        $page = DB::table('quran_mushaf_pages')
            ->where('mushaf_id', $mushafId)
            ->where('first_verse_id', '<=', $verseId)
            ->where('last_verse_id', '>=', $verseId)
            ->value('page_number');

        return $page === null ? null : (int) $page;
    }

    public function chapterLines(int $mushafId, int $chapterId): array
    {
        return Cache::remember(QuranCacheKeys::chapterLines($mushafId, $chapterId), 86400, function () use ($mushafId, $chapterId): array {
            $rows = DB::table('quran_mushaf_words as mw')
                ->join('quran_words as w', 'w.id', '=', 'mw.word_id')
                ->join('quran_verses as v', 'v.id', '=', 'mw.verse_id')
                ->select(['mw.page_number', 'mw.line_number', 'w.text_uthmani as text', 'mw.char_type', 'v.verse_number', 'w.location', 'w.code_v2', 'w.v2_page'])
                ->where('mw.mushaf_id', $mushafId)
                ->where('v.chapter_id', $chapterId)
                // Reading order comes from `position_in_page`, never `position_in_line`.
                // Quran Foundation's per-line index contradicts the page order on 8,191 of
                // the 8,820 lines (the opening word of 11:1 carries 9), which reordered words
                // inside a line and read as "missing words". `position_in_page` is complete,
                // and every line's values form one contiguous block, so it orders both the
                // lines and the words within them.
                ->orderBy('mw.page_number')->orderBy('mw.position_in_page')
                ->get();

            if ($rows->isEmpty() && $mushafId === (int) config('quran.mushaf.default_id', 1)) {
                $rows = DB::table('quran_words as w')
                    ->join('quran_verses as v', 'v.id', '=', 'w.verse_id')
                    ->select(['w.page_number', 'w.line_number', 'w.text_uthmani as text', 'w.char_type', 'v.verse_number', 'w.location', 'w.code_v2', 'w.v2_page'])
                    ->where('v.chapter_id', $chapterId)
                    ->orderBy('w.page_number')->orderBy('w.line_number')->orderBy('w.verse_id')->orderBy('w.position')
                    ->get();
            }

            $pages = [];
            foreach ($rows as $row) {
                $page = (int) $row->page_number;
                $line = (int) $row->line_number;
                $pages[$page]['page'] = $page;
                $pages[$page]['lines'][$line]['line'] = $line;
                $pages[$page]['lines'][$line]['words'][] = [
                    'text' => (string) $row->text,
                    'type' => (string) $row->char_type,
                    'verse' => (int) $row->verse_number,
                    'location' => (string) $row->location,
                    // QCF V2 glyph + the page font it belongs to (glyph reading mode).
                    'code' => $row->code_v2,
                    'glyphPage' => $row->v2_page === null ? null : (int) $row->v2_page,
                ];
            }

            return array_values(array_map(static function (array $page): array {
                $page['lines'] = array_values($page['lines']);

                return $page;
            }, $pages));
        });
    }

    public function pageLines(int $mushafId, int $pageNumber): array
    {
        return Cache::remember(QuranCacheKeys::pageLines($mushafId, $pageNumber), 86400, function () use ($mushafId, $pageNumber): array {
            $rows = DB::table('quran_mushaf_words as mw')
                ->join('quran_words as w', 'w.id', '=', 'mw.word_id')
                ->join('quran_verses as v', 'v.id', '=', 'mw.verse_id')
                ->select([
                    'mw.word_id', 'mw.verse_id', 'v.verse_key', 'mw.position_in_verse as position', 'mw.text', 'mw.char_type',
                    'mw.line_number', 'w.code_v1', 'w.code_v2', 'w.text_qpc_hafs',
                ])
                ->where('mw.mushaf_id', $mushafId)
                ->where('mw.page_number', $pageNumber)
                // Reading order comes from `position_in_page`, never `position_in_line`.
                // Quran Foundation's per-line index contradicts the page order on 8,191 of
                // the 8,820 lines (the opening word of 11:1 carries 9), which reordered words
                // inside a line and read as "missing words". `position_in_page` is complete,
                // and every line's values form one contiguous block, so it orders both the
                // lines and the words within them.
                ->orderBy('mw.position_in_page')
                ->get();

            if ($rows->isEmpty() && $mushafId === (int) config('quran.mushaf.default_id', 1)) {
                // Layout not synced yet: the default-mushaf positions stored on words.
                $rows = DB::table('quran_words as w')
                    ->join('quran_verses as v', 'v.id', '=', 'w.verse_id')
                    ->select([
                        'w.id as word_id', 'w.verse_id', 'v.verse_key', 'w.position', 'w.code_v2 as text', 'w.char_type',
                        'w.line_number', 'w.code_v1', 'w.code_v2', 'w.text_qpc_hafs',
                    ])
                    ->where('w.page_number', $pageNumber)
                    ->orderBy('w.line_number')
                    ->orderBy('w.verse_id')
                    ->orderBy('w.position')
                    ->get();
            }

            $lines = [];
            foreach ($rows as $row) {
                $lines[(int) $row->line_number][] = [
                    'word_id' => (int) $row->word_id,
                    'verse_id' => (int) $row->verse_id,
                    'verse_key' => (string) $row->verse_key,
                    'position' => (int) $row->position,
                    'text' => $row->text,
                    'char_type' => (string) $row->char_type,
                    'code_v1' => $row->code_v1,
                    'code_v2' => $row->code_v2,
                    'text_qpc_hafs' => $row->text_qpc_hafs,
                ];
            }
            ksort($lines);

            return $lines;
        });
    }

    public function update(int $id, array $attributes): Mushaf
    {
        return DB::transaction(function () use ($id, $attributes): Mushaf {
            $mushaf = Mushaf::query()->findOrFail($id);

            if (($attributes['is_default'] ?? false) === true) {
                Mushaf::query()->whereKeyNot($id)->update(['is_default' => false]);
                $attributes['is_active'] = true;
            }

            $mushaf->update($attributes);

            return $mushaf;
        });
    }
}
