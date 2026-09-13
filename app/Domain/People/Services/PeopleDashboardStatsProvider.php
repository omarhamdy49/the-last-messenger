<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use Throwable;

final class PeopleDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'sahaba';
    }

    public function getStats(): array
    {
        try {
            return ['total' => Muslim::query()->withRole('sahabi')->count()];
        } catch (Throwable $exception) {
            report($exception);

            return ['total' => 0];
        }
    }
}
