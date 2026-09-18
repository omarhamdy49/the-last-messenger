<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use Throwable;

/**
 * Dashboard tile for the Quran module: corpus size, active resources per kind and
 * when Quran Foundation content was last synced.
 *
 * Three queries, none of which scan a content table: the corpus size comes from the
 * 114 chapter rows that already carry `verses_count`, the resource counts are one
 * grouped count, and the sync time is one indexed max.
 */
class QuranDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function __construct(
        private readonly ChapterRepositoryInterface $chapters,
        private readonly ResourceRepositoryInterface $resources,
        private readonly SyncRepositoryInterface $sync,
    ) {}

    public function getModuleKey(): string
    {
        return 'quran_verses';
    }

    /**
     * @return array<string, int|string|null>
     */
    public function getStats(): array
    {
        try {
            $chapters = $this->chapters->stats();
            $active = $this->resources->activeCounts();

            return [
                'total' => $chapters['verses'],
                'chapters' => $chapters['total'],
                'active_translations' => $active['translation'] ?? 0,
                'active_tafsirs' => $active['tafsir'] ?? 0,
                'active_reciters' => ($active['recitation'] ?? 0) + ($active['chapter_reciter'] ?? 0),
                'last_synced_at' => $this->sync->lastSucceededAt(),
            ];
        } catch (Throwable $e) {
            // Reported, not swallowed: a zeroed dashboard used to be the only
            // symptom of a renamed table, which hid the breakage.
            report($e);

            return ['total' => 0];
        }
    }
}
