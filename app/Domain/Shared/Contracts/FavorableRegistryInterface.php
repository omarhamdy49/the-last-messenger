<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Enums\FavorableType;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry that maps bookmarkable content types to their backing model classes
 * and view-display mappers, without the Shared layer importing any domain
 * module. Concrete bindings are registered at boot (Bootstrap layer).
 *
 * This is the cross-module composition seam for the favorites feature: it keeps
 * the favorites service generic while each module contributes how its own
 * content is resolved and rendered.
 */
interface FavorableRegistryInterface
{
    /**
     * Register a content type.
     *
     * @param  class-string<Model>  $modelClass  The polymorphic morph class stored in `favorites.favorable_type`.
     * @param  callable(Model, string): array{title: string, excerpt: ?string, url: ?string}  $displayMapper
     *                                                                                                        Maps a loaded model + locale to a flat display payload.
     */
    public function register(FavorableType $type, string $modelClass, callable $displayMapper): void;

    /**
     * Resolve the morph class string for a content type, or null if unregistered.
     *
     * @return class-string<Model>|null
     */
    public function modelClass(FavorableType $type): ?string;

    /**
     * Resolve the content type for a stored morph class, or null if the class
     * is not whitelisted (e.g. an orphaned/deprecated favorite).
     */
    public function typeForModelClass(string $modelClass): ?FavorableType;

    /**
     * Build the flat display payload for a loaded favorable model.
     *
     * @return array{title: string, excerpt: ?string, url: ?string}
     */
    public function display(FavorableType $type, Model $model, string $locale): array;
}
