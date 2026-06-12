<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\FavorableRegistryInterface;
use App\Domain\Shared\DTOs\FavoriteItemData;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Repositories\FavoriteRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

/**
 * Favorites (bookmarks) application service.
 *
 * Orchestrates bookmark workflows for the public site: listing a user's
 * bookmarks (flattened into view-ready DTOs), per-type counts, and idempotent
 * add/remove — all strictly scoped to the acting user (IDOR boundary lives in
 * the repository's user_id predicate).
 *
 * Cross-module rendering is delegated to {@see FavorableRegistryInterface} so
 * this service never imports a domain module's models directly.
 */
final class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favorites,
        private readonly FavorableRegistryInterface $registry,
    ) {}

    /**
     * Paginate a user's bookmarks as view-ready DTOs.
     *
     * @return LengthAwarePaginator The items are {@see FavoriteItemData}.
     */
    public function paginateForUser(
        User $user,
        ?FavorableType $type,
        int $perPage,
        string $locale,
    ): LengthAwarePaginator {
        $morphClass = $type !== null ? $this->registry->modelClass($type) : null;

        $paginator = $this->favorites->paginateForUser($user->id, $morphClass, $perPage);

        $items = $paginator->getCollection()
            ->map(fn (Model $favorite): FavoriteItemData => $this->mapFavorite($favorite, $locale));

        // Preserve pagination metadata while swapping the underlying collection
        // for the flattened DTOs.
        return new ConcretePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => ConcretePaginator::resolveCurrentPath()],
        );
    }

    /**
     * Per-type bookmark counts for a user, keyed by content-type slug, plus an
     * `all` total. Unregistered/orphaned morph classes are ignored.
     *
     * @return array<string, int> e.g. ['all' => 12, 'article' => 7, 'hadith' => 5]
     */
    public function countsForUser(User $user): array
    {
        $raw = $this->favorites->countsByTypeForUser($user->id);

        $counts = ['all' => 0];
        foreach (FavorableType::cases() as $type) {
            $counts[$type->value] = 0;
        }

        foreach ($raw as $morphClass => $count) {
            $type = $this->registry->typeForModelClass((string) $morphClass);
            if ($type === null) {
                continue;
            }
            $counts[$type->value] = $count;
            $counts['all'] += $count;
        }

        return $counts;
    }

    public function totalForUser(User $user): int
    {
        return $this->favorites->totalForUser($user->id);
    }

    /**
     * All content ids the user bookmarked for one content type — hydrates
     * initial toggle state on listing pages (one query, ids only).
     *
     * @return list<int>
     */
    public function favoritedContentIds(User $user, FavorableType $type): array
    {
        $morphClass = $this->registry->modelClass($type);
        if ($morphClass === null) {
            return [];
        }

        return $this->favorites->idsForUser($user->id, $morphClass);
    }

    public function isFavorited(User $user, FavorableType $type, int $contentId): bool
    {
        $morphClass = $this->registry->modelClass($type);
        if ($morphClass === null) {
            return false;
        }

        return $this->favorites->exists($user->id, $morphClass, $contentId);
    }

    /**
     * Add a bookmark for the user. Idempotent: re-bookmarking the same content
     * is a no-op. Returns true when a new bookmark was created.
     *
     * @throws \App\Domain\Shared\Exceptions\EntityNotFoundException when the
     *                                                               content type is unregistered or the content row does not exist.
     */
    public function add(User $user, FavorableType $type, int $contentId): bool
    {
        $morphClass = $this->requireExistingContent($type, $contentId);

        return $this->favorites->add($user->id, $morphClass, $contentId);
    }

    /**
     * Remove a user's bookmark. Returns true when a row was deleted. Scoped to
     * the user, so a user can never delete another user's bookmark.
     */
    public function remove(User $user, FavorableType $type, int $contentId): bool
    {
        $morphClass = $this->registry->modelClass($type);
        if ($morphClass === null) {
            return false;
        }

        return $this->favorites->remove($user->id, $morphClass, $contentId) > 0;
    }

    /**
     * Resolve and assert the morph class for a type, and that the target row
     * exists, before allowing a bookmark to be created.
     *
     * @return class-string<Model>
     *
     * @throws \App\Domain\Shared\Exceptions\EntityNotFoundException
     */
    private function requireExistingContent(FavorableType $type, int $contentId): string
    {
        $morphClass = $this->registry->modelClass($type);

        if ($morphClass === null || ! class_exists($morphClass)) {
            throw \App\Domain\Shared\Exceptions\EntityNotFoundException::withId($type->value, $contentId);
        }

        /** @var Model $model */
        $model = new $morphClass;
        $exists = $model->newQuery()->whereKey($contentId)->exists();

        if (! $exists) {
            throw \App\Domain\Shared\Exceptions\EntityNotFoundException::withId($type->value, $contentId);
        }

        return $morphClass;
    }

    /**
     * Flatten one favorite row (with its eager-loaded target) into a DTO.
     */
    private function mapFavorite(Model $favorite, string $locale): FavoriteItemData
    {
        $morphClass = (string) $favorite->getAttribute('favorable_type');
        $type = $this->registry->typeForModelClass($morphClass);
        $target = $favorite->getRelation('favorable');

        // Orphaned: the morph class is no longer whitelisted, or the target row
        // was deleted. Render a graceful placeholder rather than crashing.
        if ($type === null || ! $target instanceof Model) {
            return new FavoriteItemData(
                favoriteId: (int) $favorite->getAttribute('id'),
                type: $type ?? FavorableType::ARTICLE,
                contentId: (int) $favorite->getAttribute('favorable_id'),
                title: __('profile.bookmark_unavailable'),
                excerpt: null,
                url: null,
                icon: 'alert-triangle',
                typeLabelKey: 'profile.type_unknown',
                createdAt: (string) $favorite->getAttribute('created_at'),
                isOrphaned: true,
            );
        }

        $display = $this->registry->display($type, $target, $locale);

        return new FavoriteItemData(
            favoriteId: (int) $favorite->getAttribute('id'),
            type: $type,
            contentId: (int) $favorite->getAttribute('favorable_id'),
            title: $display['title'] !== '' ? $display['title'] : __('profile.bookmark_untitled'),
            excerpt: $display['excerpt'] ?? null,
            url: $display['url'] ?? null,
            icon: $type->icon(),
            typeLabelKey: $type->labelKey(),
            createdAt: (string) $favorite->getAttribute('created_at'),
        );
    }
}
