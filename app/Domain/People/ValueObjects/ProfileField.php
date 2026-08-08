<?php

declare(strict_types=1);

namespace App\Domain\People\ValueObjects;

use App\Domain\People\Enums\ProfileFieldType;

/**
 * The definition of one extended-profile field.
 *
 * This is what makes the profile column schemaless but not free-form: a field
 * exists because there is a ProfileField for it in MuslimProfile::fields(), and
 * that one entry is what drives the form input, the validation rule and the
 * stored key.
 */
final readonly class ProfileField
{
    /**
     * @param  string  $key  storage key, snake_case, without any locale suffix
     * @param  bool  $translatable  true stores `{key}_ar` and `{key}_en` instead of `{key}`
     * @param  string  $group  which fieldset the admin form renders it under
     * @param  int|null  $max  overrides the type's default length cap
     * @param  list<string>|null  $onlyRoles  role slugs this field is restricted
     *                                        to; null means every person. A
     *                                        restricted field is not rendered,
     *                                        not validated and not stored for
     *                                        anyone outside those roles — the
     *                                        prophetic-mission fields are
     *                                        meaningless on a companion.
     */
    public function __construct(
        public string $key,
        public ProfileFieldType $type = ProfileFieldType::Text,
        public bool $translatable = false,
        public string $group = 'general',
        public ?int $max = null,
        public ?array $onlyRoles = null,
    ) {}

    /**
     * @param  list<string>  $roleSlugs  roles the person carries
     */
    public function isAvailableTo(array $roleSlugs): bool
    {
        if ($this->onlyRoles === null) {
            return true;
        }

        return array_intersect($this->onlyRoles, $roleSlugs) !== [];
    }

    /**
     * Every storage key this field occupies — a pair for a translatable field,
     * otherwise just the one.
     *
     * @return list<string>
     */
    public function storageKeys(): array
    {
        if (! $this->translatable) {
            return [$this->key];
        }

        return [$this->key.'_ar', $this->key.'_en'];
    }

    public function storageKey(?string $locale = null): string
    {
        return $this->translatable ? $this->key.'_'.($locale ?? 'ar') : $this->key;
    }

    /** Label lookup, so adding a field only needs a matching translation key. */
    public function label(): string
    {
        return __('sahaba.profile.'.$this->key);
    }
}
