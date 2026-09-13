<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReciterProfile extends Model
{
    protected $table = 'quran_reciter_profiles';

    protected $fillable = ['identity_key', 'slug', 'country', 'profile_image', 'is_active', 'is_featured', 'display_order', 'followers_count', 'default_recording_id'];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean', 'display_order' => 'integer', 'default_recording_id' => 'integer'];

    public function recordings(): HasMany
    {
        return $this->hasMany(Reciter::class, 'profile_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ReciterProfileTranslation::class, 'profile_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereHas('recordings', fn ($q) => $q->active()->recitationsOnly());
    }

    /**
     * Admin listing filters; every key is an optional GET parameter.
     *
     * @param  array{search?: ?string, status?: ?string, featured?: ?string, country?: ?string, recordings?: ?string, sort?: ?string}  $filters
     */
    public function scopeAdminFilter(Builder $query, array $filters, string $locale): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $query
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('translations', fn ($t) => $t->where('name', 'like', '%'.$search.'%'))
                ->orWhere('slug', 'like', '%'.$search.'%')
                ->orWhereHas('recordings.translations', fn ($t) => $t->where('name', 'like', '%'.$search.'%'))))
            ->when(in_array($filters['status'] ?? null, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->when(in_array($filters['featured'] ?? null, ['1', '0'], true), fn ($q) => $q->where('is_featured', $filters['featured'] === '1'))
            ->when(filled($filters['country'] ?? null), fn ($q) => $q->where('country', $filters['country']))
            ->when(($filters['recordings'] ?? null) === 'multiple', fn ($q) => $q->has('recordings', '>', 1))
            ->when(($filters['recordings'] ?? null) === 'single', fn ($q) => $q->has('recordings', '=', 1))
            ->tap(fn ($q) => match ($filters['sort'] ?? null) {
                'name' => $q->orderByRaw(
                    'COALESCE((SELECT name FROM quran_reciter_profile_translations t WHERE t.profile_id = quran_reciter_profiles.id AND t.locale = ? LIMIT 1), (SELECT name FROM quran_reciter_profile_translations t WHERE t.profile_id = quran_reciter_profiles.id AND t.locale = ? LIMIT 1))',
                    [$locale, 'ar']
                ),
                'recordings' => $q->orderByDesc('recordings_count'),
                'newest' => $q->latest('id'),
                default => $q->orderBy('display_order'),
            })
            ->orderBy('id');
    }

    public function displayName(string $locale): string
    {
        return $this->translations->firstWhere('locale', $locale)?->name ?? $this->translations->firstWhere('locale', 'ar')?->name ?? $this->translations->first()?->name ?? $this->slug;
    }

    public function description(string $locale): ?string
    {
        return $this->translations->firstWhere('locale', $locale)?->description ?? $this->translations->firstWhere('locale', 'ar')?->description;
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profile_image ? (str_starts_with($this->profile_image, 'http') ? $this->profile_image : asset('storage/'.$this->profile_image)) : null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => QuranCacheKeys::flushReciters());
        static::deleted(fn () => QuranCacheKeys::flushReciters());
    }
}
