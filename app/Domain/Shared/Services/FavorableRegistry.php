<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\FavorableRegistryInterface;
use App\Domain\Shared\Enums\FavorableType;
use Illuminate\Database\Eloquent\Model;

/**
 * In-memory implementation of the favorable registry.
 *
 * Bindings are added once at application boot. Kept deliberately free of any
 * domain-module or app-model imports so the Shared layer stays a leaf node.
 */
final class FavorableRegistry implements FavorableRegistryInterface
{
    /** @var array<string, class-string<Model>> slug => morph class */
    private array $modelClasses = [];

    /** @var array<string, callable(Model, string): array{title: string, excerpt: ?string, url: ?string}> */
    private array $displayMappers = [];

    public function register(FavorableType $type, string $modelClass, callable $displayMapper): void
    {
        $this->modelClasses[$type->value] = $modelClass;
        $this->displayMappers[$type->value] = $displayMapper;
    }

    public function modelClass(FavorableType $type): ?string
    {
        return $this->modelClasses[$type->value] ?? null;
    }

    public function typeForModelClass(string $modelClass): ?FavorableType
    {
        foreach ($this->modelClasses as $slug => $class) {
            if ($class === $modelClass) {
                return FavorableType::from($slug);
            }
        }

        return null;
    }

    public function display(FavorableType $type, Model $model, string $locale): array
    {
        $mapper = $this->displayMappers[$type->value] ?? null;

        if ($mapper === null) {
            return ['title' => '', 'excerpt' => null, 'url' => null];
        }

        return $mapper($model, $locale);
    }
}
