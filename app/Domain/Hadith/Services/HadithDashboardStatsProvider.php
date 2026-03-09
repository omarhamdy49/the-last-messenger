<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class HadithDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'hadith';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => Hadith::count(),
            ];
        } catch (\Throwable) {
            return ['total' => 0];
        }
    }
}
