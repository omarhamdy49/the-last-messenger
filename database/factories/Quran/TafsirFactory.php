<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Tafsir;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tafsir>
 */
class TafsirFactory extends Factory
{
    protected $model = Tafsir::class;

    public function definition(): array
    {
        return [
            'resource_id' => QuranResource::factory()->tafsir(),
            'verse_id' => Verse::factory(),
            'text' => '<p>تفسير الآية.</p>',
            'is_locked' => false,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (): array => ['is_locked' => true]);
    }
}
