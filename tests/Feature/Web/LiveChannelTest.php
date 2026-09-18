<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Media\Enums\StreamType;
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

    // ===================== LIVE CHANNELS LIST PAGE =====================

    public function test_live_channels_list_page_shows_published_and_hides_unpublished(): void
    {
        $this->createChannel(['slug' => 'live-a'], 'قناة القرآن المباشرة');
        $this->createChannel(['slug' => 'live-b', 'is_published' => false], 'قناة مخفية');

        $response = $this->get(route('public.media.live.index'));

        $response->assertStatus(200);
        $response->assertSee('قناة القرآن المباشرة');
        $response->assertDontSee('قناة مخفية');
    }

    public function test_media_hub_live_row_links_to_the_list_page(): void
    {
        $this->createChannel(['slug' => 'live-a'], 'قناة القرآن المباشرة');
        $this->createChannel(['slug' => 'live-b'], 'قناة المدينة المباشرة');

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee(route('public.media.live.index'), false);
    }

    // ===================== EMBED URL NORMALIZATION =====================

    public function test_embed_url_normalizes_youtube_variants_and_passes_through_others(): void
    {
        $channel = new LiveChannel;
        $expected = 'https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1';

        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/live/dQw4w9WgXcQ',
        ] as $url) {
            $channel->streaming_url = $url;
            $this->assertSame($expected, $channel->embed_url, "for {$url}");
        }

        $channel->streaming_url = 'https://example.com/stream.m3u8';
        $this->assertSame('https://example.com/stream.m3u8', $channel->embed_url);

        $channel->streaming_url = null;
        $this->assertNull($channel->embed_url);

        $channel->streaming_url = '   ';
        $this->assertNull($channel->embed_url);
    }

    // ===================== ADMIN STREAM TYPE ROUND TRIP =====================

    public function test_admin_create_and_edit_forms_render_the_stream_type_field(): void
    {
        $this->actingAs($this->createAdminUser());
        $channel = $this->createChannel(['slug' => 'form-render']);

        foreach ([
            route('admin.live-channels.create'),
            route('admin.live-channels.edit', $channel->id),
        ] as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertSee('name="stream_type"', false);
        }
    }

    public function test_admin_can_store_an_explicit_stream_type(): void
    {
        $this->actingAs($this->createAdminUser());

        // A radio mount: nothing in the URL says "audio", so the editor's
        // explicit choice is the only thing that makes it play correctly.
        $response = $this->post(route('admin.live-channels.store'), [
            'title' => ['ar' => 'إذاعة تجريبية'],
            'streaming_url' => 'https://n0d.radiojar.com/0tpy1h0kxtzuv',
            'stream_type' => 'audio',
            'status' => 'live',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.live-channels.index'));
        $this->assertDatabaseHas('live_channels', ['stream_type' => 'audio']);

        $channel = LiveChannel::where('stream_type', StreamType::Audio)->firstOrFail();
        $this->assertTrue($channel->stream_source['audio_only']);
    }

    public function test_blank_stream_type_is_stored_as_auto_not_as_a_guess(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.live-channels.store'), [
            'title' => ['ar' => 'قناة تلقائية'],
            'streaming_url' => 'https://win.holol.com/live/quran/playlist.m3u8',
            'stream_type' => '',
            'status' => 'live',
            'is_published' => '1',
        ])->assertRedirect();

        $channel = LiveChannel::where('slug', 'qnat-tlqayy')->first()
            ?? LiveChannel::latest('id')->firstOrFail();

        // Stored as NULL so a later URL change re-detects, but reads as HLS now.
        $this->assertNull($channel->stream_type);
        $this->assertSame(StreamType::Hls, $channel->resolved_stream_type);
    }

    public function test_admin_rejects_an_unknown_stream_type(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.live-channels.store'), [
            'title' => ['ar' => 'قناة'],
            'streaming_url' => 'https://example.com/x.m3u8',
            'stream_type' => 'rtmp',
            'status' => 'live',
        ])->assertSessionHasErrors('stream_type');
    }

    // ===================== STREAM TYPE RESOLUTION =====================

    public function test_stream_type_is_detected_from_the_url_when_left_on_auto(): void
    {
        $channel = new LiveChannel;

        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => StreamType::YouTube,
            'https://youtu.be/dQw4w9WgXcQ' => StreamType::YouTube,
            'https://www.youtube.com/live/dQw4w9WgXcQ' => StreamType::YouTube,
            'https://win.holol.com/live/quran/playlist.m3u8' => StreamType::Hls,
            'https://example.com/stream.mpd' => StreamType::Dash,
            'https://example.com/clip.mp4' => StreamType::File,
            'https://example.com/radio.mp3' => StreamType::Audio,
            // A radio mount point carries no extension at all: nothing in the URL
            // says "audio", which is exactly why the editor can override.
            'https://n0d.radiojar.com/0tpy1h0kxtzuv?rj-ttl=5' => StreamType::Iframe,
        ] as $url => $expected) {
            $channel->streaming_url = $url;
            $channel->stream_type = null;
            $this->assertSame($expected, $channel->resolved_stream_type, "for {$url}");
        }
    }

    public function test_query_string_does_not_fool_extension_detection(): void
    {
        $channel = new LiveChannel;
        // The token below ends in ".mpd" but lives in the query, not the path.
        $channel->streaming_url = 'https://cdn.example.com/live/index.m3u8?token=abc.mpd';

        $this->assertSame(StreamType::Hls, $channel->resolved_stream_type);
    }

    public function test_explicit_stream_type_overrides_detection(): void
    {
        $channel = new LiveChannel;
        $channel->streaming_url = 'https://n0d.radiojar.com/0tpy1h0kxtzuv?rj-ttl=5';
        $channel->stream_type = StreamType::Audio;

        $source = $channel->stream_source;

        $this->assertSame('audio', $source['type']);
        $this->assertSame('media', $source['engine']);
        $this->assertTrue($source['audio_only']);
        // The engine needs the manifest/mount URL untouched, not an embed form.
        $this->assertSame('https://n0d.radiojar.com/0tpy1h0kxtzuv?rj-ttl=5', $source['url']);
        $this->assertNull($source['video_id']);
    }

    public function test_youtube_stream_source_carries_the_video_id_for_the_sdk(): void
    {
        $channel = new LiveChannel;
        $channel->streaming_url = 'https://www.youtube.com/live/dQw4w9WgXcQ';

        $source = $channel->stream_source;

        $this->assertSame('youtube', $source['engine']);
        $this->assertSame('dQw4w9WgXcQ', $source['video_id']);
        $this->assertFalse($source['audio_only']);
    }

    public function test_stream_source_is_null_without_a_url(): void
    {
        $channel = new LiveChannel;

        $channel->streaming_url = null;
        $this->assertNull($channel->stream_source);

        $channel->streaming_url = '   ';
        $this->assertNull($channel->stream_source);
    }

    // ===================== SINGLE CHANNEL PAGE =====================

    public function test_single_live_channel_page_renders(): void
    {
        $channel = $this->createChannel(title: 'بث مباشر للاختبار');

        $response = $this->get(route('public.media.live', $channel->slug));

        $response->assertStatus(200);
        $response->assertSee('بث مباشر للاختبار');
        // The page now mounts the wrapped player rather than a bare YouTube
        // iframe: the video id is handed to our own chrome, and none of
        // YouTube's embed UI is reachable.
        $response->assertSee('streamPlayer(', false);
        $response->assertSee('dQw4w9WgXcQ', false);
        $response->assertDontSee('<iframe src="https://www.youtube.com/embed/', false);
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
