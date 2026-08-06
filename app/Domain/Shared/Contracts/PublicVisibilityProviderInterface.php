<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface PublicVisibilityProviderInterface
{
    /**
     * @return list<class-string<Model>>
     */
    public function modelClasses(): array;

    public function apply(Builder $query, string $locale): Builder;
}
