<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventType;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * SeerahEvent Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Seerah\Models\SeerahEvent>
 */
class SeerahEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeerahEvent::class;

    /**
     * Famous Seerah events for realistic data.
     *
     * @var array<array>
     */
    private array $famousEvents = [
        ['ar' => 'ولادة النبي محمد ﷺ', 'en' => 'Birth of Prophet Muhammad'],
        ['ar' => 'بعثة النبي ﷺ', 'en' => 'The First Revelation'],
        ['ar' => 'الهجرة إلى المدينة', 'en' => 'Migration to Medina'],
        ['ar' => 'غزوة بدر', 'en' => 'Battle of Badr'],
        ['ar' => 'غزوة أحد', 'en' => 'Battle of Uhud'],
        ['ar' => 'غزوة الخندق', 'en' => 'Battle of the Trench'],
        ['ar' => 'صلح الحديبية', 'en' => 'Treaty of Hudaybiyyah'],
        ['ar' => 'فتح مكة', 'en' => 'Conquest of Mecca'],
        ['ar' => 'حجة الوداع', 'en' => 'Farewell Pilgrimage'],
        ['ar' => 'وفاة النبي ﷺ', 'en' => 'Death of Prophet Muhammad'],
    ];

    /**
     * Famous locations.
     *
     * @var array<string>
     */
    private array $famousLocations = [
        'مكة المكرمة',
        'المدينة المنورة',
        'جبل حراء',
        'جبل أحد',
        'بدر',
        'الطائف',
        'تبوك',
        'خيبر',
    ];

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SeerahEvent $event) {
            $eventData = $this->faker->randomElement($this->famousEvents);

            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                SeerahEventTranslation::create([
                    'seerah_event_id' => $event->id,
                    'locale' => $locale,
                    'title' => $locale === 'ar' || $locale === 'ur'
                        ? $eventData['ar']
                        : ($locale === 'en' ? $eventData['en'] : ucfirst($this->faker->words(4, true))),
                    'description' => $this->faker->paragraphs(3, true),
                    'content' => $this->faker->paragraphs(10, true),
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
        $event = $this->faker->randomElement($this->famousEvents);
        $slug = Str::slug($event['en']).'-'.$this->faker->unique()->numberBetween(1, 1000);

        $hijriYear = $this->faker->numberBetween(-53, 11); // 53 BH to 11 AH
        $gregorianYear = $hijriYear + 622; // Approximate conversion

        return [
            'slug' => $slug,
            'hijri_date' => $this->generateHijriDate($hijriYear),
            'hijri_year' => $hijriYear,
            'hijri_month' => $this->faker->numberBetween(1, 12),
            'hijri_day' => $this->faker->numberBetween(1, 29),
            'gregorian_date' => $this->faker->dateTimeBetween('-1500 years', '-1400 years'),
            'gregorian_year' => $gregorianYear,
            'event_type' => $this->faker->randomElement(EventType::cases()),
            'importance' => $this->faker->randomElement(EventImportance::cases()),
            'location' => $this->faker->randomElement($this->famousLocations),
            'latitude' => $this->faker->optional(0.7)->latitude(),
            'longitude' => $this->faker->optional(0.7)->longitude(),
            'featured_image' => $this->faker->optional(0.6)->imageUrl(1200, 630, 'islamic', true),
            'gallery' => $this->faker->optional(0.3)->passthrough([
                $this->faker->imageUrl(800, 600, 'islamic', true),
                $this->faker->imageUrl(800, 600, 'islamic', true),
                $this->faker->imageUrl(800, 600, 'islamic', true),
            ]),
            'related_article_id' => $this->faker->optional(0.4)->numberBetween(1, 100),
            'views_count' => $this->faker->numberBetween(0, 100000),
            'display_order' => $this->faker->numberBetween(1, 1000),
            'is_featured' => $this->faker->boolean(25), // 25% chance
        ];
    }

    /**
     * Generate Hijri date string.
     */
    private function generateHijriDate(int $year): string
    {
        $months = [
            'محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر', 'جمادى الأولى', 'جمادى الآخرة',
            'رجب', 'شعبان', 'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة',
        ];

        $month = $this->faker->randomElement($months);
        $day = $this->faker->numberBetween(1, 29);

        if ($year < 0) {
            return "{$day} {$month} ".abs($year).' قبل الهجرة';
        }

        return "{$day} {$month} {$year} هـ";
    }

    /**
     * Create a battle event.
     */
    public function battle(): static
    {
        $battles = [
            ['ar' => 'غزوة بدر', 'en' => 'Battle of Badr'],
            ['ar' => 'غزوة أحد', 'en' => 'Battle of Uhud'],
            ['ar' => 'غزوة الخندق', 'en' => 'Battle of the Trench'],
            ['ar' => 'غزوة خيبر', 'en' => 'Battle of Khaybar'],
        ];

        $battle = $this->faker->randomElement($battles);

        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::BATTLE,
            'importance' => EventImportance::HIGH,
        ])->afterCreating(function (SeerahEvent $event) use ($battle) {
            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                $event->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['title' => in_array($locale, ['ar', 'ur']) ? $battle['ar'] : $battle['en']]
                );
            }
        });
    }

    /**
     * Create a migration event.
     */
    public function migration(): static
    {
        $titles = ['ar' => 'الهجرة', 'en' => 'Migration', 'fr' => 'Migration', 'tr' => 'Hicret', 'ur' => 'ہجرت'];

        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::MIGRATION,
            'importance' => EventImportance::HIGH,
        ])->afterCreating(function (SeerahEvent $event) use ($titles) {
            foreach ($titles as $locale => $title) {
                $event->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['title' => $title]
                );
            }
        });
    }

    /**
     * Create a revelation event.
     */
    public function revelation(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::REVELATION,
            'importance' => EventImportance::HIGH,
        ]);
    }

    /**
     * Create a miracle event.
     */
    public function miracle(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::MIRACLE,
            'importance' => $this->faker->randomElement([EventImportance::HIGH, EventImportance::MEDIUM]),
        ]);
    }

    /**
     * Create a featured event.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'importance' => EventImportance::HIGH,
            'views_count' => $this->faker->numberBetween(50000, 500000),
            'featured_image' => $this->faker->imageUrl(1200, 630, 'islamic', true),
        ]);
    }

    /**
     * Create event with location.
     */
    public function withLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $this->faker->latitude(21.0, 25.0), // Mecca/Medina area
            'longitude' => $this->faker->longitude(39.0, 40.0),
        ]);
    }

    /**
     * Create event in Mecca.
     */
    public function inMecca(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'مكة المكرمة',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
        ]);
    }

    /**
     * Create event in Medina.
     */
    public function inMedina(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'المدينة المنورة',
            'latitude' => 24.5247,
            'longitude' => 39.5692,
        ]);
    }
}
