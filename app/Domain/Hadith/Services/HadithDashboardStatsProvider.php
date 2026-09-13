<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use Throwable;

final class HadithDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'hadith';
    }

    public function getStats(): array
    {
        try {
            return ['total' => Hadith::query()->count()];
        } catch (Throwable $exception) {
            report($exception);

            return ['total' => 0];
        }
    }
}
