<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\AudioSegments;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\ChapterRecitationsHandler;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Facades\DB;

/**
 * Per-verse timestamps (+ word segments) for QF chapter reciters, from
 * `/chapter_recitations/{id}/{chapter}?segments=true`. The same response carries the
 * chapter file, so the file row is upserted too — this works for every QF chapter
 * reciter, including the ones sync does not share.
 */
final class ChapterAudioTimestampsImporter
{
    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    public function run(?int $resourceId = null, int $from = 1, int $to = Chapter::COUNT): ImportReport
    {
        $report = new ImportReport('timestamps');

        $reciters = QuranResource::query()
            ->select(['id', 'qf_id', 'name'])
            ->ofKind(ResourceKind::CHAPTER_RECITER)
            ->whereNotNull('qf_id')
            ->when($resourceId !== null, fn ($q) => $q->whereKey($resourceId), fn ($q) => $q->where(function ($q): void {
                $q->where('sync_enabled', true)->orWhere('is_active', true);
            }))
            ->orderBy('id')
            ->get();

        if ($reciters->isEmpty()) {
            $report->note('no QF chapter reciter selected');

            return $report;
        }

        $verseIds = DB::table('quran_verses')->pluck('id', 'verse_key')->map(static fn (mixed $v): int => (int) $v)->all();
        $versesCount = DB::table('quran_chapters')->pluck('verses_count', 'id')->map(static fn (mixed $v): int => (int) $v)->all();

        foreach ($reciters as $reciter) {
            $mismatches = [];
            for ($chapter = $from; $chapter <= $to; $chapter++) {
                try {
                    $file = $this->qf->chapterRecitation((int) $reciter->qf_id, $chapter, true)['audio_file'] ?? null;
                } catch (QfApiException $e) {
                    if ($e->status !== 404) {
                        throw $e;
                    }
                    $report->fail("reciter:{$reciter->id}:chapter:{$chapter}", 'no chapter file at QF');

                    continue;
                }

                if (! is_array($file)) {
                    $report->fail("reciter:{$reciter->id}:chapter:{$chapter}", 'unexpected payload');

                    continue;
                }

                $fileRow = ChapterRecitationsHandler::chapterFileRow($reciter->id, $file + ['chapter_id' => $chapter], $report);
                if ($fileRow === null) {
                    continue;
                }
                $this->repository->upsert('quran_chapter_audio_files', [$fileRow], ['resource_id', 'chapter_id'], ['qf_id', 'url', 'file_size', 'format']);
                $fileId = (int) DB::table('quran_chapter_audio_files')->where('resource_id', $reciter->id)->where('chapter_id', $chapter)->value('id');

                $rows = [];
                foreach ((array) ($file['timestamps'] ?? []) as $stamp) {
                    $verseId = $verseIds[(string) ($stamp['verse_key'] ?? '')] ?? null;
                    if ($verseId === null) {
                        $report->fail("reciter:{$reciter->id}:".($stamp['verse_key'] ?? '?'), 'verse not imported');

                        continue;
                    }
                    $segments = AudioSegments::normalize($stamp['segments'] ?? null);
                    $rows[] = [
                        'chapter_audio_file_id' => $fileId,
                        'verse_id' => $verseId,
                        'timestamp_from' => max(0, (int) ($stamp['timestamp_from'] ?? 0)),
                        'timestamp_to' => max(0, (int) ($stamp['timestamp_to'] ?? 0)),
                        'duration' => isset($stamp['duration']) ? (int) $stamp['duration'] : null,
                        'segments' => $segments === null ? null : json_encode($segments),
                    ];
                }

                $report->expected += $versesCount[$chapter] ?? 0;
                $report->add($this->repository->replaceForOwner('quran_chapter_audio_timestamps', 'chapter_audio_file_id', $fileId, $rows,
                    ['chapter_audio_file_id', 'verse_id'], ['timestamp_from', 'timestamp_to', 'duration', 'segments']));

                DB::table('quran_chapter_audio_files')->where('id', $fileId)->update(['has_timestamps' => $rows !== []]);

                if (count($rows) !== ($versesCount[$chapter] ?? -1)) {
                    $mismatches[] = "{$chapter} (".count($rows).'/'.($versesCount[$chapter] ?? '?').')';
                }
            }

            $report->gate($mismatches === [], $mismatches === []
                ? "{$reciter->name}: a timestamp for every verse"
                : "{$reciter->name}: timestamp count mismatch in chapter(s) ".implode(', ', $mismatches));

            DB::table('quran_resources')->where('id', $reciter->id)->update([
                'records_count' => DB::table('quran_chapter_audio_files')->where('resource_id', $reciter->id)->count(),
                'last_synced_at' => now(),
            ]);
        }

        QuranCacheKeys::flushReciters();

        return $report;
    }
}
