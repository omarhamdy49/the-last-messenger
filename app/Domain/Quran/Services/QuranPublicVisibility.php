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

    public function apply(Builder $query, string $locale): Builder
    {
        return $query;
    }
}
