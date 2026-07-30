<?php

declare(strict_types=1);

namespace Database\Factories\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithGrading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HadithGrading>
 */
class HadithGradingFactory extends Factory
{
    protected $model = HadithGrading::class;

    public function definition(): array
    {
        return [
            'hadith_id' => Hadith::factory(),
            'grade' => HadithGrade::SAHIH,
            'scope' => GradeScope::HADITH,
            'raw_grade' => 'Sahih',
            'locale' => 'en',
            'authority_rank' => 100,
        ];
    }

    public function grade(HadithGrade $grade, string $raw): static
    {
        return $this->state(fn (): array => ['grade' => $grade, 'raw_grade' => $raw]);
    }

    /** A verdict on the chain, which must not feed display_grade. */
    public function isnadScope(): static
    {
        return $this->state(fn (): array => ['scope' => GradeScope::ISNAD]);
    }

    public function partLabelled(string $label): static
    {
        return $this->state(fn (): array => ['part_label' => $label]);
    }
}
