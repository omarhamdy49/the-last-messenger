<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
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

    // ------------------------------------------------------------------
    // Presentation editing, show page, delete, reorder
    // ------------------------------------------------------------------

    private function createProgram(string $category = 'seerah', string $title = 'برنامج تجريبي'): MediaProgram
    {
        $program = MediaProgram::create([
            'slug' => 'program-'.uniqid(),
            'category' => $category,
            'maturity_rating' => 'all',
            'year' => 2023,
            'rating' => 7.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 10,
            'display_order' => 0,
        ]);

        MediaProgramTranslation::create([
            'media_program_id' => $program->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $program;
    }

    public function test_index_exposes_program_counts_and_views(): void
    {
        $admin = $this->createAdminUser();
        $this->createProgram('seerah');

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-categories');

        $response->assertOk();
        $categories = $response->viewData('categories');
        $this->assertSame(1, (int) $categories->firstWhere('key', 'seerah')->programs_count);
        $this->assertSame(10, (int) $categories->firstWhere('key', 'seerah')->total_views);
    }

    public function test_show_renders_category_with_assigned_programs(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram('quran', 'برنامج القرآن المخصص');

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-categories/quran');

        $response->assertOk()->assertSee('برنامج القرآن المخصص');
        $this->assertSame('quran', $response->viewData('category')->key);
        $this->assertSame($program->id, $response->viewData('programs')->first()->id);
    }

    public function test_show_returns_404_for_unknown_key(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-categories/unknown')
            ->assertNotFound();
    }

    public function test_admin_can_update_category_presentation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->patch('/su/portal/v1/admin/media-categories/seerah', [
            'name' => ['ar' => 'سيرة مخصصة', 'en' => 'Custom Seerah'],
            'description' => ['ar' => 'وصف التصنيف'],
            'icon' => 'landmark',
            'color' => '#7c3aed',
            'display_order' => 3,
        ]);

        $response->assertRedirect(route('admin.media-categories.index'));
        $category = MediaCategory::where('key', 'seerah')->firstOrFail();
        $this->assertSame('سيرة مخصصة', $category->name['ar']);
        $this->assertSame('Custom Seerah', $category->name['en']);
        $this->assertSame('وصف التصنيف', $category->description['ar']);
        $this->assertSame('landmark', $category->icon);
        $this->assertSame('#7c3aed', $category->color);
        $this->assertSame(3, $category->display_order);
        $this->assertSame('سيرة مخصصة', $category->localizedName('ar'));
    }

    public function test_update_with_redirect_hint_returns_to_show_page(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->patch('/su/portal/v1/admin/media-categories/seerah', [
            'is_featured' => 1,
            'redirect' => 'show',
        ])->assertRedirect(route('admin.media-categories.show', 'seerah'));
    }

    public function test_update_rejects_invalid_color_and_icon(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->patch('/su/portal/v1/admin/media-categories/seerah', [
            'name' => ['ar' => 'سيرة'],
            'icon' => 'Bad Icon!',
            'color' => 'red',
        ])->assertSessionHasErrors(['icon', 'color']);
    }

    public function test_delete_is_refused_while_programs_are_assigned(): void
    {
        $admin = $this->createAdminUser();
        $this->createProgram('documentary');

        $response = $this->actingAs($admin)
            ->delete('/su/portal/v1/admin/media-categories/documentary');

        $response->assertRedirect(route('admin.media-categories.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('media_categories', ['key' => 'documentary']);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->delete('/su/portal/v1/admin/media-categories/kids');

        $response->assertRedirect(route('admin.media-categories.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('media_categories', ['key' => 'kids']);
    }

    public function test_admin_can_reorder_categories(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson('/su/portal/v1/admin/media-categories/reorder', [
            'keys' => ['documentary', 'kids', 'hadith', 'quran', 'seerah'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, MediaCategory::where('key', 'documentary')->firstOrFail()->display_order);
        $this->assertSame(4, MediaCategory::where('key', 'seerah')->firstOrFail()->display_order);
    }

    public function test_reorder_rejects_unknown_keys(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->postJson('/su/portal/v1/admin/media-categories/reorder', [
            'keys' => ['seerah', 'bogus'],
        ])->assertStatus(422);
    }
}
