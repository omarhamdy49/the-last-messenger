<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Entity Not Found Exception
 *
 * Thrown when a requested entity cannot be found.
 * Extends domain exception with entity-specific context.
 */
class EntityNotFoundException extends DomainException
{
    protected string $entityType;

    protected int|string $entityId;

    /**
     * Create exception for a specific entity
     */
    public static function withId(string $entityType, int|string $id): static
    {
        $exception = new static(
            message: "الكيان {$entityType} برقم {$id} غير موجود | {$entityType} with ID {$id} not found",
            context: [
                'entity_type' => $entityType,
                'entity_id' => $id,
            ],
            code: 404
        );

        $exception->entityType = $entityType;
        $exception->entityId = $id;

        return $exception;
    }

    /**
     * Create exception for slug-based lookup
     */
    public static function withSlug(string $entityType, string $slug): static
    {
        $exception = new static(
            message: "الكيان {$entityType} بالمعرف {$slug} غير موجود | {$entityType} with slug {$slug} not found",
            context: [
                'entity_type' => $entityType,
                'slug' => $slug,
            ],
            code: 404
        );

        $exception->entityType = $entityType;
        $exception->entityId = $slug;

        return $exception;
    }

    /**
     * Create exception with custom criteria
     *
     * @param array<string, mixed> $criteria
     */
    public static function withCriteria(string $entityType, array $criteria): static
    {
        $criteriaStr = json_encode($criteria);

        return new static(
            message: "{$entityType} not found with criteria: {$criteriaStr}",
            context: [
                'entity_type' => $entityType,
                'criteria' => $criteria,
            ],
            code: 404
        );
    }

    /**
     * Get the entity type
     */
    public function getEntityType(): string
    {
        return $this->entityType ?? $this->context['entity_type'] ?? 'Unknown';
    }

    /**
     * Get the entity ID
     */
    public function getEntityId(): int|string
    {
        return $this->entityId ?? $this->context['entity_id'] ?? 0;
    }
}
