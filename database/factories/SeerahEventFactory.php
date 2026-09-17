<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTranslation;
use App\Domain\Seerah\Models\SeerahEventType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * SeerahEvent Factory
 *
 * @extends Factory<SeerahEvent>
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
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SeerahEvent $event) {
            $eventData = $this->faker->randomElement($this->famousEvents);

            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                SeerahEventTranslation::create([
                    'event_id' => $event->id,
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

        return [
            'slug' => Str::slug($event['en']).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'event_type' => fn (): int => $this->randomTypeId(),
            'order' => $this->faker->numberBetween(1, 1000),
            'hijri_year' => $this->faker->numberBetween(-53, 11), // 53 BH to 11 AH
            'hijri_month' => $this->faker->numberBetween(1, 12),
            'hijri_day' => $this->faker->numberBetween(1, 29),
            'gregorian_date' => $this->faker->dateTimeBetween('-1500 years', '-1400 years'),
            'status' => 'published',
            'era' => $this->faker->randomElement(EventEra::cases())->value,
        ];
    }

    /**
     * An unpublished event.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => 'draft']);
    }

    /**
     * Pick a random existing type id, creating "other" when none exist.
     */
    private function randomTypeId(): int
    {
        return SeerahEventType::query()->inRandomOrder()->value('id')
            ?? SeerahEventType::query()->firstOrCreate(
                ['slug' => 'other'],
                ['locale' => ['ar' => 'حدث آخر', 'en' => 'Other Event'], 'is_active' => true],
            )->id;
    }
}
