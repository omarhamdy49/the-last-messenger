<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.content.menus.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_menus_index(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.content.menus.index', ['location' => 'header']))
            ->assertOk()
            ->assertViewIs('admin.content.menus.index');
    }

    public function test_admin_can_create_menu_item_with_route_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.content.menus.store'), [
            'location' => 'header',
            'route_name' => 'admin.dashboard',
            'is_active' => '1',
            'label' => ['ar' => 'الرئيسية', 'en' => 'Home'],
        ])->assertRedirect(route('admin.content.menus.index', ['location' => 'header']));

        $item = MenuItem::query()->with('translations')->firstOrFail();

        $this->assertSame('admin.dashboard', $item->route_name);
        $this->assertSame('الرئيسية', $item->getTranslation('label', 'ar'));
        $this->assertSame('Home', $item->getTranslation('label', 'en'));
        $this->assertTrue($item->is_active);
    }

    public function test_store_validation_rejects_unknown_route_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.content.menus.store'), [
            'location' => 'header',
            'route_name' => 'definitely.not.a.route',
            'label' => ['ar' => 'تجربة'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['route_name']);
    }

    public function test_store_validation_requires_route_name_or_url_and_arabic_label(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.content.menus.store'), [
            'location' => 'header',
            'label' => ['en' => 'No Arabic'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['route_name', 'url', 'label.ar']);
    }

    public function test_store_validation_rejects_invalid_url_and_location(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.content.menus.store'), [
            'location' => 'not-a-location',
            'url' => 'not a url',
            'label' => ['ar' => 'تجربة'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['location', 'url']);
    }

    public function test_admin_can_move_menu_item_down(): void
    {
        $this->actingAs($this->createAdminUser());

        $first = MenuItem::query()->create([
            'location' => 'header', 'url' => 'https://example.com/a', 'is_active' => true, 'sort_order' => 0,
        ]);
        $second = MenuItem::query()->create([
            'location' => 'header', 'url' => 'https://example.com/b', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->post(route('admin.content.menus.move', $first->id), ['direction' => 'down'])
            ->assertRedirect();

        $this->assertSame(1, $first->refresh()->sort_order);
        $this->assertSame(0, $second->refresh()->sort_order);
    }

    public function test_admin_can_update_and_delete_menu_item(): void
    {
        $this->actingAs($this->createAdminUser());

        $item = MenuItem::query()->create([
            'location' => 'footer_quick', 'url' => 'https://example.com', 'is_active' => true, 'sort_order' => 0,
        ]);
        $item->syncTranslations(['label' => ['ar' => 'رابط']]);

        $this->put(route('admin.content.menus.update', $item->id), [
            'location' => 'footer_quick',
            'url' => 'https://example.org',
            'is_active' => '1',
            'label' => ['ar' => 'رابط محدث'],
        ])->assertRedirect(route('admin.content.menus.index', ['location' => 'footer_quick']));

        $item->refresh()->load('translations');
        $this->assertSame('https://example.org', $item->url);
        $this->assertSame('رابط محدث', $item->getTranslation('label', 'ar'));

        $this->delete(route('admin.content.menus.destroy', $item->id))->assertRedirect();
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    public function test_updating_missing_menu_item_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->put(route('admin.content.menus.update', 999999), [
            'location' => 'header',
            'url' => 'https://example.com',
            'label' => ['ar' => 'تجربة'],
        ])->assertNotFound();
    }
}
