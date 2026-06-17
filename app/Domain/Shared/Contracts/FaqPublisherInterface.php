<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\ValueObjects\FaqDraft;

/**
 * Publishes a FAQ from a draft. Implemented by the Content domain (FaqService)
 * and consumed by other domains (e.g. Quran "Ask AI" moderation) through this
 * Shared contract — so no domain has to import another domain directly.
 */
interface FaqPublisherInterface
{
    /**
     * Create a FAQ from the draft and return the new FAQ id.
     */
    public function publish(FaqDraft $draft): int;

    /**
     * Existing FAQ categories, for assigning one when publishing.
     *
     * @return list<string>
     */
    public function categories(): array;
}
