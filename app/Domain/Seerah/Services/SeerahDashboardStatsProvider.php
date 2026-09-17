<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class SeerahDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'seerah_events';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => SeerahEvent::count(),
                'published' => SeerahEvent::query()->published()->count(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['total' => 0, 'published' => 0];
        }
    }
}
