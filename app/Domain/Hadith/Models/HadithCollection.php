<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HadithCollection extends Model
{
    use HasFactory;

    protected $table = 'hadith_collections';

    protected $fillable = [
        'code',
        'slug',
        'name',
        'description',
        'abbreviation',
        'featured',
        'active',
        'display_order',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'featured' => 'boolean',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(HadithBook::class, 'collection_id');
    }

    public function getNameInLocale(string $locale = 'ar'): string
    {
        return (string) ($this->name[$locale] ?? $this->name['ar'] ?? '');
    }

    public function getDescriptionInLocale(string $locale = 'ar'): ?string
    {
        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    public function getNameArAttribute(): string
    {
        return $this->getNameInLocale('ar');
    }

    public function getNameEnAttribute(): ?string
    {
        return $this->name['en'] ?? null;
    }

    public function getDescriptionArAttribute(): ?string
    {
        return $this->getDescriptionInLocale('ar');
    }

    public function getDescriptionEnAttribute(): ?string
    {
        return $this->description['en'] ?? null;
    }

    public function getSlugArAttribute(): string
    {
        return $this->slug;
    }

    public function getSlugEnAttribute(): string
    {
        return $this->slug;
    }

    public function scopeWithBooks($query)
    {
        return $query->with('books');
    }
}
