<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\LanguageCacheService;
use Illuminate\Support\Facades\Log;

/**
 * Language Observer
 *
 * Automatically invalidates language cache when language records
 * are created, updated, or deleted to ensure cache consistency.
 */
class LanguageObserver
{
    /**
     * Language cache service instance
     *
     * @var LanguageCacheService
     */
    protected LanguageCacheService $cacheService;

    /**
     * Create a new observer instance
     *
     * @param LanguageCacheService $cacheService
     */
    public function __construct(LanguageCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Language "created" event.
     *
     * @param Language $language
     * @return void
     */
    public function created(Language $language): void
    {
        $this->invalidateCache('created', $language);
    }

    /**
     * Handle the Language "updated" event.
     *
     * @param Language $language
     * @return void
     */
    public function updated(Language $language): void
    {
        $this->invalidateCache('updated', $language);
    }

    /**
     * Handle the Language "deleted" event.
     *
     * @param Language $language
     * @return void
     */
    public function deleted(Language $language): void
    {
        $this->invalidateCache('deleted', $language);
    }

    /**
     * Handle the Language "restored" event.
     *
     * @param Language $language
     * @return void
     */
    public function restored(Language $language): void
    {
        $this->invalidateCache('restored', $language);
    }

    /**
     * Handle the Language "force deleted" event.
     *
     * @param Language $language
     * @return void
     */
    public function forceDeleted(Language $language): void
    {
        $this->invalidateCache('force_deleted', $language);
    }

    /**
     * Invalidate language cache and log the action
     *
     * @param string $action The action that triggered cache invalidation
     * @param Language $language The language model affected
     * @return void
     */
    protected function invalidateCache(string $action, Language $language): void
    {
        try {
            $this->cacheService->clearCache();

            Log::info('Language cache invalidated', [
                'action' => $action,
                'language_code' => $language->code,
                'language_name' => $language->name,
                'language_id' => $language->id,
            ]);

            // Optionally warm the cache immediately for critical data
            if (in_array($action, ['created', 'updated', 'restored'])) {
                $this->cacheService->warmCache();
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate language cache', [
                'action' => $action,
                'language_code' => $language->code,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
