<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

final class PublicVisibilityRegistry
{
    /** @var array<class-string, PublicVisibilityProviderInterface> */
    private array $providers = [];

    /**
     * @param  iterable<PublicVisibilityProviderInterface>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            foreach ($provider->modelClasses() as $modelClass) {
                if (isset($this->providers[$modelClass])) {
                    throw new LogicException("Multiple public visibility providers registered for [{$modelClass}].");
                }

                $this->providers[$modelClass] = $provider;
            }
        }
    }

    public function apply(Builder $query, string $locale): Builder
    {
        $modelClass = $query->getModel()::class;
        $provider = $this->providers[$modelClass]
            ?? throw new LogicException("No public visibility provider registered for [{$modelClass}].");

        return $provider->apply($query, $locale);
    }
}
