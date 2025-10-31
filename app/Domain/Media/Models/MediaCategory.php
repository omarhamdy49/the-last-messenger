<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * MediaCategory Model
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string|null $icon
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MediaCategory extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'display_order',
    ];

    /**
     * Translatable attributes
     * Handled by Spatie\Translatable\HasTranslations trait
     * Do NOT add these to $casts as it conflicts with HasTranslations
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get all programs in this category
     */
    public function programs(): HasMany
    {
        return $this->hasMany(MediaProgram::class, 'category_id');
    }

    /**
     * Get active programs in this category
     */
    public function activePrograms(): HasMany
    {
        return $this->programs()
            ->where('status', 'ongoing')
            ->orWhere('status', 'completed');
    }

    /**
     * Scope: Order by display order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get all translations for name attribute
     * Returns the raw JSON decoded to array
     */
    public function getNameTranslations(): array
    {
        $raw = $this->getAttributes()['name'] ?? '{}';
        return is_string($raw) ? json_decode($raw, true) : $raw;
    }

    /**
     * Get all translations for description attribute
     * Returns the raw JSON decoded to array
     */
    public function getDescriptionTranslations(): array
    {
        $raw = $this->getAttributes()['description'] ?? '{}';
        return is_string($raw) ? json_decode($raw, true) : $raw;
    }

    /**
     * Get name in specified language
     */
    public function getNameInLanguage(string $language): ?string
    {
        $translations = $this->getNameTranslations();
        return $translations[$language] ?? $translations['ar'] ?? null;
    }

    /**
     * Get description in specified language
     */
    public function getDescriptionInLanguage(string $language): ?string
    {
        $translations = $this->getDescriptionTranslations();
        return $translations[$language] ?? $translations['ar'] ?? null;
    }
}
