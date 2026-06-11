<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Media\Models\MediaCategory;
use Database\Seeders\MediaCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin media-categories screen: visibility flags for the public programs list.
 */
final class MediaCategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(MediaCategorySeeder::class);
    }

    public function test_guest_cannot_access_media_categories(): void
    {
        $this->get('/su/portal/v1/admin/media-categories')->assertRedirect();
    }

    public function test_non_admin_user_cannot_access_media_categories(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get('/su/portal/v1/admin/media-categories')
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_all_categories_with_toggles(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-categories');

        $response->assertOk();
        foreach (['seerah', 'quran', 'hadith', 'kids', 'documentary'] as $key) {
            $response->assertSee($key);
        }
    }

    public function test_admin_can_unfeature_a_category(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->patch('/su/portal/v1/admin/media-categories/seerah', [
            'is_featured' => 0,
        ]);

        $response->assertRedirect(route('admin.media-categories.index'));
        $this->assertFalse((bool) MediaCategory::query()->where('key', 'seerah')->value('is_featured'));
    }

    public function test_update_rejects_unknown_category_key(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->patch('/su/portal/v1/admin/media-categories/not-a-category', ['is_featured' => 0])
            ->assertNotFound();
    }

    public function test_update_validates_is_featured(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->from('/su/portal/v1/admin/media-categories')
            ->patch('/su/portal/v1/admin/media-categories/seerah', [])
            ->assertSessionHasErrors('is_featured');
    }
}
