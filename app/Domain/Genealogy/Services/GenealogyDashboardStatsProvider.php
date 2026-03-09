<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Services;

use App\Domain\Genealogy\Models\Person;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;

class GenealogyDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'genealogy';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => Person::count(),
            ];
        } catch (\Throwable) {
            return ['total' => 0];
        }
    }
}
