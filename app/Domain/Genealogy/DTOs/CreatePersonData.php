<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\DTOs;

use App\Http\Requests\Admin\StoreGenealogyRequest;

/**
 * Create Person DTO
 *
 * Carries the validated, type-safe payload needed to create a new person in
 * the genealogy tree. Built from the incoming StoreGenealogyRequest so that
 * service classes never touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 * @param  array<string, string|null>  $title  Locale-keyed titles.
 * @param  array<string, string|null>  $birthPlace  Locale-keyed birth place descriptions.
 * @param  array<string, string|null>  $deathPlace  Locale-keyed death place descriptions.
 * @param  array<string, string|null>  $burialPlace  Locale-keyed burial place descriptions.
 * @param  array<string, string|null>  $occupation  Locale-keyed occupation descriptions.
 * @param  array<string, string|null>  $biography  Locale-keyed biography content.
 * @param  array<string, string|null>  $notes  Locale-keyed notes.
 */
final class CreatePersonData
{
    public function __construct(
        public readonly array $name,
        public readonly array $title,
        public readonly string $personType,
        public readonly string $gender,
        public readonly ?int $fatherId,
        public readonly ?int $motherId,
        public readonly ?int $sahabaId,
        public readonly ?string $birthDateHijri,
        public readonly ?string $birthDateGregorian,
        public readonly array $birthPlace,
        public readonly ?string $deathDateHijri,
        public readonly ?string $deathDateGregorian,
        public readonly array $deathPlace,
        public readonly array $burialPlace,
        public readonly ?string $tribe,
        public readonly array $occupation,
        public readonly array $biography,
        public readonly array $notes,
        public readonly bool $isVerified,
    ) {}

    /**
     * Construct a CreatePersonData instance from the validated store request.
     */
    public static function fromRequest(StoreGenealogyRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            title: $validated['title'] ?? [],
            personType: $validated['person_type'],
            gender: $validated['gender'],
            fatherId: isset($validated['father_id']) ? (int) $validated['father_id'] : null,
            motherId: isset($validated['mother_id']) ? (int) $validated['mother_id'] : null,
            sahabaId: isset($validated['sahaba_id']) ? (int) $validated['sahaba_id'] : null,
            birthDateHijri: $validated['birth_date_hijri'] ?? null,
            birthDateGregorian: $validated['birth_date_gregorian'] ?? null,
            birthPlace: $validated['birth_place'] ?? [],
            deathDateHijri: $validated['death_date_hijri'] ?? null,
            deathDateGregorian: $validated['death_date_gregorian'] ?? null,
            deathPlace: $validated['death_place'] ?? [],
            burialPlace: $validated['burial_place'] ?? [],
            tribe: $validated['tribe'] ?? null,
            occupation: $validated['occupation'] ?? [],
            biography: $validated['biography'] ?? [],
            notes: $validated['notes'] ?? [],
            isVerified: $request->boolean('is_verified'),
        );
    }
}
