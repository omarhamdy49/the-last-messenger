<?php

declare(strict_types=1);

namespace App\Domain\People\ValueObjects;

use App\Domain\People\Enums\ProfileFieldType;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Extended biographical detail, stored in the `muslims.profile` JSON column.
 *
 * ==> TO ADD A FIELD, ADD ONE ENTRY TO fields(). THAT IS THE WHOLE CHANGE. <==
 *
 * From that single entry come:
 *   - the input the admin form renders (type + group + label)
 *   - the validation rules the FormRequest applies
 *   - the key that is written to the column
 *   - whether the field exists at all for a given person (onlyRoles)
 *
 * No migration, because the column is JSON. No free-form keys either: anything
 * not defined here is dropped on write, so a typo cannot invent a field that
 * then reads back as data.
 *
 * Add a matching `sahaba.profile.<key>` translation and it is done.
 */
final readonly class MuslimProfile implements Arrayable, Castable, JsonSerializable
{
    public const GROUP_IDENTITY = 'identity';

    public const GROUP_BIRTH = 'birth';

    public const GROUP_DEATH = 'death';

    public const GROUP_MISSION = 'mission';

    /** Groups in the order the admin form renders them. */
    public const GROUPS = [
        self::GROUP_IDENTITY,
        self::GROUP_BIRTH,
        self::GROUP_DEATH,
        self::GROUP_MISSION,
    ];

    /** @param array<string, string|int|null> $values keyed by storage key */
    public function __construct(private array $values = []) {}

    /**
     * The field registry — the single place a field is defined.
     *
     * @return list<ProfileField>
     */
    public static function fields(): array
    {
        return [
            // Identity
            new ProfileField('full_name', translatable: true, group: self::GROUP_IDENTITY, max: 500),
            new ProfileField('tribe', translatable: true, group: self::GROUP_IDENTITY),

            // Birth
            new ProfileField('birth_day', translatable: true, group: self::GROUP_BIRTH, max: 100),
            new ProfileField('birth_region', translatable: true, group: self::GROUP_BIRTH),
            new ProfileField('birth_year_ce', ProfileFieldType::Number, group: self::GROUP_BIRTH),

            // Death
            new ProfileField('death_day', translatable: true, group: self::GROUP_DEATH, max: 100),
            new ProfileField('death_place', translatable: true, group: self::GROUP_DEATH),
            new ProfileField('death_detail', translatable: true, group: self::GROUP_DEATH, max: 500),
            new ProfileField('death_year_ce', ProfileFieldType::Number, group: self::GROUP_DEATH),

            // Prophethood — restricted. These describe the start of revelation
            // and are not facts a companion or scholar can have.
            new ProfileField('mission_place', translatable: true, group: self::GROUP_MISSION, onlyRoles: ['prophet']),
            new ProfileField('mission_age', ProfileFieldType::Number, group: self::GROUP_MISSION, onlyRoles: ['prophet']),
            new ProfileField('mission_year_ce', ProfileFieldType::Number, group: self::GROUP_MISSION, onlyRoles: ['prophet']),
            new ProfileField('mission_detail', ProfileFieldType::LongText, translatable: true, group: self::GROUP_MISSION, onlyRoles: ['prophet']),
        ];
    }

    /**
     * The fields a person with these roles actually has.
     *
     * @param  list<string>  $roleSlugs
     * @return list<ProfileField>
     */
    public static function fieldsFor(array $roleSlugs): array
    {
        return array_values(array_filter(
            self::fields(),
            fn (ProfileField $field): bool => $field->isAvailableTo($roleSlugs),
        ));
    }

    /**
     * @param  list<string>  $roleSlugs
     * @return list<ProfileField>
     */
    public static function fieldsInGroup(string $group, array $roleSlugs): array
    {
        return array_values(array_filter(
            self::fieldsFor($roleSlugs),
            fn (ProfileField $field): bool => $field->group === $group,
        ));
    }

    /**
     * Validation rules, generated so a new field is validated without touching
     * the FormRequest. Scoped to the person's roles: a restricted field posted
     * by someone who should not have it simply is not an accepted key.
     *
     * @param  list<string>  $roleSlugs
     * @return array<string, list<string>>
     */
    public static function rulesFor(array $roleSlugs): array
    {
        $rules = ['profile' => ['nullable', 'array']];

        foreach (self::fieldsFor($roleSlugs) as $field) {
            foreach ($field->storageKeys() as $key) {
                $rules['profile.'.$key] = $field->type->rules($field->max);
            }
        }

        return $rules;
    }

    /**
     * Build from submitted form data, keeping only defined keys the person's
     * roles allow and coercing each to its stored type. Empty values are
     * dropped rather than stored as "", so `isEmpty()` stays meaningful and the
     * column holds NULL for a person with no extended detail.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $roleSlugs
     */
    public static function fromFormData(array $data, array $roleSlugs): self
    {
        $values = [];

        foreach (self::fieldsFor($roleSlugs) as $field) {
            foreach ($field->storageKeys() as $key) {
                $coerced = $field->type->coerce($data[$key] ?? null);

                if ($coerced !== null) {
                    $values[$key] = $coerced;
                }
            }
        }

        return new self($values);
    }

    /** @param array<string, mixed>|null $values */
    public static function fromArray(?array $values): self
    {
        return new self($values ?? []);
    }

    /**
     * Raw read by storage key. Unknown keys return null rather than throwing:
     * a column written before a field was renamed must not break the page.
     */
    public function get(string $key): string|int|null
    {
        $value = $this->values[$key] ?? null;

        return is_string($value) || is_int($value) ? $value : null;
    }

    /**
     * Read a field in a locale, falling back to the other language rather than
     * showing nothing — a profile filled only in Arabic should still render on
     * the English page.
     */
    public function localized(string $key, string $locale = 'ar'): ?string
    {
        $primary = $this->get($key.'_'.$locale);

        if ($primary !== null && $primary !== '') {
            return (string) $primary;
        }

        $fallback = $this->get($key.'_'.($locale === 'ar' ? 'en' : 'ar'));

        return $fallback === null || $fallback === '' ? null : (string) $fallback;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Arrayable + JsonSerializable are not decoration: without them a cast
     * attribute serialises as `{}`, so `$muslim->toArray()` and every API
     * resource built on it would silently drop the whole profile.
     *
     * @return array<string, string|int|null>
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }

    /**
     * Cast the JSON column straight to this object, so `$muslim->profile` is
     * never a raw array anywhere in the app.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes): MuslimProfile
            {
                return MuslimProfile::fromArray(
                    is_string($value) ? json_decode($value, true) : null,
                );
            }

            /** Stores NULL, not `{}`, when there is nothing to keep. */
            public function set($model, string $key, $value, array $attributes): array
            {
                if ($value instanceof MuslimProfile) {
                    return [$key => $value->isEmpty() ? null : json_encode($value->toArray(), JSON_UNESCAPED_UNICODE)];
                }

                if (is_array($value)) {
                    return [$key => $value === [] ? null : json_encode($value, JSON_UNESCAPED_UNICODE)];
                }

                return [$key => null];
            }
        };
    }
}
