<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class SahabaDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'sahaba';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => Sahabi::onlySahaba()->count(),
                'views' => (int) Sahabi::onlySahaba()->sum('views_count'),
            ];
        } catch (\Throwable) {
            return ['total' => 0, 'views' => 0];
        }
    }
}
