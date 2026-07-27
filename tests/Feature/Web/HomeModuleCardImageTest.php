<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Models\HomeModuleCard;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class HomeModuleCardImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_images_used_by_active_home_cards_are_public(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('home-cards/hero.webp', 'image');

        $media = Media::query()->create([
            'filename' => 'hero.webp',
            'original_filename' => 'hero.webp',
            'path' => 'home-cards/hero.webp',
            'mime_type' => 'image/webp',
            'size' => 5,
            'disk' => 'local',
            'type' => 'image',
            'collection_name' => 'home-cards',
        ]);

        $card = HomeModuleCard::query()->create([
            'key' => 'hero-image',
            'placement' => HomeModuleCardPlacement::HERO,
            'image' => (string) $media->id,
            'is_active' => true,
        ]);

        $this->get(route('public.home-card-image', $media->id))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp');

        $card->update(['is_active' => false]);

        $this->get(route('public.home-card-image', $media->id))->assertNotFound();
    }
}
