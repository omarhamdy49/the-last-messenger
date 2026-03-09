<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\Language;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HasLocalizedContent Trait
 *
 * Provides translation support via a separate per-module translation table.
 * Replaces Spatie's HasTranslations with a more scalable approach that supports
 * FULLTEXT search, per-locale readiness tracking, and proper eager loading.
 *
 * Models using this trait must implement:
 *   - getTranslationModelClass(): string  — FQCN of the translation model
 *   - getTranslatableFields(): array      — list of translatable field names
 *   - getTranslationForeignKey(): string  — FK column name on the translations table
 *
 * The translation model must have a `locale` column and columns matching
 * the translatable fields.
 */
trait HasLocalizedContent
{
    /**
     * Boot the trait: register event listeners.
     */
    public static function bootHasLocalizedContent(): void
    {
        // Cascade delete is handled by FK constraint, but in case
        // of soft-deleted parents that get force-deleted later:
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            $model->translations()->delete();
        });
    }

    /**
     * Get the translations relationship.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(
            $this->getTranslationModelClass(),
            $this->getTranslationForeignKey()
        );
    }

    /**
     * Get the FQCN of the translation model.
     * Must be implemented by the using model.
     */
    abstract public function getTranslationModelClass(): string;

    /**
     * Get the list of translatable field names.
     * Must be implemented by the using model.
     */
    abstract public function getTranslatableFields(): array;

    /**
     * Get the foreign key column on the translations table.
     * Override if the FK doesn't follow the default convention.
     */
    public function getTranslationForeignKey(): string
    {
        return $this->getForeignKey();
    }

    /**
     * Get a single translation value for a field and locale.
     *
     * Backward-compatible with Spatie's HasTranslations::getTranslation().
     * Blade views using `$model->getTranslation('title', 'ar')` continue to work.
     *
     * @param string $field The translatable field name
     * @param string|null $locale The locale code (defaults to app locale)
     * @param bool $useFallbackLocale Whether to fall back to default language
     * @return string|null
     */
    public function getTranslation(string $field, ?string $locale = null, bool $useFallbackLocale = true): ?string
    {
        if (! in_array($field, $this->getTranslatableFields())) {
            return $this->getAttribute($field);
        }

        $locale = $locale ?? app()->getLocale();

        // Try the requested locale first
        $translation = $this->findTranslationByLocale($locale);
        if ($translation && ! empty($translation->{$field})) {
            return $translation->{$field};
        }

        // Fall back to default language
        if ($useFallbackLocale) {
            $defaultLocale = Language::getDefaultCode();
            if ($locale !== $defaultLocale) {
                $translation = $this->findTranslationByLocale($defaultLocale);
                if ($translation && ! empty($translation->{$field})) {
                    return $translation->{$field};
                }
            }

            // Return first available non-empty translation
            foreach ($this->getLoadedTranslations() as $trans) {
                if (! empty($trans->{$field})) {
                    return $trans->{$field};
                }
            }
        }

        return null;
    }

    /**
     * Get all translations for a field as a locale-keyed map.
     *
     * Backward-compatible with Spatie's HasTranslations::getTranslations().
     * Returns: ['ar' => '...', 'en' => '...', 'fr' => '...']
     *
     * @param string $field The translatable field name
     * @return array<string, string|null>
     */
    public function getTranslations(string $field): array
    {
        if (! in_array($field, $this->getTranslatableFields())) {
            return [];
        }

        $result = [];
        foreach ($this->getLoadedTranslations() as $translation) {
            $result[$translation->locale] = $translation->{$field};
        }

        return $result;
    }

    /**
     * Get the translation record for a specific locale.
     *
     * @param string|null $locale The locale code (defaults to app locale)
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translationFor(?string $locale = null): ?\Illuminate\Database\Eloquent\Model
    {
        $locale = $locale ?? app()->getLocale();

        return $this->findTranslationByLocale($locale);
    }

    /**
     * Sync translations from form data.
     *
     * Accepts data in the format sent by admin forms:
     *   ['title' => ['ar' => '...', 'en' => '...'], 'description' => ['ar' => '...'], ...]
     *
     * Groups the data by locale, then upserts one record per locale.
     *
     * @param array<string, array<string, string>> $data Keyed by field → locale → value
     * @return void
     */
    public function syncTranslations(array $data): void
    {
        $translatableFields = $this->getTranslatableFields();

        // Group data by locale: ['ar' => ['title' => '...', 'description' => '...'], ...]
        $byLocale = [];
        foreach ($translatableFields as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                continue;
            }

            foreach ($data[$field] as $locale => $value) {
                $byLocale[$locale][$field] = $value;
            }
        }

        // Upsert each locale
        $foreignKey = $this->getTranslationForeignKey();
        foreach ($byLocale as $locale => $fields) {
            // Skip locales where all fields are empty
            $hasContent = false;
            foreach ($fields as $value) {
                if (! empty(trim((string) ($value ?? '')))) {
                    $hasContent = true;
                    break;
                }
            }

            if (! $hasContent) {
                continue;
            }

            $attributes = array_merge($fields, [
                $foreignKey => $this->getKey(),
                'locale' => $locale,
                'is_ready' => $this->isLocaleReady($fields),
            ]);

            $this->translations()->updateOrCreate(
                [$foreignKey => $this->getKey(), 'locale' => $locale],
                $attributes
            );
        }

        // Refresh the translations relationship
        $this->load('translations');
    }

    /**
     * Check if a translation exists for a field and locale.
     *
     * @param string $field The translatable field name
     * @param string $locale The locale code
     * @return bool
     */
    public function hasTranslation(string $field, string $locale): bool
    {
        $translation = $this->findTranslationByLocale($locale);

        return $translation && ! empty($translation->{$field});
    }

    /**
     * Get translation for the current app locale.
     *
     * @param string $field The translatable field name
     * @return string|null
     */
    public function trans(string $field): ?string
    {
        return $this->getTranslation($field, app()->getLocale());
    }

    /**
     * Check if all required translatable fields are filled for a locale.
     *
     * @param array<string, mixed> $fields The field values for a locale
     * @return bool
     */
    protected function isLocaleReady(array $fields): bool
    {
        // A locale is ready if at least the primary field (first translatable) has content
        $primaryField = $this->getTranslatableFields()[0] ?? null;
        if (! $primaryField) {
            return false;
        }

        return ! empty(trim((string) ($fields[$primaryField] ?? '')));
    }

    /**
     * Find a translation record by locale from the loaded relationship.
     *
     * @param string $locale
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function findTranslationByLocale(string $locale): ?\Illuminate\Database\Eloquent\Model
    {
        $translations = $this->getLoadedTranslations();

        return $translations->firstWhere('locale', $locale);
    }

    /**
     * Get the loaded translations collection. Auto-loads if not yet loaded.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getLoadedTranslations(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->relationLoaded('translations')) {
            $this->load('translations');
        }

        return $this->getRelation('translations');
    }

    /**
     * Scope: Eager load translations for a single locale.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTranslation($query, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $query->with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }]);
    }

    /**
     * Scope: Eager load all translations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAllTranslations($query)
    {
        return $query->with('translations');
    }

    /**
     * Scope: Search a translatable field using whereHas on translations table.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field Translatable field name
     * @param string $term Search term
     * @param string|null $locale Optional locale filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchTranslation($query, string $field, string $term, ?string $locale = null)
    {
        return $query->whereHas('translations', function ($q) use ($field, $term, $locale) {
            $q->where($field, 'LIKE', "%{$term}%");

            if ($locale) {
                $q->where('locale', $locale);
            }
        });
    }
}
