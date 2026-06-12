<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\QuickFilterChip;
use App\Domain\Content\Services\HomeContentService;
use Illuminate\Database\Seeder;

/**
 * QuickFilterChipsSeeder
 *
 * Seeds the hero quick-filter chips with the CURRENT hardcoded set from
 * Site\HomeController::index() $quickFilters (targets verbatim, labels from
 * resources/lang/{ar,en,fr,tr,ur}/site.php quick_filter_* keys).
 * Idempotent: chips are upserted by key.
 */
class QuickFilterChipsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->chips() as $index => $chip) {
            /** @var QuickFilterChip $model */
            $model = QuickFilterChip::query()->updateOrCreate(
                ['key' => $chip['key']],
                [
                    'target_query' => null,
                    'target_url' => $chip['target_url'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );

            $model->syncTranslations(['label' => $chip['labels']]);
        }

        app(HomeContentService::class)->flush();

        $this->command?->info('Quick filter chips seeded: '.QuickFilterChip::query()->count().' chips.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function chips(): array
    {
        return [
            [
                'key' => 'seerah',
                'target_url' => 'public.seerah',
                'labels' => ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Siyer', 'ur' => 'سیرت'],
            ],
            [
                'key' => 'hadiths',
                'target_url' => 'public.hadith',
                'labels' => ['ar' => 'الأحاديث', 'en' => 'Hadiths', 'fr' => 'Hadiths', 'tr' => 'Hadisler', 'ur' => 'احادیث'],
            ],
            [
                'key' => 'battles',
                'target_url' => 'public.seerah',
                'labels' => ['ar' => 'المعارك', 'en' => 'Battles', 'fr' => 'Batailles', 'tr' => 'Savaşlar', 'ur' => 'غزوات'],
            ],
            [
                'key' => 'companions',
                'target_url' => 'public.sahaba',
                'labels' => ['ar' => 'الصحابة', 'en' => 'Companions', 'fr' => 'Compagnons', 'tr' => 'Sahabeler', 'ur' => 'صحابہ'],
            ],
        ];
    }
}
