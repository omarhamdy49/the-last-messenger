<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Sahabi Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Sahaba\Models\Sahabi>
 */
class SahabiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sahabi::class;

    /**
     * Famous companion names for realistic data.
     *
     * @var array<string>
     */
    private array $famousCompanions = [
        'أبو بكر الصديق',
        'عمر بن الخطاب',
        'عثمان بن عفان',
        'علي بن أبي طالب',
        'طلحة بن عبيد الله',
        'الزبير بن العوام',
        'عبد الرحمن بن عوف',
        'سعد بن أبي وقاص',
        'سعيد بن زيد',
        'أبو عبيدة بن الجراح',
        'حمزة بن عبد المطلب',
        'عبد الله بن مسعود',
        'أبو هريرة',
        'أنس بن مالك',
        'جابر بن عبد الله',
    ];

    /**
     * Famous battles.
     *
     * @var array<string>
     */
    private array $famousBattles = [
        'بدر',
        'أحد',
        'الخندق',
        'خيبر',
        'حنين',
        'تبوك',
        'مؤتة',
    ];

    /**
     * Famous tribes.
     *
     * @var array<string>
     */
    private array $famousTribes = [
        'قريش',
        'الأنصار',
        'الأوس',
        'الخزرج',
        'بني هاشم',
        'بني أمية',
        'ثقيف',
    ];

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Sahabi $sahabi) {
            $arabicName = $this->faker->randomElement($this->famousCompanions);
            $englishName = 'Companion '.$this->faker->firstName();
            $hasLaqab = $this->faker->boolean(50);
            $hasNarrations = $this->faker->boolean(60);

            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                $isArabic = in_array($locale, ['ar', 'ur']);

                SahabiTranslation::create([
                    'sahabi_id' => $sahabi->id,
                    'locale' => $locale,
                    'name' => $isArabic ? $arabicName : $englishName,
                    'full_name' => $isArabic
                        ? $arabicName.' '.$this->faker->words(2, true)
                        : $englishName.' '.$this->faker->lastName(),
                    'kunya' => match ($locale) {
                        'ar', 'ur' => 'أبو '.$this->faker->firstName(),
                        'fr' => 'Abou '.$this->faker->firstName(),
                        'tr' => 'Ebu '.$this->faker->firstName(),
                        default => 'Abu '.$this->faker->firstName(),
                    },
                    'laqab' => $hasLaqab ? match ($locale) {
                        'ar', 'ur' => $this->faker->randomElement(['الصديق', 'الفاروق', 'ذو النورين', 'الأمين']),
                        'en' => $this->faker->randomElement(['The Truthful', 'The Criterion', 'The Trustworthy']),
                        'fr' => $this->faker->randomElement(['Le Véridique', 'Le Critère', 'Le Digne de Confiance']),
                        'tr' => $this->faker->randomElement(['Sadık', 'Ayırıcı', 'Güvenilir']),
                        default => null,
                    } : null,
                    'biography' => $this->faker->paragraphs(5, true),
                    'virtues' => $this->faker->paragraphs(3, true),
                    'famous_narrations' => $hasNarrations ? $this->faker->paragraphs(2, true) : null,
                    'is_ready' => true,
                ]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $englishName = 'Companion '.$this->faker->firstName();
        $slug = Str::slug($englishName).'-'.$this->faker->unique()->numberBetween(1, 10000);

        return [
            'slug' => $slug,
            'gender' => 'male',
            'status' => $this->faker->randomElement(CompanionStatus::cases()),
            'birth_date_hijri' => $this->faker->optional(0.5)->numberBetween(-50, -10).' قبل الهجرة',
            'death_date_hijri' => $this->faker->optional(0.7)->numberBetween(10, 60).' هـ',
            'birth_date_gregorian' => $this->faker->optional(0.5)->dateTimeBetween('-1500 years', '-1400 years'),
            'death_date_gregorian' => $this->faker->optional(0.7)->dateTimeBetween('-1400 years', '-1300 years'),
            'age_at_death' => $this->faker->optional(0.7)->numberBetween(40, 90),
            'birth_place' => $this->faker->optional(0.6)->randomElement(['مكة المكرمة', 'المدينة المنورة', 'الطائف']),
            'death_place' => $this->faker->optional(0.7)->randomElement(['المدينة المنورة', 'مكة المكرمة', 'الكوفة', 'البصرة', 'دمشق']),
            'burial_place' => $this->faker->optional(0.7)->randomElement(['البقيع', 'مكة المكرمة', 'الشام', 'العراق']),
            'father_id' => $this->faker->optional(0.3)->numberBetween(1, 100),
            'mother_id' => $this->faker->optional(0.2)->numberBetween(1, 100),
            'tribe' => $this->faker->randomElement($this->famousTribes),
            'clan' => $this->faker->optional(0.5)->words(2, true),
            'conversion_date_hijri' => $this->faker->optional(0.4)->numberBetween(-13, 8).' هـ',
            'conversion_order' => $this->faker->optional(0.3)->numberBetween(1, 100),
            'roles' => $this->faker->randomElements([
                'صحابي',
                'محدث',
                'فقيه',
                'قائد عسكري',
                'والي',
                'كاتب وحي',
            ], $this->faker->numberBetween(1, 4)),
            'hadith_count' => $this->faker->numberBetween(0, 7000),
            'battles' => $this->faker->randomElements($this->famousBattles, $this->faker->numberBetween(0, 5)),
            'profile_image' => $this->faker->optional(0.3)->imageUrl(400, 400, 'people', true),
            'gallery' => $this->faker->optional(0.2)->passthrough([
                $this->faker->imageUrl(800, 600, 'islamic', true),
                $this->faker->imageUrl(800, 600, 'islamic', true),
            ]),
            'related_article_id' => $this->faker->optional(0.3)->numberBetween(1, 100),
            'views_count' => $this->faker->numberBetween(0, 50000),
            'is_featured' => $this->faker->boolean(20), // 20% chance
            'importance_rank' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Create a female companion.
     */
    public function female(): static
    {
        $femaleNames = [
            'عائشة بنت أبي بكر',
            'خديجة بنت خويلد',
            'فاطمة بنت محمد',
            'حفصة بنت عمر',
            'أم سلمة',
            'أسماء بنت أبي بكر',
        ];

        $arabicName = $this->faker->randomElement($femaleNames);

        return $this->state(fn (array $attributes) => [
            'gender' => 'female',
            'battles' => [], // Women typically didn't participate in battles
        ])->afterCreating(function (Sahabi $sahabi) use ($arabicName) {
            $names = [
                'ar' => $arabicName,
                'en' => 'Female Companion '.$this->faker->firstName('female'),
                'fr' => 'Compagnonne '.$this->faker->firstName('female'),
                'tr' => 'Sahabe '.$this->faker->firstName('female'),
                'ur' => $arabicName,
            ];

            foreach ($names as $locale => $name) {
                $sahabi->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name]
                );
            }
        });
    }

    /**
     * Create one of the ten promised paradise.
     */
    public function tenPromisedParadise(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanionStatus::TEN_PROMISED_PARADISE,
            'importance_rank' => $this->faker->numberBetween(1, 10),
            'is_featured' => true,
            'hadith_count' => $this->faker->numberBetween(100, 5000),
        ]);
    }

    /**
     * Create Badr participant.
     */
    public function badrParticipant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanionStatus::BADR_PARTICIPANT,
            'battles' => array_merge(['بدر'], $this->faker->randomElements($this->famousBattles, 3)),
            'importance_rank' => $this->faker->numberBetween(1, 30),
        ]);
    }

    /**
     * Create featured companion.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'views_count' => $this->faker->numberBetween(10000, 100000),
            'hadith_count' => $this->faker->numberBetween(500, 7000),
        ]);
    }

    /**
     * Create top hadith narrator.
     */
    public function topNarrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'hadith_count' => $this->faker->numberBetween(3000, 7000),
            'roles' => ['صحابي', 'محدث', 'فقيه'],
        ]);
    }
}
