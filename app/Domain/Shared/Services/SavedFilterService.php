<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\SavedFilter;
use App\Domain\Shared\Repositories\SavedFilterRepositoryInterface;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

/**
 * Saved Filter Service
 *
 * Encapsulates all business logic for saved filters, including ownership
 * enforcement, default-flag management, and usage tracking.
 * All Eloquent access is delegated to SavedFilterRepositoryInterface.
 */
class SavedFilterService
{
    public function __construct(
        protected SavedFilterRepositoryInterface $repository
    ) {}

    /**
     * List saved filters for a user, optionally scoped to a module.
     *
     * @return Collection<int, SavedFilter>
     */
    public function listForUser(User|int $user, ?string $module = null): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->repository->getByUser($userId, $module);
    }

    /**
     * Retrieve a single filter by ID.
     *
     * @throws ResourceNotFoundException
     */
    public function findOrFail(int $id): SavedFilter
    {
        /** @var SavedFilter|null $filter */
        $filter = $this->repository->find($id);

        if ($filter === null) {
            throw new ResourceNotFoundException('SavedFilter', $id);
        }

        return $filter;
    }

    /**
     * Create a new saved filter for a user.
     *
     * When is_default is true, any existing defaults for the same
     * user+module pair are cleared atomically before creating.
     */
    public function create(User|int $user, array $data): SavedFilter
    {
        $userId = $user instanceof User ? $user->id : $user;

        if ($data['is_default'] ?? false) {
            $this->repository->clearDefaults($userId, $data['module']);
        }

        /** @var SavedFilter $filter */
        $filter = $this->repository->create(array_merge($data, ['user_id' => $userId]));

        return $filter;
    }

    /**
     * Update an existing filter, enforcing ownership.
     *
     * When flipping is_default to true, existing defaults for the same
     * user+module are cleared first.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function update(int $id, User|int $user, array $data): SavedFilter
    {
        $userId = $user instanceof User ? $user->id : $user;
        $filter = $this->findOrFail($id);

        $this->assertOwnership($filter, $userId, 'update');

        // Only clear other defaults when the flag is being turned ON.
        if (($data['is_default'] ?? false) && ! $filter->is_default) {
            $this->repository->clearDefaults($userId, $filter->module);
        }

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? $filter->description,
            'filters' => $data['filters'],
            'sort_options' => $data['sort_options'] ?? $filter->sort_options,
            'is_public' => $data['is_public'] ?? $filter->is_public,
            'is_default' => $data['is_default'] ?? $filter->is_default,
        ];

        return $this->repository->updateFilter($id, $payload);
    }

    /**
     * Delete a saved filter, enforcing ownership.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function delete(int $id, User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $filter = $this->findOrFail($id);

        $this->assertOwnership($filter, $userId, 'delete');

        $this->repository->delete($id);
    }

    /**
     * Set a filter as the module default for a user, enforcing ownership.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function setDefault(int $id, User|int $user): SavedFilter
    {
        $userId = $user instanceof User ? $user->id : $user;
        $filter = $this->findOrFail($id);

        $this->assertOwnership($filter, $userId, 'modify');

        $this->repository->clearDefaults($userId, $filter->module);

        return $this->repository->updateFilter($id, ['is_default' => true]);
    }

    /**
     * Retrieve public filters, optionally scoped to a module.
     *
     * @return Collection<int, SavedFilter>
     */
    public function listPublic(?string $module = null): Collection
    {
        return $this->repository->getPublic($module);
    }

    /**
     * Retrieve a filter and verify it is accessible by the given user.
     *
     * A filter is accessible if it is public, or if it is owned by the user.
     * Increments usage_count on every successful access.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function findAccessibleForApply(int $id, ?User $user): SavedFilter
    {
        $filter = $this->findOrFail($id);

        if (! $filter->is_public && $filter->user_id !== $user?->id) {
            throw new AuthorizationException(
                'You do not have permission to use this filter'
            );
        }

        $this->repository->incrementUsage($id);

        // Return a fresh copy so usage_count reflects the increment.
        return $this->findOrFail($id);
    }

    /**
     * Assert that the given user owns the filter.
     *
     * @throws AuthorizationException
     */
    private function assertOwnership(SavedFilter $filter, int $userId, string $action): void
    {
        if ($filter->user_id !== $userId) {
            throw new AuthorizationException(
                "You do not have permission to {$action} this filter"
            );
        }
    }
}
