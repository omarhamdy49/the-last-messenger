<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Media\Models\LiveChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LiveChannel>
 */
class LiveChannelFactory extends Factory
{
    protected $model = LiveChannel::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'streaming_url' => 'https://www.youtube.com/embed/'.Str::random(11),
            'status' => $this->faker->randomElement(LiveStatus::cases())->value,
            'is_published' => true,
            'display_order' => $this->faker->numberBetween(0, 20),
            'views_count' => $this->faker->numberBetween(0, 50000),
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => ['status' => LiveStatus::Live->value]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    /**
     * Attach a translation after creating the channel.
     */
    public function withTranslation(string $locale, string $title, ?string $description = null): static
    {
        return $this->afterCreating(function (LiveChannel $channel) use ($locale, $title, $description): void {
            $channel->translations()->create([
                'locale' => $locale,
                'title' => $title,
                'description' => $description,
                'is_ready' => true,
            ]);
        });
    }
}
