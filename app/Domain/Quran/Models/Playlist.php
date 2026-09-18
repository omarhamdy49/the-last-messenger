<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A visitor-built list of surahs, each with an assigned reciter recording.
 *
 * Shared by an unguessable random slug; `is_public` decides whether anyone
 * with the link (and the gallery) can see it. `scopeVisibleTo()` is the only
 * visibility rule — every public read goes through it.
 */
class Playlist extends Model
{
    protected $table = 'quran_playlists';

    protected $fillable = ['user_id', 'name', 'description', 'scope', 'is_public', 'is_featured'];

    protected $casts = [
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'plays_count' => 'integer',
        'items_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $playlist): void {
            // ponytail: 36^12 keyspace; the retry loop is belt-and-braces.
            do {
                $slug = Str::lower(Str::random(12));
            } while (static::query()->where('slug', $slug)->exists());
            $playlist->slug = $slug;
        });
        static::saved(fn () => QuranCacheKeys::flushPlaylists());
        static::deleted(fn () => QuranCacheKeys::flushPlaylists());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'playlist_id')->orderBy('chapter_id');
    }

    /**
     * Public, or owned by the given user.
     */
    public function scopeVisibleTo(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->where('is_public', true);
            if ($userId !== null) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    /**
     * Distinct chapter reciters behind the loaded items. Needs items.reciter loaded.
     *
     * @return Collection<int, QuranResource>
     */
    public function reciters(): Collection
    {
        return $this->items
            ->map(fn (PlaylistItem $item) => $item->reciter)
            ->filter()
            ->unique('id')
            ->values();
    }
}
