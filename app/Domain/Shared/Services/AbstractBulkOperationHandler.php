<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\BulkOperationHandlerInterface;
use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base for bulk operation handlers.
 *
 * Concrete handlers supply only the Eloquent model class string via modelClass().
 * All operation logic lives here so domain handlers stay thin and declarative.
 */
abstract class AbstractBulkOperationHandler implements BulkOperationHandlerInterface
{
    /**
     * Return the fully-qualified Eloquent model class for this entity type.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * {@inheritDoc}
     */
    public function handle(string $operationType, array $ids, array $parameters = []): array
    {
        $type = BulkOperationTypeEnum::from($operationType);

        return match ($type) {
            BulkOperationTypeEnum::UPDATE => $this->bulkUpdate($ids, $parameters),
            BulkOperationTypeEnum::DELETE => $this->bulkDelete($ids, soft: false),
            BulkOperationTypeEnum::SOFT_DELETE => $this->bulkDelete($ids, soft: true),
            BulkOperationTypeEnum::RESTORE => $this->bulkRestore($ids),
            BulkOperationTypeEnum::PUBLISH => $this->bulkPublish($ids),
            BulkOperationTypeEnum::UNPUBLISH => $this->bulkUnpublish($ids),
            BulkOperationTypeEnum::ARCHIVE => $this->bulkArchive($ids),
            BulkOperationTypeEnum::CHANGE_CATEGORY => $this->bulkChangeCategory($ids, $parameters),
            BulkOperationTypeEnum::ADD_TAGS => $this->bulkAddTags($ids, $parameters),
            BulkOperationTypeEnum::REMOVE_TAGS => $this->bulkRemoveTags($ids, $parameters),
            BulkOperationTypeEnum::CHANGE_STATUS => $this->bulkChangeStatus($ids, $parameters),
            BulkOperationTypeEnum::CHANGE_AUTHOR => $this->bulkChangeAuthor($ids, $parameters),
            BulkOperationTypeEnum::ASSIGN_USER => $this->bulkAssignUser($ids, $parameters),
            default => throw new \InvalidArgumentException(
                "Operation [{$operationType}] is not supported by handler for module [{$this->getSupportedModule()}]."
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function preview(array $ids): array
    {
        $model = $this->modelClass();

        return $model::whereIn('id', array_slice($ids, 0, 10))
            ->get(['id', 'title', 'name'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title ?? $item->name ?? 'N/A',
            ])
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function export(array $ids, string $format): string
    {
        $model = $this->modelClass();
        $items = $model::whereIn('id', $ids)->get();

        return match ($format) {
            'json' => $items->toJson(),
            'csv' => $this->toCsv($items),
            default => throw new \InvalidArgumentException("Unsupported export format: [{$format}]."),
        };
    }

    // -------------------------------------------------------------------------
    // Private operation implementations
    // -------------------------------------------------------------------------

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkUpdate(array $ids, array $parameters): array
    {
        $fields = $parameters['fields'] ?? [];

        if (empty($fields)) {
            throw new \InvalidArgumentException('No fields provided for update operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $model::where('id', $id)->update($fields);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkDelete(array $ids, bool $soft): array
    {
        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null) {
                    $failedCount++;
                    $errors[$id] = 'Item not found.';

                    continue;
                }

                $soft ? $item->delete() : $item->forceDelete();
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkRestore(array $ids): array
    {
        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::withTrashed()->find($id);

                if ($item === null || ! $item->trashed()) {
                    $failedCount++;
                    $errors[$id] = 'Item not found or not in trash.';

                    continue;
                }

                $item->restore();
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkPublish(array $ids): array
    {
        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null) {
                    $failedCount++;
                    $errors[$id] = 'Item not found.';

                    continue;
                }

                if (method_exists($item, 'publish')) {
                    $item->publish();
                } else {
                    $model::where('id', $id)->update([
                        'status' => 'published',
                        'published_at' => now(),
                    ]);
                }

                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkUnpublish(array $ids): array
    {
        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null) {
                    $failedCount++;
                    $errors[$id] = 'Item not found.';

                    continue;
                }

                if (method_exists($item, 'unpublish')) {
                    $item->unpublish();
                } else {
                    $model::where('id', $id)->update(['status' => 'draft']);
                }

                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkArchive(array $ids): array
    {
        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null) {
                    $failedCount++;
                    $errors[$id] = 'Item not found.';

                    continue;
                }

                if (method_exists($item, 'archive')) {
                    $item->archive();
                } else {
                    $model::where('id', $id)->update(['status' => 'archived']);
                }

                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkChangeCategory(array $ids, array $parameters): array
    {
        $categoryId = $parameters['category_id'] ?? null;

        if ($categoryId === null) {
            throw new \InvalidArgumentException('category_id is required for change_category operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $model::where('id', $id)->update(['category_id' => $categoryId]);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkAddTags(array $ids, array $parameters): array
    {
        $tagIds = $parameters['tag_ids'] ?? [];

        if (empty($tagIds)) {
            throw new \InvalidArgumentException('tag_ids are required for add_tags operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null || ! method_exists($item, 'tags')) {
                    $failedCount++;
                    $errors[$id] = 'Item not found or does not support tags.';

                    continue;
                }

                $item->tags()->syncWithoutDetaching($tagIds);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkRemoveTags(array $ids, array $parameters): array
    {
        $tagIds = $parameters['tag_ids'] ?? [];

        if (empty($tagIds)) {
            throw new \InvalidArgumentException('tag_ids are required for remove_tags operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $item = $model::find($id);

                if ($item === null || ! method_exists($item, 'tags')) {
                    $failedCount++;
                    $errors[$id] = 'Item not found or does not support tags.';

                    continue;
                }

                $item->tags()->detach($tagIds);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkChangeStatus(array $ids, array $parameters): array
    {
        $status = $parameters['status'] ?? null;

        if ($status === null) {
            throw new \InvalidArgumentException('status is required for change_status operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $model::where('id', $id)->update(['status' => $status]);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkChangeAuthor(array $ids, array $parameters): array
    {
        $authorId = $parameters['author_id'] ?? null;

        if ($authorId === null) {
            throw new \InvalidArgumentException('author_id is required for change_author operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $model::where('id', $id)->update(['author_id' => $authorId]);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $parameters
     * @return array{successCount: int, failedCount: int, errors: array<int, string>}
     */
    private function bulkAssignUser(array $ids, array $parameters): array
    {
        $userId = $parameters['user_id'] ?? null;

        if ($userId === null) {
            throw new \InvalidArgumentException('user_id is required for assign_user operation.');
        }

        $model = $this->modelClass();
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $model::where('id', $id)->update(['assigned_to' => $userId]);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$id] = $e->getMessage();
            }
        }

        return compact('successCount', 'failedCount', 'errors');
    }

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>  $items
     */
    private function toCsv(\Illuminate\Database\Eloquent\Collection $items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $headers = array_keys($items->first()->toArray());
        $lines = [implode(',', $headers)];

        foreach ($items as $item) {
            $row = array_map(
                fn ($value) => is_array($value) ? json_encode($value) : $value,
                $item->toArray()
            );
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines);
    }
}
