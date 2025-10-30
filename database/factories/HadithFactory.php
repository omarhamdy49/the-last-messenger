<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Hadith Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Hadith\Models\Hadith>
 */
class HadithFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Hadith::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookId = $this->faker->numberBetween(1, 9);
        $bookNames = [
            1 => 'Sahih Bukhari',
            2 => 'Sahih Muslim',
            3 => 'Sunan Abu Dawud',
            4 => 'Jami at-Tirmidhi',
            5 => 'Sunan an-Nasa\'i',
            6 => 'Sunan Ibn Majah',
            7 => 'Muwatta Malik',
            8 => 'Musnad Ahmad',
            9 => 'Sunan ad-Darimi',
        ];

        $hadithNumber = $this->faker->numberBetween(1, 7563);
        $reference = $bookNames[$bookId].' '.$hadithNumber;

        return [
            'book_id' => $bookId,
            'chapter_id' => $this->faker->numberBetween(1, 50),
            'hadith_number' => $hadithNumber,
            'arabic_text' => $this->generateArabicHadith(),
            'narrator_chain' => $this->generateNarratorChain(),
            'translations' => [
                'ar' => $this->generateArabicHadith(),
                'en' => $this->faker->paragraphs(3, true),
                'fr' => $this->faker->paragraphs(3, true),
                'tr' => $this->faker->paragraphs(3, true),
                'ur' => $this->generateArabicHadith(), // Using Arabic text as placeholder for Urdu
            ],
            'grade' => $this->faker->randomElement([
                HadithGrade::SAHIH,
                HadithGrade::SAHIH,
                HadithGrade::SAHIH, // Weight toward sahih
                HadithGrade::HASAN,
                HadithGrade::HASAN,
                HadithGrade::DAIF,
                HadithGrade::UNKNOWN,
            ]),
            'reference_url' => $this->faker->optional(0.5)->url(),
            'views_count' => $this->faker->numberBetween(0, 5000),
        ];
    }

    /**
     * Generate realistic Arabic hadith text.
     */
    private function generateArabicHadith(): string
    {
        $arabicPhrases = [
            'قال رسول الله صلى الله عليه وسلم',
            'عن أبي هريرة رضي الله عنه',
            'إن الله تعالى يحب',
            'من كان يؤمن بالله واليوم الآخر',
            'لا يؤمن أحدكم حتى',
            'المسلم من سلم المسلمون من لسانه ويده',
            'خير الناس أنفعهم للناس',
            'الدين النصيحة',
        ];

        return implode(' ', $this->faker->randomElements($arabicPhrases, $this->faker->numberBetween(3, 6)));
    }

    /**
     * Generate narrator chain text.
     */
    private function generateNarratorChain(): string
    {
        $narrators = [
            'أبو هريرة',
            'عبد الله بن عمر',
            'عائشة',
            'أنس بن مالك',
            'جابر بن عبد الله',
            'البخاري',
            'مسلم',
            'الزهري',
        ];

        $chain = [];
        $count = $this->faker->numberBetween(3, 6);
        for ($i = 0; $i < $count; $i++) {
            $chain[] = $this->faker->randomElement($narrators);
        }

        return implode(' عن ', $chain);
    }

    /**
     * Indicate that the hadith is sahih (authentic).
     */
    public function sahih(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => HadithGrade::SAHIH,
        ]);
    }

    /**
     * Indicate that the hadith is hasan (good).
     */
    public function hasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => HadithGrade::HASAN,
        ]);
    }

    /**
     * Indicate that the hadith is daif (weak).
     */
    public function daif(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => HadithGrade::DAIF,
        ]);
    }

    /**
     * Create hadith from specific book.
     */
    public function fromBook(int $bookId): static
    {
        return $this->state(fn (array $attributes) => [
            'book_id' => $bookId,
        ]);
    }

    /**
     * Create popular hadith.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => $this->faker->numberBetween(10000, 100000),
        ]);
    }
}
