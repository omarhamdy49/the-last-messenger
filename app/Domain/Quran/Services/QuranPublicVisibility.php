<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

final class QuranPublicVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [Verse::class];
    }

    /**
     * A verse is public when its chapter is. Verses carry no publish flag of their
     * own, so an unpublished chapter must not leak through a topic or a bookmark.
     */
    public function apply(Builder $query, string $locale): Builder
    {
        return $query->whereHas('chapter', static fn (Builder $chapter): Builder => $chapter->where('is_published', true));
    }
}
