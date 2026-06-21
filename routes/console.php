<?php

declare(strict_types=1);

use App\Domain\Shared\Services\SchedulerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
    } catch (\Exception $e) {
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
        \Illuminate\Support\Facades\Log::info('Scheduled content processed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Failed to process scheduled content');
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
        \Illuminate\Support\Facades\Log::error('Failed to publish scheduled media programs');
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
        \Illuminate\Support\Facades\Log::error('Failed to publish scheduled articles');
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
        \Illuminate\Support\Facades\Log::error('Failed to prune Ask Quran AI inquiries');
    });
