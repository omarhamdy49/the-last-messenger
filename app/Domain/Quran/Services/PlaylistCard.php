<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Support\Facades\Storage;

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
            'reciters' => $playlist->reciters()
                ->map(static fn (QuranResource $r): array => [
                    'name' => $r->displayName($locale),
                    'image' => self::imageUrl($r->image),
                    'url' => route('public.quran.reciter', $r->slug),
                ])
                ->all(),
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }
}
