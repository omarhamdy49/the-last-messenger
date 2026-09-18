<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        return [
            'resource_id' => QuranResource::factory()->translation(),
            'verse_id' => Verse::factory(),
            'text' => 'In the Name of Allah—the Most Compassionate, Most Merciful.',
            'foot_notes' => null,
            'is_locked' => false,
        ];
    }

    public function withFootnote(string $id = '1', string $text = 'A footnote.'): static
    {
        return $this->state(fn (): array => [
            'text' => 'In the Name of Allah<sup foot_note='.$id.'>1</sup>.',
            'foot_notes' => [$id => $text],
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => ['is_locked' => true]);
    }
}
