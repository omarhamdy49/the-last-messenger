<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tag Model
 * Generic tagging system for all content types
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    /**
     * Get articles with this tag
     *
     * @return MorphToMany
     */
    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'taggable', 'taggables');
    }

    /**
     * Scope: Filter by type
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get name in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getNameInLanguage(string $language): ?string
    {
        return $this->name[$language] ?? $this->name['ar'] ?? null;
    }
}

