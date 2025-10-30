<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Repositories\AdminLogRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin Logger Service
 *
 * Handles logging of all admin actions including CRUD operations,
 * authentication events, and bulk actions. Automatically captures
 * IP address, user agent, and filters sensitive data.
 */
class AdminLoggerService
{
    /**
     * List of sensitive fields to filter from logs
     *
     * @var array<string>
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'api_token',
        'access_token',
        'secret',
        'token',
        'secret_key',
        'private_key',
        'api_secret',
    ];

    /**
     * Constructor
     */
    public function __construct(
        private readonly AdminLogRepositoryInterface $adminLogRepository
    ) {}

    /**
     * Log creation of a model
     *
     * @param  Model  $model  The model that was created
     * @param  User  $user  The user who created it
     */
    public function logCreate(Model $model, User $user): void
    {
        try {
            $attributes = $this->filterSensitiveData($model->getAttributes());

            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => 'created',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => null,
                'new_values' => $attributes,
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log create action', [
                'error' => $e->getMessage(),
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log update of a model
     *
     * @param  Model  $model  The model that was updated
     * @param  array<string, mixed>  $oldValues  Original values before update
     * @param  array<string, mixed>  $newValues  New values after update
     * @param  User  $user  The user who updated it
     */
    public function logUpdate(Model $model, array $oldValues, array $newValues, User $user): void
    {
        try {
            // Filter only changed fields
            $changedOldValues = [];
            $changedNewValues = [];

            foreach ($newValues as $key => $value) {
                if (! array_key_exists($key, $oldValues) || $oldValues[$key] !== $value) {
                    $changedOldValues[$key] = $oldValues[$key] ?? null;
                    $changedNewValues[$key] = $value;
                }
            }

            // Skip logging if nothing actually changed
            if (empty($changedNewValues)) {
                return;
            }

            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => 'updated',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => $this->filterSensitiveData($changedOldValues),
                'new_values' => $this->filterSensitiveData($changedNewValues),
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log update action', [
                'error' => $e->getMessage(),
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log deletion of a model
     *
     * @param  Model  $model  The model that was deleted
     * @param  User  $user  The user who deleted it
     */
    public function logDelete(Model $model, User $user): void
    {
        try {
            // Capture all attributes before deletion
            $attributes = $this->filterSensitiveData($model->getAttributes());

            // Determine if it's soft delete or hard delete
            $action = method_exists($model, 'trashed') && $model->trashed()
                ? 'soft_deleted'
                : 'deleted';

            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => $attributes,
                'new_values' => null,
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log delete action', [
                'error' => $e->getMessage(),
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log successful admin login
     *
     * @param  User  $user  The user who logged in
     */
    public function logLogin(User $user): void
    {
        try {
            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => 'login',
                'model_type' => User::class,
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'login_at' => now()->toDateTimeString(),
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ],
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log login action', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log admin logout
     *
     * @param  User  $user  The user who logged out
     */
    public function logLogout(User $user): void
    {
        try {
            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => 'logout',
                'model_type' => User::class,
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'logout_at' => now()->toDateTimeString(),
                ],
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log logout action', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log bulk operations on multiple models
     *
     * @param  string  $action  The action performed (e.g., 'bulk_delete', 'bulk_publish')
     * @param  string  $modelType  The fully qualified class name of the model
     * @param  array<int>  $ids  Array of model IDs affected
     * @param  User  $user  The user who performed the action
     */
    public function logBulkAction(string $action, string $modelType, array $ids, User $user): void
    {
        try {
            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => null, // Null for bulk operations
                'old_values' => null,
                'new_values' => [
                    'affected_ids' => $ids,
                    'count' => count($ids),
                    'action_at' => now()->toDateTimeString(),
                ],
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log bulk action', [
                'error' => $e->getMessage(),
                'action' => $action,
                'model_type' => $modelType,
                'ids_count' => count($ids),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log restoration of a soft-deleted model
     *
     * @param  Model  $model  The model that was restored
     * @param  User  $user  The user who restored it
     */
    public function logRestore(Model $model, User $user): void
    {
        try {
            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => 'restored',
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'old_values' => [
                    'deleted_at' => method_exists($model, 'getDeletedAtColumn')
                        ? $model->getAttribute($model->getDeletedAtColumn())
                        : null,
                ],
                'new_values' => [
                    'restored_at' => now()->toDateTimeString(),
                ],
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log restore action', [
                'error' => $e->getMessage(),
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Log a custom action
     *
     * @param  string  $action  Custom action name
     * @param  Model|null  $model  The related model (if any)
     * @param  User  $user  The user who performed the action
     * @param  array<string, mixed>|null  $oldValues  Old values (if any)
     * @param  array<string, mixed>|null  $newValues  New values (if any)
     */
    public function logCustomAction(
        string $action,
        ?Model $model,
        User $user,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $this->adminLogRepository->log([
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->getKey() : null,
                'old_values' => $oldValues ? $this->filterSensitiveData($oldValues) : null,
                'new_values' => $newValues ? $this->filterSensitiveData($newValues) : null,
                'ip_address' => $this->getIpAddress(),
                'user_agent' => $this->getUserAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log custom action', [
                'error' => $e->getMessage(),
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Filter sensitive data from arrays
     *
     * @param  array<string, mixed>  $data  Data to filter
     * @return array<string, mixed> Filtered data
     */
    private function filterSensitiveData(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            // Check if key contains any sensitive field name (case-insensitive)
            $isSensitive = false;
            foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
                if (stripos($key, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $filtered[$key] = '***FILTERED***';
            } elseif (is_array($value)) {
                // Recursively filter nested arrays
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Get current request IP address
     */
    private function getIpAddress(): ?string
    {
        try {
            return request()->ip();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Get current request user agent
     */
    private function getUserAgent(): ?string
    {
        try {
            return request()->userAgent();
        } catch (Throwable) {
            return null;
        }
    }
}
