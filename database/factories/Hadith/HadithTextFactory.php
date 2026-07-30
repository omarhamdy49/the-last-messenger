<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithText>
 */
class HadithTextFactory extends Factory
{
    protected $model = HadithText::class;

    public function definition(): array
    {
        $body = $this->faker->sentence();

        return [
            'hadith_id' => Hadith::factory(),
            'locale' => 'ar',
            'body' => $body,
            'search_body' => $body,
        ];
    }

    public function arabic(string $body = 'إنما الأعمال بالنيات'): static
    {
        return $this->state(fn (): array => [
            'locale' => 'ar',
            'body' => $body,
            // Callers testing the normaliser should set this explicitly; the
            // factory does not normalise, so a test cannot pass by accident.
            'search_body' => $body,
        ]);
    }

    public function english(string $body = 'Actions are judged by intentions'): static
    {
        return $this->state(fn (): array => [
            'locale' => 'en',
            'body' => $body,
            'search_body' => $body,
        ]);
    }
}
