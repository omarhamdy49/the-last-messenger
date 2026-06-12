<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiBattle;
use App\Domain\Sahaba\Models\SahabiTimelineItem;
use App\Domain\Sahaba\Models\SahabiTimelineItemTranslation;
use App\Domain\Sahaba\Models\SahabiTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Base class for Sahaba content batch seeders.
 *
 * Idempotent: companions are upserted by slug, translations by
 * (sahabi_id, locale), timeline items by (sahabi_id, position) and
 * battles by (sahabi_id, battle_name). Re-running never duplicates rows.
 */
abstract class SahabaContentSeederBase extends Seeder
{
    /**
     * Companion definitions for this batch.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function companions(): array;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->companions() as $companion) {
            DB::transaction(fn () => $this->seedCompanion($companion));
        }
    }

    /**
     * Upsert a single companion with translations, timeline and battles.
     *
     * @param  array<string, mixed>  $companion
     */
    private function seedCompanion(array $companion): void
    {
        /** @var Sahabi $sahabi */
        $sahabi = Sahabi::query()->updateOrCreate(
            ['slug' => $companion['slug']],
            $companion['attributes'],
        );

        foreach ($companion['translations'] as $locale => $fields) {
            SahabiTranslation::query()->updateOrCreate(
                ['sahabi_id' => $sahabi->id, 'locale' => $locale],
                $fields + ['is_ready' => true],
            );
        }

        foreach ($companion['battles'] ?? [] as $battleName) {
            SahabiBattle::query()->firstOrCreate([
                'sahabi_id' => $sahabi->id,
                'battle_name' => $battleName,
            ]);
        }

        $this->seedTimeline($sahabi, $companion['timeline'] ?? []);
    }

    /**
     * Upsert the companion's personal timeline entries.
     *
     * @param  array<int, array<string, mixed>>  $timeline
     */
    private function seedTimeline(Sahabi $sahabi, array $timeline): void
    {
        foreach ($timeline as $index => $item) {
            /** @var SahabiTimelineItem $timelineItem */
            $timelineItem = SahabiTimelineItem::query()->updateOrCreate(
                ['sahabi_id' => $sahabi->id, 'position' => $index + 1],
                [
                    'hijri_date' => $item['hijri_date'] ?? null,
                    'hijri_year' => $item['hijri_year'] ?? null,
                    'color' => $item['color'] ?? null,
                ],
            );

            foreach (['ar', 'en'] as $locale) {
                if (! isset($item[$locale])) {
                    continue;
                }

                SahabiTimelineItemTranslation::query()->updateOrCreate(
                    [
                        'sahabi_timeline_item_id' => $timelineItem->id,
                        'locale' => $locale,
                    ],
                    [
                        'title' => $item[$locale]['title'] ?? null,
                        'description' => $item[$locale]['description'] ?? null,
                        'is_ready' => true,
                    ],
                );
            }
        }
    }
}
