<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Person Factory (Genealogy)
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Genealogy\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Person::class;

    /**
     * Famous names for realistic genealogy data.
     *
     * @var array<string>
     */
    private array $famousNames = [
        'عبد الله',
        'محمد',
        'إبراهيم',
        'إسماعيل',
        'عبد المطلب',
        'أبو طالب',
        'حمزة',
        'العباس',
        'فاطمة',
        'خديجة',
        'عائشة',
    ];

    /**
     * Famous tribes.
     *
     * @var array<string>
     */
    private array $tribes = [
        'قريش',
        'بني هاشم',
        'بني أمية',
        'الأنصار',
        'الأوس',
        'الخزرج',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $arabicName = $this->faker->randomElement($this->famousNames);
        $englishName = $this->faker->firstName();

        return [
            'name' => [
                'ar' => $arabicName,
                'en' => $englishName,
                'fr' => $englishName,
                'tr' => $englishName,
                'ur' => $arabicName,
            ],
            'title' => $this->faker->optional(0.3)->passthrough([
                'ar' => $this->faker->randomElement(['السيد', 'الشيخ', 'الأمير']),
                'en' => $this->faker->randomElement(['The Leader', 'The Sheikh', 'The Prince']),
                'fr' => $this->faker->randomElement(['Le Chef', 'Le Cheikh', 'Le Prince']),
                'tr' => $this->faker->randomElement(['Lider', 'Şeyh', 'Prens']),
                'ur' => $this->faker->randomElement(['السيد', 'الشيخ', 'الأمير']),
            ]),
            'biography' => $this->faker->optional(0.4)->passthrough([
                'ar' => $this->faker->paragraphs(3, true),
                'en' => $this->faker->paragraphs(3, true),
                'fr' => $this->faker->paragraphs(3, true),
                'tr' => $this->faker->paragraphs(3, true),
                'ur' => $this->faker->paragraphs(3, true),
            ]),
            'person_type' => $this->faker->randomElement([
                PersonType::FAMILY,
                PersonType::FAMILY,
                PersonType::FAMILY, // Weight toward family
                PersonType::HISTORICAL_FIGURE,
                PersonType::TRIBE_LEADER,
            ]),
            'gender' => $this->faker->randomElement(['male', 'male', 'male', 'female']), // Weight toward male
            'birth_date_hijri' => $this->faker->optional(0.4)->dateTimeBetween('-1500 years', '-1300 years'),
            'birth_date_gregorian' => $this->faker->optional(0.4)->dateTimeBetween('-1500 years', '-1300 years'),
            'birth_place' => $this->faker->optional(0.3)->passthrough([
                'ar' => $this->faker->randomElement(['مكة', 'المدينة', 'الطائف']),
                'en' => $this->faker->randomElement(['Mecca', 'Medina', 'Taif']),
                'fr' => $this->faker->randomElement(['La Mecque', 'Médine', 'Taif']),
                'tr' => $this->faker->randomElement(['Mekke', 'Medine', 'Taif']),
                'ur' => $this->faker->randomElement(['مکہ', 'مدینہ', 'طائف']),
            ]),
            'death_date_hijri' => $this->faker->optional(0.5)->dateTimeBetween('-1400 years', '-1200 years'),
            'death_date_gregorian' => $this->faker->optional(0.5)->dateTimeBetween('-1400 years', '-1200 years'),
            'death_place' => $this->faker->optional(0.4)->passthrough([
                'ar' => $this->faker->randomElement(['مكة', 'المدينة', 'الشام', 'العراق']),
                'en' => $this->faker->randomElement(['Mecca', 'Medina', 'Sham', 'Iraq']),
                'fr' => $this->faker->randomElement(['La Mecque', 'Médine', 'Sham', 'Iraq']),
                'tr' => $this->faker->randomElement(['Mekke', 'Medine', 'Şam', 'Irak']),
                'ur' => $this->faker->randomElement(['مکہ', 'مدینہ', 'شام', 'عراق']),
            ]),
            'burial_place' => $this->faker->optional(0.4)->passthrough([
                'ar' => $this->faker->randomElement(['البقيع', 'مكة', 'المدينة']),
                'en' => $this->faker->randomElement(['Al-Baqi', 'Mecca', 'Medina']),
                'fr' => $this->faker->randomElement(['Al-Baqi', 'La Mecque', 'Médine']),
                'tr' => $this->faker->randomElement(['Baki', 'Mekke', 'Medine']),
                'ur' => $this->faker->randomElement(['البقیع', 'مکہ', 'مدینہ']),
            ]),
            'father_id' => null, // Should be set explicitly for family tree
            'mother_id' => null, // Should be set explicitly for family tree
            'sahaba_id' => $this->faker->optional(0.1)->numberBetween(1, 100),
            'tribe' => $this->faker->randomElement($this->tribes),
            'occupation' => $this->faker->optional(0.3)->passthrough([
                'ar' => $this->faker->randomElement(['تاجر', 'قائد', 'عالم', 'شاعر']),
                'en' => $this->faker->randomElement(['Merchant', 'Commander', 'Scholar', 'Poet']),
                'fr' => $this->faker->randomElement(['Marchand', 'Commandant', 'Savant', 'Poète']),
                'tr' => $this->faker->randomElement(['Tüccar', 'Komutan', 'Alim', 'Şair']),
                'ur' => $this->faker->randomElement(['تاجر', 'قائد', 'عالم', 'شاعر']),
            ]),
            'is_verified' => $this->faker->boolean(80), // 80% verified
            'notes' => $this->faker->optional(0.2)->passthrough([
                'ar' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
                'fr' => $this->faker->paragraph(),
                'tr' => $this->faker->paragraph(),
                'ur' => $this->faker->paragraph(),
            ]),
        ];
    }

    /**
     * Create a prophet.
     */
    public function prophet(): static
    {
        $prophetNames = [
            'محمد ﷺ',
            'إبراهيم',
            'إسماعيل',
            'إسحاق',
            'يعقوب',
            'موسى',
            'عيسى',
        ];

        return $this->state(fn (array $attributes) => [
            'person_type' => PersonType::PROPHET,
            'name' => [
                'ar' => $this->faker->randomElement($prophetNames),
                'en' => 'Prophet '.$this->faker->firstName(),
                'fr' => 'Prophète '.$this->faker->firstName(),
                'tr' => 'Peygamber '.$this->faker->firstName(),
                'ur' => $this->faker->randomElement($prophetNames),
            ],
            'is_verified' => true,
        ]);
    }

    /**
     * Create a male person.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'male',
        ]);
    }

    /**
     * Create a female person.
     */
    public function female(): static
    {
        $femaleNames = [
            'فاطمة',
            'خديجة',
            'عائشة',
            'حفصة',
            'أم كلثوم',
            'زينب',
        ];

        return $this->state(fn (array $attributes) => [
            'gender' => 'female',
            'name' => [
                'ar' => $this->faker->randomElement($femaleNames),
                'en' => $this->faker->firstName('female'),
                'fr' => $this->faker->firstName('female'),
                'tr' => $this->faker->firstName('female'),
                'ur' => $this->faker->randomElement($femaleNames),
            ],
        ]);
    }

    /**
     * Create a companion.
     */
    public function companion(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => $this->faker->randomElement([
                PersonType::COMPANION_MALE,
                PersonType::COMPANION_FEMALE,
            ]),
            'sahaba_id' => $this->faker->numberBetween(1, 100),
            'is_verified' => true,
        ]);
    }

    /**
     * Create a tribe leader.
     */
    public function tribeLeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => PersonType::TRIBE_LEADER,
            'occupation' => [
                'ar' => 'زعيم قبيلة',
                'en' => 'Tribe Leader',
                'fr' => 'Chef de Tribu',
                'tr' => 'Kabile Lideri',
                'ur' => 'قبیلے کا سردار',
            ],
        ]);
    }

    /**
     * Create person with father.
     */
    public function withFather(int $fatherId): static
    {
        return $this->state(fn (array $attributes) => [
            'father_id' => $fatherId,
        ]);
    }

    /**
     * Create person with mother.
     */
    public function withMother(int $motherId): static
    {
        return $this->state(fn (array $attributes) => [
            'mother_id' => $motherId,
        ]);
    }

    /**
     * Create verified person.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
