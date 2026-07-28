<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class MediaDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'media';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => MediaProgram::count(),
                'views' => (int) MediaProgram::sum('views_count'),
            ];
        } catch (\Throwable) {
            return ['total' => 0, 'views' => 0];
        }
    }
}
