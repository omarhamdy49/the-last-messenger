<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

final class HadithPublicVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [Hadith::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query;
    }
}
