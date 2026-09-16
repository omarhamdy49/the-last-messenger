<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Observers;

use App\Domain\Hadith\Services\HadithBrowseService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class HadithCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(): void
    {
        HadithBrowseService::forget();
    }

    public function deleted(): void
    {
        HadithBrowseService::forget();
    }
}
