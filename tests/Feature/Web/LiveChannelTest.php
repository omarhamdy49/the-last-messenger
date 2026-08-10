<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Media\Models\LiveChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Live channels: public row on /media, the single channel page, visibility
 * rules, and admin CRUD.
 */
final class LiveChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createChannel(array $overrides = [], string $title = 'قناة تجريبية مباشرة'): LiveChannel
    {
        $channel = LiveChannel::create(array_merge([
            'slug' => 'live-'.uniqid(),
            'streaming_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'status' => LiveStatus::Live->value,
            'is_published' => true,
            'display_order' => 0,
            'views_count' => 0,
        ], $overrides));

        $channel->translations()->create([
            'locale' => 'ar',
            'title' => $title,
            'description' => 'وصف القناة المباشرة للاختبار',
            'is_ready' => true,
        ]);

        return $channel;
    }

    // ===================== PUBLIC ROW ON /media =====================

    public function test_media_hub_promotes_a_single_live_channel_via_cta_banner(): void
    {
        $this->createChannel(title: 'قناة القرآن المباشرة');

        $response = $this->get('/media');

        $response->assertStatus(200);
        // One channel → CTA banner (no row); the row needs 2+ channels.
        $response->assertSee('قناة القرآن المباشرة');
        $response->assertSee(trans('media.public.watch_live'));
        $response->assertDontSee(trans('media.public.live_channels'));
    }

    public function test_media_hub_shows_live_channels_row_with_multiple_channels(): void
    {
        $this->createChannel(['slug' => 'live-a'], 'قناة القرآن المباشرة');
        $this->createChannel(['slug' => 'live-b'], 'قناة المدينة المباشرة');

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.live_channels'));
        $response->assertSee('قناة القرآن المباشرة');
        $response->assertSee('قناة المدينة المباشرة');
    }

    public function test_unpublished_live_channel_is_hidden_from_hub(): void
    {
        $this->createChannel(['is_published' => false], 'قناة مخفية');

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertDontSee('قناة مخفية');
    }

    // ===================== SINGLE CHANNEL PAGE =====================

    public function test_single_live_channel_page_renders(): void
    {
        $channel = $this->createChannel(title: 'بث مباشر للاختبار');

        $response = $this->get(route('public.media.live', $channel->slug));

        $response->assertStatus(200);
        $response->assertSee('بث مباشر للاختبار');
        // Embed URL is rendered in the iframe.
        $response->assertSee('youtube.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_single_live_channel_shows_related_channels(): void
    {
        $main = $this->createChannel(title: 'القناة الرئيسية');
        $this->createChannel(title: 'قناة ذات صلة');

        $response = $this->get(route('public.media.live', $main->slug));

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.related_channels'));
        $response->assertSee('قناة ذات صلة');
    }

    public function test_unpublished_single_live_channel_returns_404(): void
    {
        $channel = $this->createChannel(['is_published' => false]);

        $this->get(route('public.media.live', $channel->slug))->assertStatus(404);
    }

    public function test_unknown_live_channel_slug_returns_404(): void
    {
        $this->get(route('public.media.live', 'does-not-exist'))->assertStatus(404);
    }

    public function test_visiting_single_live_channel_increments_views(): void
    {
        $channel = $this->createChannel(['views_count' => 5]);

        $this->get(route('public.media.live', $channel->slug))->assertStatus(200);

        $this->assertSame(6, $channel->fresh()->views_count);
    }

    // ===================== ADMIN CRUD =====================

    public function test_non_admin_cannot_access_admin_live_channels(): void
    {
        // The admin guard redirects non-admins (with a flash message) rather
        // than returning a bare 403.
        $this->actingAs($this->createUser())
            ->get('/su/portal/v1/admin/live-channels')
            ->assertRedirect();
    }

    public function test_admin_can_list_live_channels(): void
    {
        $this->createChannel(title: 'قناة في القائمة');

        $this->actingAs($this->createAdminUser())
            ->get('/su/portal/v1/admin/live-channels')
            ->assertStatus(200)
            ->assertSee('قناة في القائمة');
    }

    public function test_admin_can_create_live_channel_with_auto_slug(): void
    {
        $response = $this->actingAs($this->createAdminUser())
            ->post('/su/portal/v1/admin/live-channels', [
                'title' => ['ar' => 'قناة جديدة', 'en' => 'New Channel'],
                'description' => ['ar' => 'الوصف'],
                'streaming_url' => 'https://www.youtube.com/embed/abc12345678',
                'status' => 'live',
                'is_published' => '1',
                'display_order' => '0',
            ]);

        $response->assertRedirect(route('admin.live-channels.index'));
        // Slug is auto-generated from the title (Arabic transliterated); assert
        // the channel + translation exist rather than a hard-coded slug.
        $this->assertDatabaseHas('live_channels', ['status' => 'live', 'is_published' => true]);
        $this->assertDatabaseHas('live_channel_translations', ['locale' => 'ar', 'title' => 'قناة جديدة']);
        $channel = LiveChannel::firstWhere('status', 'live');
        $this->assertNotEmpty($channel->slug);
    }

    public function test_store_validation_requires_arabic_title(): void
    {
        $this->actingAs($this->createAdminUser())
            ->post('/su/portal/v1/admin/live-channels', [
                'title' => ['en' => 'Only English'],
                'status' => 'live',
            ])
            ->assertSessionHasErrors('title.ar');
    }

    public function test_admin_can_update_live_channel(): void
    {
        $channel = $this->createChannel();

        $this->actingAs($this->createAdminUser())
            ->put('/su/portal/v1/admin/live-channels/'.$channel->id, [
                'slug' => $channel->slug,
                'title' => ['ar' => 'عنوان محدّث'],
                'status' => 'offline',
                'is_published' => '1',
                'display_order' => '3',
            ])
            ->assertRedirect(route('admin.live-channels.show', $channel->id));

        $this->assertDatabaseHas('live_channels', ['id' => $channel->id, 'status' => 'offline', 'display_order' => 3]);
        $this->assertDatabaseHas('live_channel_translations', ['live_channel_id' => $channel->id, 'title' => 'عنوان محدّث']);
    }

    public function test_admin_can_delete_live_channel(): void
    {
        $channel = $this->createChannel();

        $this->actingAs($this->createAdminUser())
            ->delete('/su/portal/v1/admin/live-channels/'.$channel->id)
            ->assertRedirect(route('admin.live-channels.index'));

        $this->assertDatabaseMissing('live_channels', ['id' => $channel->id]);
    }
}
