<?php

declare(strict_types=1);

use App\Domain\Media\Models\MediaViewEvent;
use App\Domain\Shared\Models\AdminLog;
use App\Domain\Shared\Services\SchedulerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Content Scheduler Command
 * Processes scheduled content for publishing/unpublishing/updating
 * Runs every 5 minutes
 */
Artisan::command('content:process-scheduled', function () {
    $this->info('Processing scheduled content...');

    $schedulerService = app(SchedulerService::class);

    try {
        $results = $schedulerService->processScheduledContent();

        $this->info("Processed: {$results['processed']}");
        $this->info("Success: {$results['success']}");

        if ($results['failed'] > 0) {
            $this->warn("Failed: {$results['failed']}");

            foreach ($results['errors'] as $error) {
                $this->error("Schedule #{$error['schedule_id']}: {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('✓ Content scheduling processing completed successfully');

        return 0;
    } catch (Exception $e) {
        $this->error('Failed to process scheduled content: '.$e->getMessage());

        return 1;
    }
})->purpose('Process scheduled content for publishing');

/**
 * Schedule the content processor to run every 5 minutes
 */
Schedule::command('content:process-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('Scheduled content processed successfully');
    })
    ->onFailure(function () {
        Log::error('Failed to process scheduled content');
    });

/**
 * Publish media programs whose scheduled publish_at has passed.
 * Runs hourly.
 */
Schedule::command('media:publish-scheduled')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to publish scheduled media programs');
    });

/**
 * Publish articles whose scheduled publish time has passed (status scheduled → published).
 * Runs every minute so scheduled articles go live close to their chosen time.
 */
Schedule::command('articles:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to publish scheduled articles');
    });

/**
 * Prune media view events older than 90 days (the analytics windows only need
 * 30). Batched deletes so a large backlog never holds one long transaction.
 *
 * Deliberately NOT scheduled: run it by hand from Settings → Commands, where
 * the run is queued and its progress/output are visible.
 */
Artisan::command('media:prune-view-events', function () {
    $cutoff = now()->subDays(90);
    $total = 0;

    do {
        $deleted = MediaViewEvent::query()
            ->where('created_at', '<', $cutoff)
            ->limit(5000)
            ->delete();
        $total += $deleted;
    } while ($deleted > 0);

    $this->info("Pruned {$total} media view events older than {$cutoff->toDateString()}.");

    return 0;
})->purpose('Prune media view events older than 90 days');

/**
 * Prune admin activity logs (the `admin_logs` table behind Settings → Activity
 * Logs). Every admin write appends a row with old/new value snapshots, so the
 * table grows with editing activity and nothing else trims it.
 *
 * Scheduled nightly. Batched like the view-event prune so a large backlog never
 * holds one long transaction while the admin panel is still writing to it.
 */
Artisan::command('admin-logs:prune {--days=7 : Keep logs newer than N days}', function () {
    $cutoff = now()->subDays(max(1, (int) $this->option('days')));
    $total = 0;

    do {
        $deleted = AdminLog::query()
            ->where('created_at', '<', $cutoff)
            ->limit(5000)
            ->delete();
        $total += $deleted;
    } while ($deleted > 0);

    $this->info("Pruned {$total} admin activity logs older than {$cutoff->toDateTimeString()}.");

    return 0;
})->purpose('Prune admin activity logs older than 7 days');

Schedule::command('admin-logs:prune')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to prune admin activity logs');
    });

/**
 * Recompute media trending scores from real view events (last 7 days).
 * Twice daily (01:00 / 13:00); also drives the /media Trending row cache
 * refresh. The window is 7 days, so scores barely move inside 12 hours —
 * recomputing more often just re-reads the same events.
 */
Schedule::command('media:compute-trending')
    ->twiceDaily()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to compute media trending scores');
    });

// quran:ai-prune (prunes Ask Quran inquiries + stale quota rows) is manual-only
// too — see Settings → Commands. It takes --dry-run, so preview before deleting.
