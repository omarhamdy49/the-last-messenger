<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Reciter Model (Quran Reciter/Qari)
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $description
 * @property string|null $style
 * @property string|null $country
 * @property string|null $profile_image
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Reciter extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_reciters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'style',
        'country',
        'profile_image',
        'is_featured',
        'is_active',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the FQCN of the translation model.
     */
    public function getTranslationModelClass(): string
    {
        return ReciterTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($reciter) {
            if (empty($reciter->slug)) {
                $name = $reciter->getTranslation('name', 'en') ?? $reciter->getTranslation('name', 'ar');
                $reciter->slug = Str::slug($name);
            }
        });
    }

    /**
     * Get all recitations recorded by this reciter.
     */
    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class, 'reciter_id');
    }

    /**
     * Get display name based on language
     */
    public function getDisplayName(string $lang = 'ar'): string
    {
        return $this->getTranslation('name', $lang) ?? $this->getTranslation('name', 'ar') ?? '';
    }

    /**
     * Get description based on language
     */
    public function getDescriptionText(string $lang = 'ar'): ?string
    {
        return $this->getTranslation('description', $lang);
    }

    /**
     * Get profile image URL
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }

        return null;
    }

    /**
     * Scope: Featured reciters only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFeatured($query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Active reciters only
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order by display order
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope: Filter by country
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Filter by style
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByStyle($query, string $style)
    {
        return $query->where('style', $style);
    }

    /**
     * Scope: Search by name (multi-language)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$term}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$term}%"]);
        });
    }
}
