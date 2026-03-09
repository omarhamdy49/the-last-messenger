<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Language Model
 *
 * Represents available languages in the system.
 *
 * @property int $id
 * @property string $code
 * @property array $name
 * @property string $native_name
 * @property string $direction
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property string|null $flag_icon
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Language extends Model
{
    use HasLocalizedContent;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'native_name',
        'direction',
        'is_active',
        'is_default',
        'sort_order',
        'flag_icon',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return LanguageTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name'];
    }

    /**
     * Scope: Get only active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get the default language.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Get all active languages.
     *
     * @return Collection
     */
    public static function getAllActive(): Collection
    {
        return Cache::remember(
            'languages:active',
            now()->addMinutes(5),
            fn () => self::active()->ordered()->get()
        );
    }

    /**
     * Get the default language.
     *
     * @return Language|null
     */
    public static function getDefault(): ?Language
    {
        return Cache::remember(
            'languages:default',
            now()->addMinutes(5),
            fn () => self::default()->first()
        );
    }

    /**
     * Get default language (alias for compatibility)
     *
     * @return Language|null
     */
    public static function getDefaultCached(): ?Language
    {
        return self::getDefault();
    }

    /**
     * Get all active languages (alias for compatibility)
     *
     * @return Collection
     */
    public static function getActiveCached(): Collection
    {
        return self::getAllActive();
    }

    /**
     * Get all languages (not just active)
     *
     * @return Collection
     */
    public static function getAllCached(): Collection
    {
        return Cache::remember(
            'languages:all',
            now()->addMinutes(5),
            fn () => self::ordered()->get()
        );
    }

    /**
     * Get default language code
     *
     * @return string
     */
    public static function getDefaultCode(): string
    {
        $default = self::getDefault();
        return $default ? $default->code : 'ar';
    }

    /**
     * Get active language codes as array.
     *
     * @return array<string>
     */
    public static function getActiveCodes(): array
    {
        return Cache::remember(
            'languages:codes',
            now()->addMinutes(5),
            fn () => self::active()->pluck('code')->toArray()
        );
    }

    /**
     * Check if a language code is valid and active.
     *
     * @param string $code
     * @return bool
     */
    public static function isValidCode(string $code): bool
    {
        return in_array($code, self::getActiveCodes(), true);
    }

    /**
     * Clear language caches.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('languages:active');
        Cache::forget('languages:default');
        Cache::forget('languages:codes');
    }

    /**
     * Boot method to handle events.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when language is created, updated, or deleted
        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });

        // Ensure only one default language exists
        static::saving(function ($language) {
            if ($language->is_default) {
                self::where('id', '!=', $language->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
