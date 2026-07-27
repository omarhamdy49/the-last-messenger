<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

final class SeerahPublicVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [SeerahEvent::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query
            ->published()
            ->whereHas('translations', fn (Builder $translationQuery) => $translationQuery
                ->where('locale', $locale)
                ->ready());
    }
}
