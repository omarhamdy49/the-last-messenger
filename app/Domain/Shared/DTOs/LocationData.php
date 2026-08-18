<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTOs;

use App\Domain\Shared\Enums\LocationType;

/**
 * Input for creating or updating a location.
 *
 * Constitution III: `array $data` never crosses a layer boundary. The controller
 * hands this to the service; the service hands it to the repository.
 *
 * ONE DTO, NOT TWO: the plan named a separate `UpdateLocationData`, but the
 * create and update payloads are field-for-field identical — the only difference
 * lives in the validation rules (`unique:locations,slug,{id}`), which belong to
 * the FormRequest, not here. A second class with no differing field is the
 * "no speculative abstraction" constitution IX forbids. The id is passed
 * alongside the DTO to `LocationService::update()`.
 */
final readonly class LocationData
{
    /**
     * @param  array<int, string>  $aliases
     * @param  array<string, array<string, string|null>>  $translations  locale => ['name' => …, 'description' => …]
     */
    private function __construct(
        public string $slug,
        public LocationType $type,
        public ?int $parentId,
        public ?string $latitude,
        public ?string $longitude,
        public array $aliases,
        public bool $isActive,
        public int $displayOrder,
        public array $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $validated  Output of StoreLocationRequest/UpdateLocationRequest::validated()
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            slug: (string) $validated['slug'],
            type: $validated['type'] instanceof LocationType
                ? $validated['type']
                : LocationType::from((string) $validated['type']),
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            latitude: self::coordinate($validated['latitude'] ?? null),
            longitude: self::coordinate($validated['longitude'] ?? null),
            aliases: self::cleanAliases($validated['aliases'] ?? []),
            isActive: (bool) ($validated['is_active'] ?? true),
            displayOrder: (int) ($validated['display_order'] ?? 0),
            translations: self::cleanTranslations($validated),
        );
    }

    private static function coordinate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  array<int, mixed>  $aliases
     * @return array<int, string>
     */
    public static function cleanAliases(array $aliases): array
    {
        $clean = [];

        foreach ($aliases as $alias) {
            if (! is_string($alias)) {
                continue;
            }

            $alias = trim($alias);

            if ($alias !== '' && ! in_array($alias, $clean, true)) {
                $clean[] = $alias;
            }
        }

        return $clean;
    }

    /**
     * Pull the translatable fields out of the validated payload.
     *
     * Admin forms post field-major (`name[ar]`, `description[ar]`), which is the
     * shape HasLocalizedContent::syncTranslations() already expects, so there is
     * nothing to reshape — only empties to drop.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, array<string, string>>
     */
    public static function cleanTranslations(array $validated): array
    {
        $byField = [];

        foreach (['name', 'description'] as $field) {
            $values = $validated[$field] ?? null;

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as $locale => $value) {
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }

                $byField[$field][(string) $locale] = trim($value);
            }
        }

        return $byField;
    }
}
