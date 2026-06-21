<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\ArticleRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publishes articles whose scheduled_at has passed (status: scheduled → published).
 * Scheduled to run every minute.
 */
class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';

    protected $description = 'Publish articles whose scheduled publish time has passed';

    public function handle(ArticleRepository $articles): int
    {
        try {
            $count = $articles->publishDueScheduled();
        } catch (Throwable $e) {
            $this->error('Failed to publish scheduled articles: '.$e->getMessage());
            Log::error('articles:publish-scheduled failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        if ($count > 0) {
            $this->info("Published {$count} scheduled article(s).");
            Log::info('articles:publish-scheduled published articles', ['count' => $count]);
        }

        return self::SUCCESS;
    }
}
