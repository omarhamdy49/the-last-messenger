<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Playlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PlaylistRepository
{
    /**
     * Everything a card or the playlist page needs, columns constrained.
     * Reciter names live in quran_reciter_profile_translations.
     */
    private const WITH = [
        'owner:id,name,avatar_path',
        'items.surah:id,name_ar,name_en,transliteration,verses_count',
        'items.reciter:id,profile_id,style,riwayah,slug',
        'items.reciter.profile:id,slug,profile_image,country',
        'items.reciter.profile.translations:id,profile_id,locale,name',
    ];

    private function base(): Builder
    {
        return Playlist::query()->with(self::WITH);
    }

    public function forUser(int $userId): Collection
    {
        return $this->base()->where('user_id', $userId)->latest('id')->get();
    }

    public function countForUser(int $userId): int
    {
        return Playlist::query()->where('user_id', $userId)->count();
    }

    /**
     * Public playlists, plus the viewer's own private ones (they render with a
     * "private" badge) so the gallery is also the signed-in user's library.
     */
    public function gallery(?string $search, string $sort, ?int $viewerId = null, int $perPage = 24): LengthAwarePaginator
    {
        return $this->base()
            ->visibleTo($viewerId)
            ->when($search, fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($sort === 'plays', fn (Builder $q) => $q->orderByDesc('plays_count'), fn (Builder $q) => $q->latest('id'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function featured(int $limit = 1): Collection
    {
        return $this->base()->where('is_public', true)->where('is_featured', true)
            ->orderByDesc('plays_count')->limit($limit)->get();
    }

    public function latestPublic(int $limit = 3): Collection
    {
        return $this->base()->where('is_public', true)->latest('id')->limit($limit)->get();
    }

    /** The 404 boundary for show/play. */
    public function findBySlugVisibleTo(string $slug, ?int $userId): ?Playlist
    {
        return $this->base()->where('slug', $slug)->visibleTo($userId)->first();
    }

    /** The 404 boundary for edit/update/destroy/visibility. */
    public function findOwnedBySlug(string $slug, int $userId): ?Playlist
    {
        return $this->base()->where('slug', $slug)->where('user_id', $userId)->first();
    }

    /**
     * @param  array<string, mixed>  $filters  search, public ('1'|'0'), featured ('1'|'0')
     */
    public function adminList(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Playlist::query()
            ->with('owner:id,name,email')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('owner', fn (Builder $o) => $o->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'))))
            ->when(in_array($filters['public'] ?? null, ['1', '0'], true), fn (Builder $q) => $q->where('is_public', $filters['public'] === '1'))
            ->when(in_array($filters['featured'] ?? null, ['1', '0'], true), fn (Builder $q) => $q->where('is_featured', $filters['featured'] === '1'))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
