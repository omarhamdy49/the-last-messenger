<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;

final class MediaPublicVisibility implements PublicVisibilityProviderInterface
{
    public function modelClasses(): array
    {
        return [MediaProgram::class, MediaEpisode::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query->published();
    }
}
