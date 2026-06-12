<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin media-episodes screens: list (search/filter/sort/stats), CRUD, thumbnail wiring.
 */
final class MediaEpisodeAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createProgram(string $title = 'برنامج السيرة التجريبي'): MediaProgram
    {
        $program = MediaProgram::create([
            'slug' => 'program-'.uniqid(),
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2023,
            'rating' => 7.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
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

    private function createEpisode(MediaProgram $program, int $number, array $overrides = [], string $title = 'حلقة تجريبية'): MediaEpisode
    {
        $episode = MediaEpisode::create(array_merge([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => $number,
            'slug' => 'episode-'.uniqid(),
            'video_url' => 'https://example.com/video.mp4',
            'duration_seconds' => 600,
            'views_count' => 0,
            'is_featured' => false,
        ], $overrides));

        // created_at is not mass assignable — apply it explicitly when overridden.
        if (isset($overrides['created_at'])) {
            $episode->created_at = $overrides['created_at'];
            $episode->save();
        }

        MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $episode;
    }

    private function validPayload(MediaProgram $program, array $overrides = []): array
    {
        return array_merge([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'slug' => 'test-episode',
            'video_url' => 'https://www.youtube.com/embed/test',
            'duration_seconds' => 900,
            'title' => ['ar' => 'حلقة جديدة', 'en' => 'New Episode'],
            'description' => ['ar' => '<p>وصف الحلقة</p>'],
            'transcript' => ['ar' => '00:00 مقدمة'],
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Authorization
    // ------------------------------------------------------------------

    public function test_guest_is_redirected_from_episodes_index(): void
    {
        $this->get('/su/portal/v1/admin/media-episodes')->assertRedirect();
    }

    public function test_non_admin_cannot_access_episodes_index(): void
    {
        $this->actingAs($this->createUser())
            ->get('/su/portal/v1/admin/media-episodes')
            ->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Index: ordering, search, filters, stats
    // ------------------------------------------------------------------

    public function test_admin_sees_episodes_list_ordered_newest_first(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $older = $this->createEpisode($program, 1, ['created_at' => now()->subDays(5)]);
        $newer = $this->createEpisode($program, 2, ['created_at' => now()]);

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-episodes');

        $response->assertOk();
        $listed = $response->viewData('episodes');
        $this->assertSame($newer->id, $listed->first()->id);
        $this->assertSame($older->id, $listed->last()->id);
    }

    public function test_program_filter_returns_natural_season_episode_order(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $other = $this->createProgram();
        $second = $this->createEpisode($program, 2, ['created_at' => now()]);
        $first = $this->createEpisode($program, 1, ['created_at' => now()->subDay()]);
        $this->createEpisode($other, 1);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes?program_id='.$program->id);

        $response->assertOk();
        $listed = $response->viewData('episodes');
        $this->assertCount(2, $listed);
        $this->assertSame($first->id, $listed->first()->id);
        $this->assertSame($second->id, $listed->last()->id);
    }

    public function test_admin_can_search_episodes_by_title(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $match = $this->createEpisode($program, 1, [], 'حلقة فريدة للبحث');
        $this->createEpisode($program, 2);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes?q='.urlencode('فريدة للبحث'));

        $response->assertOk();
        $listed = $response->viewData('episodes');
        $this->assertCount(1, $listed);
        $this->assertSame($match->id, $listed->first()->id);
    }

    public function test_index_rejects_unknown_sort_column(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes?sort=slug')
            ->assertSessionHasErrors('sort');
    }

    public function test_index_exposes_aggregate_stats(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $this->createEpisode($program, 1, ['is_featured' => true, 'views_count' => 100, 'duration_seconds' => 3600]);
        $this->createEpisode($program, 2, ['views_count' => 50, 'duration_seconds' => 1800]);

        $response = $this->actingAs($admin)->get('/su/portal/v1/admin/media-episodes');

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['featured']);
        $this->assertSame(150, $stats['total_views']);
        $this->assertSame(5400, $stats['total_duration_seconds']);
    }

    public function test_json_picker_excludes_program_and_searches(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createProgram();
        $source = $this->createProgram();
        $this->createEpisode($target, 1, [], 'حلقة داخل البرنامج');
        $foreign = $this->createEpisode($source, 1, [], 'حلقة خارجية مميزة');

        $response = $this->actingAs($admin)->getJson(
            '/su/portal/v1/admin/media-episodes?exclude_program_id='.$target->id.'&q='.urlencode('مميزة')
        );

        $response->assertOk()->assertJsonPath('success', true);
        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$foreign->id], $ids);
    }

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function test_create_form_renders(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes/create?program_id='.$program->id)
            ->assertOk();
    }

    public function test_admin_can_create_episode_with_translations(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $response = $this->actingAs($admin)
            ->post('/su/portal/v1/admin/media-episodes', $this->validPayload($program));

        $response->assertRedirect('/su/portal/v1/admin/media-episodes?program_id='.$program->id);
        $this->assertDatabaseHas('media_episodes', [
            'program_id' => $program->id,
            'slug' => 'test-episode',
            'episode_number' => 1,
        ]);
        $episode = MediaEpisode::where('slug', 'test-episode')->firstOrFail();
        $this->assertDatabaseHas('media_episode_translations', [
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => 'حلقة جديدة',
        ]);
    }

    public function test_store_requires_episode_number_and_arabic_title(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)
            ->post('/su/portal/v1/admin/media-episodes', $this->validPayload($program, [
                'episode_number' => null,
                'title' => ['ar' => ''],
            ]))
            ->assertSessionHasErrors(['episode_number', 'title.ar']);
    }

    public function test_store_rejects_unknown_thumbnail_library_id(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)
            ->post('/su/portal/v1/admin/media-episodes', $this->validPayload($program, [
                'thumbnail_id' => 999999,
            ]))
            ->assertSessionHasErrors('thumbnail_id');
    }

    public function test_admin_can_update_episode(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);

        $response = $this->actingAs($admin)
            ->put('/su/portal/v1/admin/media-episodes/'.$episode->id, $this->validPayload($program, [
                'slug' => $episode->slug,
                'episode_number' => 7,
                'title' => ['ar' => 'عنوان محدث'],
            ]));

        // Saving an edit returns to the episode details page, not the list.
        $response->assertRedirect('/su/portal/v1/admin/media-episodes/'.$episode->id);
        $this->assertDatabaseHas('media_episodes', ['id' => $episode->id, 'episode_number' => 7]);
        $this->assertDatabaseHas('media_episode_translations', [
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => 'عنوان محدث',
        ]);
    }

    public function test_edit_form_renders(): void
    {
        $admin = $this->createAdminUser();
        $episode = $this->createEpisode($this->createProgram(), 1);

        $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes/'.$episode->id.'/edit')
            ->assertOk();
    }

    public function test_show_page_renders_episode(): void
    {
        $admin = $this->createAdminUser();
        $episode = $this->createEpisode($this->createProgram(), 1, [], 'حلقة للعرض');

        $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes/'.$episode->id)
            ->assertOk()
            ->assertSee('حلقة للعرض');
    }

    public function test_admin_can_delete_episode(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);

        $response = $this->actingAs($admin)
            ->delete('/su/portal/v1/admin/media-episodes/'.$episode->id);

        $response->assertRedirect('/su/portal/v1/admin/media-episodes?program_id='.$program->id);
        $this->assertDatabaseMissing('media_episodes', ['id' => $episode->id]);
    }

    // ------------------------------------------------------------------
    // Publishing & scheduling
    // ------------------------------------------------------------------

    public function test_admin_can_create_scheduled_episode(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)->post('/su/portal/v1/admin/media-episodes', $this->validPayload($program, [
            'is_published' => 0,
            'publish_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]));

        $episode = MediaEpisode::where('slug', 'test-episode')->firstOrFail();
        $this->assertFalse($episode->is_published);
        $this->assertNotNull($episode->publish_at);
        $this->assertSame('scheduled', $episode->publishStatus());
    }

    public function test_admin_can_filter_episodes_by_publish_status(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $this->createEpisode($program, 1, ['is_published' => true]);
        $scheduled = $this->createEpisode($program, 2, ['is_published' => false, 'publish_at' => now()->addDay()]);
        $this->createEpisode($program, 3, ['is_published' => false]);

        $response = $this->actingAs($admin)
            ->get('/su/portal/v1/admin/media-episodes?publish_status=scheduled');

        $response->assertOk();
        $listed = $response->viewData('episodes');
        $this->assertCount(1, $listed);
        $this->assertSame($scheduled->id, $listed->first()->id);
    }

    public function test_publish_command_publishes_only_due_episodes(): void
    {
        $program = $this->createProgram();
        $due = $this->createEpisode($program, 1, ['is_published' => false, 'publish_at' => now()->subMinute()]);
        $future = $this->createEpisode($program, 2, ['is_published' => false, 'publish_at' => now()->addDay()]);
        $draft = $this->createEpisode($program, 3, ['is_published' => false]);

        $this->artisan('media:publish-scheduled')->assertSuccessful();

        $this->assertTrue($due->fresh()->is_published);
        $this->assertFalse($future->fresh()->is_published);
        $this->assertFalse($draft->fresh()->is_published);
    }

    public function test_unpublished_episode_is_hidden_from_public_until_published(): void
    {
        $program = $this->createProgram();
        $this->createEpisode($program, 1, ['is_published' => false, 'publish_at' => now()->subMinute()]);

        $this->get('/media/'.$program->slug.'/episodes/1')->assertNotFound();

        $this->artisan('media:publish-scheduled')->assertSuccessful();

        $this->get('/media/'.$program->slug.'/episodes/1')->assertOk();
    }

    // ------------------------------------------------------------------
    // SEO
    // ------------------------------------------------------------------

    public function test_admin_can_save_episode_seo_fields_per_locale(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();

        $this->actingAs($admin)->post('/su/portal/v1/admin/media-episodes', $this->validPayload($program, [
            'seo_title' => ['ar' => 'عنوان سيو مخصص'],
            'seo_description' => ['ar' => 'وصف سيو مخصص للحلقة'],
            'seo_keywords' => ['ar' => 'حلقة, سيرة, برنامج'],
        ]));

        $episode = MediaEpisode::where('slug', 'test-episode')->firstOrFail();
        $this->assertDatabaseHas('media_episode_translations', [
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'seo_title' => 'عنوان سيو مخصص',
            'seo_description' => 'وصف سيو مخصص للحلقة',
            'seo_keywords' => 'حلقة, سيرة, برنامج',
        ]);
    }

    public function test_public_episode_page_uses_seo_title_override(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);
        $episode->translations()->where('locale', 'ar')->update([
            'seo_title' => 'عنوان سيو للحلقة',
            'seo_keywords' => 'كلمات, مفتاحية',
        ]);

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertOk()
            ->assertSee('<title>عنوان سيو للحلقة</title>', false)
            ->assertSee('<meta name="keywords" content="كلمات, مفتاحية">', false);
    }

    public function test_admin_update_invalidates_cached_public_episode_page(): void
    {
        $admin = $this->createAdminUser();
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);

        // Populate the per-number public cache first.
        $this->get('/media/'.$program->slug.'/episodes/1')->assertOk();

        $this->actingAs($admin)
            ->put('/su/portal/v1/admin/media-episodes/'.$episode->id, $this->validPayload($program, [
                'slug' => $episode->slug,
                'is_published' => 1,
                'seo_title' => ['ar' => 'عنوان محدث بعد الكاش'],
            ]));

        $this->get('/media/'.$program->slug.'/episodes/1')
            ->assertOk()
            ->assertSee('<title>عنوان محدث بعد الكاش</title>', false);
    }

    public function test_public_episode_page_falls_back_to_episode_and_program_title(): void
    {
        $program = $this->createProgram('برنامج الاختبار');
        $this->createEpisode($program, 1, [], 'حلقة الاختبار');

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertOk()
            ->assertSee('<title>حلقة الاختبار — برنامج الاختبار</title>', false);
    }

    // ------------------------------------------------------------------
    // Hero thumbnail change/remove
    // ------------------------------------------------------------------

    public function test_admin_can_change_thumbnail_from_hero(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = $this->createAdminUser();
        $episode = $this->createEpisode($this->createProgram(), 1);

        $response = $this->actingAs($admin)->post(
            '/su/portal/v1/admin/media-episodes/'.$episode->id.'/thumbnail',
            ['thumbnail' => \Illuminate\Http\UploadedFile::fake()->image('thumb.jpg', 800, 450)]
        );

        $response->assertRedirect('/su/portal/v1/admin/media-episodes/'.$episode->id);
        $this->assertCount(1, $episode->fresh()->getMedia('thumbnail'));
    }

    public function test_admin_can_remove_thumbnail_from_hero(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = $this->createAdminUser();
        $episode = $this->createEpisode($this->createProgram(), 1);
        $episode->addMedia(\Illuminate\Http\UploadedFile::fake()->image('thumb.jpg', 800, 450))
            ->toMediaCollection('thumbnail');

        $response = $this->actingAs($admin)->post(
            '/su/portal/v1/admin/media-episodes/'.$episode->id.'/thumbnail',
            ['remove' => 1]
        );

        $response->assertRedirect('/su/portal/v1/admin/media-episodes/'.$episode->id);
        $this->assertCount(0, $episode->fresh()->getMedia('thumbnail'));
    }

    public function test_thumbnail_endpoint_rejects_missing_file_without_remove_flag(): void
    {
        $admin = $this->createAdminUser();
        $episode = $this->createEpisode($this->createProgram(), 1);

        $this->actingAs($admin)
            ->post('/su/portal/v1/admin/media-episodes/'.$episode->id.'/thumbnail', [])
            ->assertSessionHasErrors('thumbnail');
    }

    // ------------------------------------------------------------------
    // WYSIWYG transcript
    // ------------------------------------------------------------------

    public function test_public_episode_page_renders_html_transcript_lines(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1);
        $episode->translations()->where('locale', 'ar')->update([
            'transcript' => '<p>00:15 مقدمة الحلقة</p><p>سطر بدون توقيت</p>',
        ]);

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertOk()
            ->assertSee('00:15')
            ->assertSee('مقدمة الحلقة')
            ->assertSee('سطر بدون توقيت')
            ->assertDontSee('&lt;p&gt;');
    }
}
