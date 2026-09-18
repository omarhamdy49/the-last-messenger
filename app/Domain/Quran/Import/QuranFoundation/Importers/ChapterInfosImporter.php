<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Facades\DB;

/**
 * Chapter introductions for every enabled chapter-info resource
 * (`/chapters/{id}/info?resource_id=`). A chapter the source does not cover
 * (`chapter_info: null`) is itemised as a note, not a failure. Locked rows are kept.
 */
final class ChapterInfosImporter
{
    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    public function run(?int $resourceId = null, int $from = 1, int $to = Chapter::COUNT): ImportReport
    {
        $report = new ImportReport('chapter-infos');

        $resources = QuranResource::query()
            ->select(['id', 'qf_id', 'language_iso', 'name'])
            ->ofKind(ResourceKind::CHAPTER_INFO)
            ->whereNotNull('qf_id')
            ->when($resourceId !== null, fn ($q) => $q->whereKey($resourceId), fn ($q) => $q->where('sync_enabled', true))
            ->orderBy('id')
            ->get();

        if ($resources->isEmpty()) {
            $report->note('no chapter-info resource is enabled for sync');

            return $report;
        }

        foreach ($resources as $resource) {
            $rows = [];
            $uncovered = [];
            for ($chapter = $from; $chapter <= $to; $chapter++) {
                $report->expected++;
                try {
                    $info = $this->qf->chapterInfo($chapter, $resource->language_iso ?? 'en', (string) $resource->qf_id)['chapter_info'] ?? null;
                } catch (QfApiException $e) {
                    if ($e->status !== 404) {
                        throw $e;
                    }
                    $info = null;
                }

                if (! is_array($info) || trim((string) ($info['text'] ?? '')) === '') {
                    $uncovered[] = $chapter;

                    continue;
                }

                $rows[] = [
                    'resource_id' => $resource->id,
                    'chapter_id' => $chapter,
                    'qf_id' => isset($info['id']) ? (int) $info['id'] : null,
                    'language_iso' => $resource->language_iso,
                    'text' => (string) $info['text'],
                    'short_text' => trim((string) ($info['short_text'] ?? '')) !== '' ? (string) $info['short_text'] : null,
                    'source' => isset($info['source']) ? mb_substr((string) $info['source'], 0, 255) : null,
                ];
            }

            $report->add($this->repository->upsert('quran_chapter_infos', $rows, ['resource_id', 'chapter_id'],
                ['qf_id', 'language_iso', 'text', 'short_text', 'source'], 'is_locked'));

            DB::table('quran_resources')->where('id', $resource->id)->update([
                'records_count' => DB::table('quran_chapter_infos')->where('resource_id', $resource->id)->count(),
                'last_synced_at' => now(),
            ]);

            if ($uncovered !== []) {
                $report->note("{$resource->name}: no text for chapter(s) ".implode(',', $uncovered));
            }
        }

        QuranCacheKeys::flushContent();

        return $report;
    }
}
