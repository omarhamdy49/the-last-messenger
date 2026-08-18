<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class QuranDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'quran_verses';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => Verse::count(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['total' => 0];
        }
    }
}
