<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\HomeModuleCard;
use App\Domain\Content\Models\QuickFilterChip;
use App\Domain\Content\Services\HomeContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeContentServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomeContentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HomeContentService::class);
    }

    public function test_active_cards_are_ordered_and_translated_with_fallback(): void
    {
        $hadith = HomeModuleCard::query()->create([
            'key' => 'hadith', 'icon' => 'message-square-quote', 'target_url' => 'public.hadith',
            'accent_color' => '#218838', 'is_featured' => true, 'is_active' => true, 'sort_order' => 20,
        ]);
        $hadith->syncTranslations(['title' => ['ar' => 'الحديث الشريف'], 'description' => ['ar' => 'وصف']]);

        $seerah = HomeModuleCard::query()->create([
            'key' => 'seerah', 'icon' => 'scroll-text', 'target_url' => 'public.seerah',
            'accent_color' => '#0056b3', 'is_featured' => true, 'is_active' => true, 'sort_order' => 10,
        ]);
        $seerah->syncTranslations(['title' => ['ar' => 'السيرة', 'en' => 'Biography'], 'description' => ['ar' => 'وصف']]);

        HomeModuleCard::query()->create([
            'key' => 'hidden', 'is_featured' => false, 'is_active' => false, 'sort_order' => 5,
        ]);

        $cards = $this->service->activeCards('en');

        $this->assertCount(2, $cards);
        $this->assertSame('seerah', $cards[0]['key']);
        $this->assertSame('Biography', $cards[0]['title']);
        // No EN translation → falls back to Arabic.
        $this->assertSame('الحديث الشريف', $cards[1]['title']);
        $this->assertSame('#218838', $cards[1]['accent_color']);
    }

    public function test_active_chips_are_ordered_and_translated(): void
    {
        $battles = QuickFilterChip::query()->create([
            'key' => 'battles', 'target_url' => 'public.seerah', 'is_active' => true, 'sort_order' => 20,
        ]);
        $battles->syncTranslations(['label' => ['ar' => 'المعارك', 'en' => 'Battles']]);

        $companions = QuickFilterChip::query()->create([
            'key' => 'companions', 'target_url' => 'public.sahaba', 'is_active' => true, 'sort_order' => 10,
        ]);
        $companions->syncTranslations(['label' => ['ar' => 'الصحابة']]);

        QuickFilterChip::query()->create(['key' => 'hidden', 'is_active' => false, 'sort_order' => 5]);

        $chips = $this->service->activeChips('en');

        $this->assertCount(2, $chips);
        $this->assertSame('companions', $chips[0]['key']);
        // No EN translation → falls back to Arabic.
        $this->assertSame('الصحابة', $chips[0]['label']);
        $this->assertSame('Battles', $chips[1]['label']);
    }

    public function test_flush_invalidates_cached_cards_and_chips(): void
    {
        $this->assertCount(0, $this->service->activeCards('ar'));
        $this->assertCount(0, $this->service->activeChips('ar'));

        HomeModuleCard::query()->create(['key' => 'quran', 'is_featured' => false, 'is_active' => true, 'sort_order' => 10]);
        QuickFilterChip::query()->create(['key' => 'seerah', 'is_active' => true, 'sort_order' => 10]);

        // Still cached until flushed.
        $this->assertCount(0, $this->service->activeCards('ar'));

        $this->service->flush();

        $this->assertCount(1, $this->service->activeCards('ar'));
        $this->assertCount(1, $this->service->activeChips('ar'));
    }
}
