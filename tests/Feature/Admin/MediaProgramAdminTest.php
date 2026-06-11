<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin media-programs screens: list (search/filter/sort), CRUD, totals fields.
 */
final class MediaProgramAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createProgram(array $overrides = [], string $title = 'برنامج السيرة التجريبي'): MediaProgram
    {
        $program = MediaProgram::create(array_merge([
            'slug' => 'program-'.uniqid(),
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2023,
            'total_seasons' => 1,
            'total_episodes' => 10,
            'rating' => 7.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
            'display_order' => 0,
        ], $overrides));

        // created_at is not mass assignable — apply it explicitly when overridden.
        if (isset($overrides['created_at'])) {
            $program->created_at = $overrides['created_at'];
            $program->save();
        }

        MediaProgramTranslation::create([
            'media_program_id' => $program->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $program;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'test-program',
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'total_seasons' => 2,
            'total_episodes' => 30,
            'rating' => 8.5,
            'display_order' => 1,
            'title' => ['ar' => 'برنامج تجريبي', 'en' => 'Test Program'],
            'description' => ['ar' => '<p>وصف تجريبي</p>'],
            'host' => ['ar' => 'مقدم البرنامج'],
        ], $overrides);
    }

    public function test_guest_is_redirected_from_programs_index(): void
    {
        $this->get('/su/portal/v1/admin/media-programs')->assertRedirect();
    }

    public function test_non_admin_cannot_access_programs_index(): void
    {
        $this->actingAs($this->createUser())
            ->get('/su/portal/v1/admin/media-programs')
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_programs_list_ordered_newest_first(): void
    {
        $admin = $this->createAdminUser();
        $older = $this->createProgram(['created_at' => now()->subDays(5)]);
        $newer = $this->createProgram(['created_at' => now()]);

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-programs');

        $response->assertOk();
        $listed = $response->viewData('programs');
        $this->assertSame($newer->id, $listed->first()->id);
        $this->assertSame($older->id, $listed->last()->id);
    }

    public function test_admin_can_search_programs_by_title(): void
    {
        $admin = $this->createAdminUser();
        $match = $this->createProgram([], 'برنامج فريد للبحث');
        $this->createProgram();

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs?q='.urlencode('فريد للبحث'));

        $response->assertOk();
        $listed = $response->viewData('programs');
        $this->assertCount(1, $listed);
        $this->assertSame($match->id, $listed->first()->id);
    }

    public function test_admin_can_filter_programs_by_category(): void
    {
        $admin = $this->createAdminUser();
        $seerah = $this->createProgram(['category' => 'seerah']);
        $this->createProgram(['category' => 'kids']);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs?category=seerah');

        $response->assertOk();
        $listed = $response->viewData('programs');
        $this->assertCount(1, $listed);
        $this->assertSame($seerah->id, $listed->first()->id);
    }

    public function test_index_rejects_invalid_category_filter(): void
    {
        $this->actingAs($this->createAdminUser())
            ->get('/su/portal/v1/admin/media-programs?category=bogus')
            ->assertSessionHasErrors('category');
    }

    public function test_admin_can_create_program_with_totals(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post('/su/portal/v1/admin/media-programs', $this->validPayload());

        $response->assertRedirect(route('admin.media-programs.index'));
        $this->assertDatabaseHas('media_programs', [
            'slug' => 'test-program',
            'total_seasons' => 2,
            'total_episodes' => 30,
        ]);
        $this->assertDatabaseHas('media_program_translations', [
            'locale' => 'ar',
            'title' => 'برنامج تجريبي',
        ]);
    }

    public function test_store_requires_arabic_title(): void
    {
        $this->actingAs($this->createAdminUser())
            ->post('/su/portal/v1/admin/media-programs', $this->validPayload(['title' => ['en' => 'No Arabic']]))
            ->assertSessionHasErrors('title.ar');
    }

    public function test_store_rejects_invalid_totals(): void
    {
        $this->actingAs($this->createAdminUser())
            ->post('/su/portal/v1/admin/media-programs', $this->validPayload([
                'total_seasons' => 0,
                'total_episodes' => -3,
            ]))
            ->assertSessionHasErrors(['total_seasons', 'total_episodes']);
    }

    public function test_store_rejects_unknown_library_media_ids(): void
    {
        $this->actingAs($this->createAdminUser())
            ->post('/su/portal/v1/admin/media-programs', $this->validPayload([
                'poster_id' => 999999,
                'banner_id' => 999999,
            ]))
            ->assertSessionHasErrors(['poster_id', 'banner_id']);
    }

    public function test_admin_can_view_program_details(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram(['total_seasons' => 3, 'total_episodes' => 45]);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs/'.$program->id);

        $response->assertOk();
        $response->assertSee($program->getTranslation('title', 'ar'));
    }

    public function test_show_returns_404_for_unknown_program(): void
    {
        $this->actingAs($this->createAdminUser())
            ->get('/su/portal/v1/admin/media-programs/999999')
            ->assertNotFound();
    }

    public function test_admin_can_update_program_totals(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $response = $this->actingAs($admin)->put(
            '/su/portal/v1/admin/media-programs/'.$program->id,
            $this->validPayload(['slug' => $program->slug, 'total_seasons' => 4, 'total_episodes' => 60])
        );

        $response->assertRedirect(route('admin.media-programs.index'));
        $this->assertDatabaseHas('media_programs', [
            'id' => $program->id,
            'total_seasons' => 4,
            'total_episodes' => 60,
        ]);
    }

    public function test_admin_can_create_scheduled_program(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/su/portal/v1/admin/media-programs', $this->validPayload([
            'is_published' => 0,
            'publish_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]));

        $response->assertRedirect(route('admin.media-programs.index'));
        $program = MediaProgram::query()->where('slug', 'test-program')->firstOrFail();
        $this->assertFalse($program->is_published);
        $this->assertNotNull($program->publish_at);
        $this->assertSame('scheduled', $program->publishStatus());
    }

    public function test_admin_can_filter_programs_by_publish_status(): void
    {
        $admin = $this->createAdminUser();
        $published = $this->createProgram(['is_published' => true]);
        $this->createProgram(['is_published' => false, 'publish_at' => now()->addDay()]);
        $this->createProgram(['is_published' => false]);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs?publish_status=published');
        $response->assertOk();
        $this->assertCount(1, $response->viewData('programs'));
        $this->assertSame($published->id, $response->viewData('programs')->first()->id);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs?publish_status=scheduled');
        $this->assertCount(1, $response->viewData('programs'));

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-programs?publish_status=draft');
        $this->assertCount(1, $response->viewData('programs'));
    }

    public function test_index_rejects_invalid_publish_status_filter(): void
    {
        $this->actingAs($this->createAdminUser())
            ->get('/su/portal/v1/admin/media-programs?publish_status=bogus')
            ->assertSessionHasErrors('publish_status');
    }

    public function test_publish_scheduled_command_publishes_due_programs_only(): void
    {
        $due = $this->createProgram(['is_published' => false, 'publish_at' => now()->subHour()]);
        $future = $this->createProgram(['is_published' => false, 'publish_at' => now()->addDay()]);
        $draft = $this->createProgram(['is_published' => false]);

        $this->artisan('media:publish-scheduled')
            ->expectsOutput('Published 1 scheduled media program(s).')
            ->assertExitCode(0);

        $this->assertTrue($due->fresh()->is_published);
        $this->assertFalse($future->fresh()->is_published);
        $this->assertFalse($draft->fresh()->is_published);
    }

    public function test_unpublished_program_is_hidden_from_public_page(): void
    {
        $program = $this->createProgram(['is_published' => false, 'publish_at' => now()->addDay()]);

        $this->withoutVite()->get('/media/'.$program->slug)->assertNotFound();
    }

    public function test_published_program_is_visible_on_public_page(): void
    {
        $program = $this->createProgram(['is_published' => true]);

        $this->withoutVite()->get('/media/'.$program->slug)->assertOk();
    }

    private function createEpisode(MediaProgram $program, int $number, string $title = 'حلقة تجريبية'): \App\Domain\Media\Models\MediaEpisode
    {
        $episode = \App\Domain\Media\Models\MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => $number,
            'slug' => 'episode-'.uniqid(),
            'video_url' => 'https://example.com/video.mp4',
            'duration_seconds' => 600,
        ]);

        \App\Domain\Media\Models\MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $episode;
    }

    public function test_admin_can_reorder_episodes(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $first = $this->createEpisode($program, 1);
        $second = $this->createEpisode($program, 2);
        $third = $this->createEpisode($program, 3);

        $response = $this->actingAs($admin)->postJson(
            "/su/portal/v1/admin/media-programs/{$program->id}/episodes/reorder",
            ['episode_ids' => [$third->id, $first->id, $second->id]]
        );

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, $third->fresh()->episode_number);
        $this->assertSame(2, $first->fresh()->episode_number);
        $this->assertSame(3, $second->fresh()->episode_number);
    }

    public function test_reorder_rejects_unknown_episode_ids(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)->postJson(
            "/su/portal/v1/admin/media-programs/{$program->id}/episodes/reorder",
            ['episode_ids' => [999999]]
        )->assertStatus(422);
    }

    public function test_admin_can_assign_episodes_from_another_program(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createProgram();
        $this->createEpisode($target, 1);
        $source = $this->createProgram();
        $foreign = $this->createEpisode($source, 1);

        $response = $this->actingAs($admin)->postJson(
            "/su/portal/v1/admin/media-programs/{$target->id}/episodes/assign",
            ['episode_ids' => [$foreign->id]]
        );

        $response->assertOk()->assertJson(['success' => true, 'assigned' => 1]);
        $moved = $foreign->fresh();
        $this->assertSame($target->id, $moved->program_id);
        $this->assertSame(2, $moved->episode_number);
    }

    public function test_episodes_library_search_excludes_target_program(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createProgram();
        $this->createEpisode($target, 1, 'حلقة داخل البرنامج');
        $source = $this->createProgram();
        $this->createEpisode($source, 1, 'حلقة خارجية مميزة');

        $response = $this->actingAs($admin)->getJson(
            '/su/portal/v1/admin/media-episodes?exclude_program_id='.$target->id.'&q='.urlencode('مميزة')
        );

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($source->id, $response->json('data.0.program_id'));
    }

    public function test_admin_can_delete_episode_from_program_page(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);

        $this->actingAs($admin)
            ->delete('/su/portal/v1/admin/media-episodes/'.$episode->id, ['from_program' => 1])
            ->assertRedirect(route('admin.media-programs.show', $program->id));

        $this->assertDatabaseMissing('media_episodes', ['id' => $episode->id]);
    }

    public function test_admin_can_update_images_from_hero(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $response = $this->actingAs($admin)->post(
            "/su/portal/v1/admin/media-programs/{$program->id}/images",
            ['poster' => \Illuminate\Http\Testing\File::image('poster.jpg', 300, 300)]
        );

        $response->assertRedirect(route('admin.media-programs.show', $program->id));
        $this->assertSame(1, $program->fresh()->getMedia('poster')->count());
    }

    public function test_images_endpoint_rejects_invalid_file(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)->post(
            "/su/portal/v1/admin/media-programs/{$program->id}/images",
            ['poster' => \Illuminate\Http\Testing\File::create('document.pdf', 100)]
        )->assertSessionHasErrors('poster');
    }

    public function test_admin_can_save_seo_fields_per_locale(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/su/portal/v1/admin/media-programs', $this->validPayload([
            'seo_title' => ['ar' => 'عنوان سيو مخصص', 'en' => 'Custom SEO Title'],
            'seo_description' => ['ar' => 'وصف سيو مخصص للبرنامج'],
            'seo_keywords' => ['ar' => 'سيرة, برنامج, رمضان'],
        ]));

        $response->assertRedirect(route('admin.media-programs.index'));
        $this->assertDatabaseHas('media_program_translations', [
            'locale' => 'ar',
            'seo_title' => 'عنوان سيو مخصص',
            'seo_description' => 'وصف سيو مخصص للبرنامج',
            'seo_keywords' => 'سيرة, برنامج, رمضان',
        ]);
        $this->assertDatabaseHas('media_program_translations', [
            'locale' => 'en',
            'seo_title' => 'Custom SEO Title',
        ]);
    }

    public function test_public_program_page_uses_seo_title_override(): void
    {
        $program = $this->createProgram();
        MediaProgramTranslation::query()
            ->where('media_program_id', $program->id)
            ->where('locale', 'ar')
            ->update(['seo_title' => 'عنوان سيو خاص', 'seo_description' => 'وصف سيو خاص']);

        $response = $this->withoutVite()->get('/media/'.$program->slug);

        $response->assertOk();
        $response->assertSee('<title>عنوان سيو خاص</title>', false);
        $response->assertSee('وصف سيو خاص');
    }

    public function test_public_program_page_falls_back_to_title_with_app_name(): void
    {
        $program = $this->createProgram([], 'برنامج بدون سيو');

        $response = $this->withoutVite()->get('/media/'.$program->slug);

        $response->assertOk();
        $response->assertSee('<title>برنامج بدون سيو | '.config('app.name').'</title>', false);
    }

    public function test_admin_can_delete_program(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)
            ->delete('/su/portal/v1/admin/media-programs/'.$program->id)
            ->assertRedirect(route('admin.media-programs.index'));

        $this->assertDatabaseMissing('media_programs', ['id' => $program->id]);
    }
}
