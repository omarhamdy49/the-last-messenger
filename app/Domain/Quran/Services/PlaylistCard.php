<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\ReciterProfile;

/**
 * The presentation shape shared by every playlist card (gallery, profile tab,
 * Quran home, playlist hero). Needs a playlist loaded via PlaylistRepository.
 */
final class PlaylistCard
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Playlist $playlist, string $locale): array
    {
        return [
            'slug' => $playlist->slug,
            'name' => $playlist->name,
            'description' => $playlist->description,
            'url' => route('public.quran.playlists.show', $playlist->slug),
            'owner' => ['name' => $playlist->owner?->name ?? '', 'avatar' => $playlist->owner?->avatarUrl()],
            'items' => $playlist->items_count,
            'plays' => $playlist->plays_count,
            'isPublic' => $playlist->is_public,
            'isFeatured' => $playlist->is_featured,
            'scope' => $playlist->scope,
            'reciters' => $playlist->reciterProfiles()
                ->map(fn (ReciterProfile $p) => ['name' => $p->displayName($locale), 'image' => $p->profile_image_url])
                ->all(),
        ];
    }
}
