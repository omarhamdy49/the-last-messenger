<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use App\Models\Article;

class BlogDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'articles';
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => Article::count(),
                'views' => (int) Article::sum('views'),
                'published' => Article::where('status', 'published')->count(),
                'draft' => Article::where('status', 'draft')->count(),
                'archived' => Article::where('status', 'archived')->count(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0, 'views' => 0];
        }
    }
}
