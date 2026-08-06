<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Gates `muslims` on every shared public read path — notably cross-module
 * search, which would otherwise expose unpublished people.
 *
 * Genealogy used to be exempt from this gating. That exemption is gone: a
 * person is now an ordinary published/unpublished record like any other
 * content. Unpublished people remain selectable in the ADMIN picker, which is
 * a separate, authorized surface.
 */
final class MuslimPublicVisibility implements PublicVisibilityProviderInterface
{
    /** @return list<class-string<Model>> */
    public function modelClasses(): array
    {
        return [Muslim::class];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query->where($query->getModel()->getTable().'.is_published', true);
    }
}
