<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface PublicVisibilityProviderInterface
{
    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function modelClasses(): array;

    public function apply(Builder $query, string $locale): Builder;
}
