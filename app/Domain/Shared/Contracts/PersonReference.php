<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * How any domain module refers to a person without importing the People module.
 *
 * `muslims` is the application-wide identity: hadith narrators, collection
 * compilers, companions, and genealogy nodes are all rows in it. Constitution IV
 * forbids one domain module importing another, so Hadith may hold a
 * `narrator_id` foreign key into `muslims` (database FKs may cross modules) but
 * may never `use App\Domain\People\Models\Muslim`. It receives this instead.
 */
final readonly class PersonReference
{
    public function __construct(
        public int $id,
        public string $slug,
        public ?string $nameAr = null,
        public ?string $nameEn = null,
    ) {}

    /**
     * Display name for a locale, falling back to the other language rather than
     * to an empty string — source records legitimately carry only one name, so
     * a missing Arabic name must not render as blank.
     */
    public function name(string $locale = 'ar'): string
    {
        return $locale === 'ar'
            ? ($this->nameAr ?? $this->nameEn ?? '')
            : ($this->nameEn ?? $this->nameAr ?? '');
    }
}
