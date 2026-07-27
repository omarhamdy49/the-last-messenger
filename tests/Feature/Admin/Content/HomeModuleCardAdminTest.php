<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Content\Models\HomeModuleCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomeModuleCardAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_card_at_a_chosen_placement(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.content.home-cards.store'), [
            'key' => 'hero-custom',
            'placement' => HomeModuleCardPlacement::HERO->value,
            'icon' => 'star',
            'target_url' => 'public.seerah',
            'accent_color' => '#0056b3',
            'is_active' => '1',
            'title' => ['ar' => 'بطاقة الواجهة', 'en' => 'Hero card'],
        ])->assertRedirect(route('admin.content.home-cards.index'));

        $this->assertDatabaseHas('home_module_cards', [
            'key' => 'hero-custom',
            'placement' => HomeModuleCardPlacement::HERO->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('home_module_card_translations', [
            'locale' => 'en',
            'title' => 'Hero card',
        ]);
    }

    public function test_admin_can_filter_cards_by_placement(): void
    {
        $this->actingAs($this->createAdminUser());

        $hero = HomeModuleCard::query()->create([
            'key' => 'hero-filter',
            'placement' => HomeModuleCardPlacement::HERO,
        ]);
        $hero->syncTranslations(['title' => ['ar' => 'بطاقة الواجهة']]);

        $explore = HomeModuleCard::query()->create([
            'key' => 'explore-filter',
            'placement' => HomeModuleCardPlacement::EXPLORE,
        ]);
        $explore->syncTranslations(['title' => ['ar' => 'بطاقة الاستكشاف']]);

        $this->get(route('admin.content.home-cards.index', ['placement' => 'hero']))
            ->assertOk()
            ->assertSeeText('بطاقة الواجهة')
            ->assertDontSeeText('بطاقة الاستكشاف');
    }
}
