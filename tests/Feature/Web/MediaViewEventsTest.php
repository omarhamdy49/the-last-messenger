<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\Enums\MediaViewableType;
use App\Jobs\RecordMediaViewJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Covers the queued, deduplicated view-event pipeline: dispatch on page
 * render, per-visitor/hour dedupe before dispatch, the job's event insert +
 * counter increment, and event pruning.
 */
final class MediaViewEventsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgram(string $slug): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_program_translations')->insert([
            'media_program_id' => $programId,
            'locale' => 'ar',
            'title' => 'برنامج المشاهدات',
            'description' => 'وصف تجريبي',
            'host' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    private function makeLiveChannel(string $slug): int
    {
        $channelId = (int) DB::table('live_channels')->insertGetId([
            'slug' => $slug,
            'streaming_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'status' => 'live',
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('live_channel_translations')->insert([
            'live_channel_id' => $channelId,
            'locale' => 'ar',
            'title' => 'قناة البث التجريبية',
            'description' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $channelId;
    }

    public function test_program_page_dispatches_one_view_job_per_visitor_per_hour(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $programId = $this->makeProgram('viewed-program');

        $this->actingAs($user)->get(route('public.media.program', 'viewed-program'))->assertOk();

        Queue::assertPushed(RecordMediaViewJob::class, function (RecordMediaViewJob $job) use ($programId, $user): bool {
            return $job->type === MediaViewableType::Program
                && $job->viewableId === $programId
                && $job->userId === $user->id;
        });
        Queue::assertPushed(RecordMediaViewJob::class, 1);

        // Second visit inside the dedupe hour: no additional job.
        $this->actingAs($user)->get(route('public.media.program', 'viewed-program'))->assertOk();
        Queue::assertPushed(RecordMediaViewJob::class, 1);
    }

    public function test_live_page_dispatches_a_live_channel_view_job(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $channelId = $this->makeLiveChannel('viewed-live');

        $this->actingAs($user)->get(route('public.media.live', 'viewed-live'))->assertOk();

        Queue::assertPushed(RecordMediaViewJob::class, function (RecordMediaViewJob $job) use ($channelId): bool {
            return $job->type === MediaViewableType::LiveChannel && $job->viewableId === $channelId;
        });
    }

    public function test_guest_view_dispatches_with_null_user_id(): void
    {
        Queue::fake();
        $this->makeProgram('guest-viewed');

        $this->get(route('public.media.program', 'guest-viewed'))->assertOk();

        Queue::assertPushed(RecordMediaViewJob::class, function (RecordMediaViewJob $job): bool {
            return $job->userId === null && $job->visitorHash !== '';
        });
    }

    public function test_job_inserts_event_row_and_increments_views_count(): void
    {
        $programId = $this->makeProgram('counted-program');

        (new RecordMediaViewJob(MediaViewableType::Program, $programId, null, sha1('visitor')))->handle();

        $this->assertDatabaseHas('media_view_events', [
            'viewable_type' => 'program',
            'viewable_id' => $programId,
            'user_id' => null,
            'visitor_hash' => sha1('visitor'),
        ]);
        $this->assertDatabaseHas('media_programs', [
            'id' => $programId,
            'views_count' => 1,
        ]);
    }

    public function test_prune_command_deletes_only_events_older_than_90_days(): void
    {
        $programId = $this->makeProgram('pruned-program');

        DB::table('media_view_events')->insert([
            [
                'viewable_type' => 'program',
                'viewable_id' => $programId,
                'user_id' => null,
                'visitor_hash' => sha1('old'),
                'created_at' => now()->subDays(91),
            ],
            [
                'viewable_type' => 'program',
                'viewable_id' => $programId,
                'user_id' => null,
                'visitor_hash' => sha1('fresh'),
                'created_at' => now()->subDays(5),
            ],
        ]);

        $this->artisan('media:prune-view-events')->assertSuccessful();

        $this->assertSame(1, DB::table('media_view_events')->count());
        $this->assertDatabaseHas('media_view_events', ['visitor_hash' => sha1('fresh')]);
    }
}
