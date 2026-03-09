<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface DashboardStatsProviderInterface
{
    /**
     * Returns the module key (e.g., 'hadith', 'quran_verses', 'articles')
     */
    public function getModuleKey(): string;

    /**
     * Returns statistics array for this module's dashboard card.
     * Must not throw exceptions — return empty/zero values on failure.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;
}
