<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

final class SahabaPublicVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [Sahabi::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', fn (Builder $translationQuery) => $translationQuery
            ->where('locale', $locale)
            ->ready());
    }
}
