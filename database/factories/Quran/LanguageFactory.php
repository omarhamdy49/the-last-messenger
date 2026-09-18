<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return [
            'id' => fn (): int => ((int) Language::query()->max('id')) + 1,
            'name' => 'English',
            'native_name' => 'English',
            'iso_code' => fn (): string => 'l'.(((int) Language::query()->max('id')) + 1),
            'direction' => 'ltr',
            'translations_count' => 1,
            'is_active' => true,
        ];
    }

    public function iso(string $iso, string $name, string $direction = 'ltr'): static
    {
        return $this->state(fn (): array => ['iso_code' => $iso, 'name' => $name, 'native_name' => $name, 'direction' => $direction]);
    }
}
