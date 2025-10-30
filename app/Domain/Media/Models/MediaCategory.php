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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['name', 'description'];

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
}
