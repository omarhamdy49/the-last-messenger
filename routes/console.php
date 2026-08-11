<?php

declare(strict_types=1);

use App\Domain\Media\Models\MediaViewEvent;
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

Schedule::command('media:prune-view-events')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to prune media view events');
    });

/**
 * Recompute media trending scores from real view events (last 7 days).
 * Runs every 30 minutes; also drives the /media Trending row cache refresh.
 */
Schedule::command('media:compute-trending')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to compute media trending scores');
    });

/**
 * Prune old Ask Quran AI inquiries + stale quota rows so saved questions don't
 * grow disk/queries unbounded. Runs daily off-peak; deletes are batched.
 */
Schedule::command('quran:ai-prune')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        Log::error('Failed to prune Ask Quran AI inquiries');
    });
