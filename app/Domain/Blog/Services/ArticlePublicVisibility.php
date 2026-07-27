<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ArticlePublicVisibility implements PublicVisibilityProviderInterface
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function modelClasses(): array
    {
        return [$this->modelClass];
    }

    public function apply(Builder $query, string $locale): Builder
    {
        return $query
            ->where('status', ArticleStatus::PUBLISHED->value)
            ->whereHas('locales', fn (Builder $localeQuery) => $localeQuery
                ->where('locale', $locale)
                ->where('is_ready', true));
    }
}
