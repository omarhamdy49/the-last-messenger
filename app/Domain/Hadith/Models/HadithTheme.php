<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HadithTheme Model
 *
 * Represents themes/subjects for categorizing Hadiths (e.g., "Prayer", "Fasting", "Marriage")
 * Supports hierarchical structure with parent-child relationships
 *
 * @property int $id
 * @property array $name Arabic/English theme name (translatable)
 * @property string|null $theme_en Theme name in English
 * @property string|null $theme_ar Theme name in Arabic
 * @property string|null $description Theme description
 * @property int|null $parent_id Parent theme ID for hierarchy
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithTheme extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_themes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'theme_en',
        'theme_ar',
        'parent_id',
        'review_status',
        'source_reference',
    ];

    /**
     * Disable timestamps if table doesn't have them
     * (Based on migration, the table doesn't have created_at/updated_at)
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return HadithThemeTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Get all Hadiths associated with this theme
     */
    public function hadiths(): BelongsToMany
    {
        return $this->belongsToMany(
            Hadith::class,
            'hadith_hadith_themes',
            'hadith_theme_id',
            'hadith_id'
        );
    }

    /**
     * Get the parent theme
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(HadithTheme::class, 'parent_id');
    }

    /**
     * Get all child themes
     */
    public function children(): HasMany
    {
        return $this->hasMany(HadithTheme::class, 'parent_id');
    }

    /**
     * Get all descendant themes recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope: Get only root themes (no parent)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Get themes with children
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithChildren($query)
    {
        return $query->has('children');
    }

    /**
     * Scope: Get leaf themes (no children)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLeaves($query)
    {
        return $query->doesntHave('children');
    }

    /**
     * Check if this theme has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this theme is a root theme
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get the full hierarchy path (e.g., "Worship > Prayer > Obligatory Prayers")
     */
    public function getHierarchyPath(string $locale = 'ar', string $separator = ' > '): string
    {
        $path = [];
        $theme = $this;

        while ($theme) {
            array_unshift($path, $theme->getTranslation('name', $locale));
            $theme = $theme->parent;
        }

        return implode($separator, $path);
    }

    /**
     * Get theme name accessor (backward compatibility with theme_ar/theme_en)
     */
    public function getNameAttribute(): array
    {
        return [
            'ar' => $this->theme_ar ?? '',
            'en' => $this->theme_en ?? '',
        ];
    }

    /**
     * Set theme name mutator (backward compatibility with theme_ar/theme_en)
     */
    public function setNameAttribute(array $value): void
    {
        if (isset($value['ar'])) {
            $this->attributes['theme_ar'] = $value['ar'];
        }
        if (isset($value['en'])) {
            $this->attributes['theme_en'] = $value['en'];
        }
    }
}
