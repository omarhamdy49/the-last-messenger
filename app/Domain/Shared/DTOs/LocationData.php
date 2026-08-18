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
            translations: self::cleanTranslations($validated['translations'] ?? []),
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
     * Reshape locale-major input into the field-major shape syncTranslations() expects.
     *
     * In:  ['ar' => ['name' => 'مكة'], 'en' => ['name' => 'Makkah']]
     * Out: ['name' => ['ar' => 'مكة', 'en' => 'Makkah']]
     *
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, string|null>>
     */
    public static function cleanTranslations(array $translations): array
    {
        $byField = [];

        foreach ($translations as $locale => $fields) {
            if (! is_array($fields)) {
                continue;
            }

            foreach (['name', 'description'] as $field) {
                $value = $fields[$field] ?? null;

                if (! is_string($value) || trim($value) === '') {
                    continue;
                }

                $byField[$field][$locale] = trim($value);
            }
        }

        return $byField;
    }
}
