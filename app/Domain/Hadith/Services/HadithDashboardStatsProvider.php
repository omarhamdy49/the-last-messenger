<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use Illuminate\Support\Facades\DB;

class HadithDashboardStatsProvider implements DashboardStatsProviderInterface
{
    public function getModuleKey(): string
    {
        return 'hadith';
    }

    /**
     * No `views` figure: the rebuilt schema stores no view counter, and a
     * hardcoded zero on a dashboard card reads as a measurement.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            // One pass with conditional aggregates rather than five COUNT queries
            // that all scan the same table with the same filter.
            $row = DB::table('hadith_hadiths')->selectRaw('
                COUNT(*) AS total,
                SUM(is_published = 1) AS published,
                SUM(display_grade = ?) AS sahih,
                SUM(display_grade = ?) AS hasan,
                SUM(display_grade IS NULL) AS ungraded
            ', [
                HadithGrade::SAHIH->value,
                HadithGrade::HASAN->value,
            ])->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'published' => (int) ($row->published ?? 0),
                'sahih' => (int) ($row->sahih ?? 0),
                'hasan' => (int) ($row->hasan ?? 0),
                'ungraded' => (int) ($row->ungraded ?? 0),
            ];
        } catch (\Throwable) {
            return ['total' => 0, 'published' => 0, 'sahih' => 0, 'hasan' => 0, 'ungraded' => 0];
        }
    }

    /**
     * Kept so a caller that only needs the headline figure does not pull the
     * whole aggregate. Hadith::count() hits the primary key only.
     */
    public function total(): int
    {
        try {
            return Hadith::count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
