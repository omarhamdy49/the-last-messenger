<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\Mushaf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mushaf>
 */
class MushafFactory extends Factory
{
    protected $model = Mushaf::class;

    public function definition(): array
    {
        return [
            'id' => fn (): int => ((int) Mushaf::query()->max('id')) + 1,
            'name' => 'QCF V2',
            'description' => 'Madani Mushaf',
            'pages_count' => 604,
            'lines_per_page' => 15,
            'default_font_name' => 'v2',
            'mapping_mode' => 'reference',
            'qirat_name' => 'Hafs',
            'font_version' => 'v2',
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true, 'is_active' => true]);
    }
}
