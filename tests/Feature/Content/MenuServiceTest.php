<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Models\MenuItem;
use App\Domain\Content\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuServiceTest extends TestCase
{
    use RefreshDatabase;

    private MenuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MenuService::class);
    }

    private function createItem(array $attributes, array $labels): MenuItem
    {
        /** @var MenuItem $item */
        $item = MenuItem::query()->create(array_merge([
            'location' => MenuLocation::HEADER->value,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));

        $item->syncTranslations(['label' => $labels]);

        return $item;
    }

    public function test_tree_returns_translated_labels_in_sort_order(): void
    {
        $this->createItem(['route_name' => 'public.hadith', 'sort_order' => 20], ['ar' => 'الحديث', 'en' => 'Hadith']);
        $this->createItem(['route_name' => 'public.home', 'sort_order' => 10], ['ar' => 'الرئيسية', 'en' => 'Home']);

        $tree = $this->service->tree(MenuLocation::HEADER, 'en');

        $this->assertCount(2, $tree);
        $this->assertSame('Home', $tree[0]['label']);
        $this->assertSame('public.home', $tree[0]['route_name']);
        $this->assertSame('Hadith', $tree[1]['label']);
    }

    public function test_tree_nests_children_under_their_parent(): void
    {
        $parent = $this->createItem(['route_name' => 'public.seerah', 'sort_order' => 10], ['ar' => 'السيرة']);
        $this->createItem(
            ['route_name' => 'public.seerah.battles', 'parent_id' => $parent->id, 'sort_order' => 10],
            ['ar' => 'الغزوات']
        );

        $tree = $this->service->tree(MenuLocation::HEADER, 'ar');

        $this->assertCount(1, $tree);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('الغزوات', $tree[0]['children'][0]['label']);
    }

    public function test_label_falls_back_to_arabic_when_locale_missing(): void
    {
        $this->createItem(['route_name' => 'public.home', 'sort_order' => 10], ['ar' => 'الرئيسية']);

        $tree = $this->service->tree(MenuLocation::HEADER, 'tr');

        $this->assertSame('الرئيسية', $tree[0]['label']);
    }

    public function test_tree_excludes_inactive_items_and_other_locations(): void
    {
        $this->createItem(['route_name' => 'public.home', 'sort_order' => 10], ['ar' => 'الرئيسية']);
        $this->createItem(['route_name' => 'public.hidden', 'sort_order' => 20, 'is_active' => false], ['ar' => 'مخفي']);
        $this->createItem(
            ['route_name' => 'public.about', 'location' => MenuLocation::FOOTER_QUICK->value, 'sort_order' => 10],
            ['ar' => 'عن المشروع']
        );

        $tree = $this->service->tree(MenuLocation::HEADER, 'ar');

        $this->assertCount(1, $tree);
        $this->assertSame('public.home', $tree[0]['route_name']);
    }

    public function test_flush_invalidates_the_cached_tree(): void
    {
        $this->createItem(['route_name' => 'public.home', 'sort_order' => 10], ['ar' => 'الرئيسية']);

        // Prime the cache.
        $this->assertCount(1, $this->service->tree(MenuLocation::HEADER, 'ar'));

        $this->createItem(['route_name' => 'public.quran', 'sort_order' => 20], ['ar' => 'القرآن']);

        // Still cached until flushed.
        $this->assertCount(1, $this->service->tree(MenuLocation::HEADER, 'ar'));

        $this->service->flush();

        $this->assertCount(2, $this->service->tree(MenuLocation::HEADER, 'ar'));
    }
}
