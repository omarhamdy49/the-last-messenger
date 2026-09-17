<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public delivery for media attached to a published article or Seerah event.
 *
 * The media library is encrypted at rest and served only through the admin
 * guard, which left article attachments with no public URL at all — a reader
 * could never play an attached video. This opens the narrowest possible door:
 * a file streams only while it is attached to an article that is actually
 * published, so unattaching or unpublishing revokes access immediately. It is
 * deliberately not a general-purpose media endpoint. Seerah events reuse it
 * because their gallery and media tables mirror the article ones.
 */
final class ArticleMediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function stream(Media $media): Response
    {
        abort_unless($this->isPubliclyReachable($media), 404);

        return $this->mediaService->stream($media);
    }

    /**
     * True when the media is attached to a published article or Seerah event,
     * either as a media item or as a gallery image.
     */
    private function isPubliclyReachable(Media $media): bool
    {
        foreach (['seerah_events_media', 'seerah_events_gallery'] as $table) {
            $attachedToEvent = DB::table($table)
                ->join('seerah_events', 'seerah_events.id', '=', "{$table}.event_id")
                ->where("{$table}.media_id", $media->id)
                ->where('seerah_events.status', 'published')
                ->exists();

            if ($attachedToEvent) {
                return true;
            }
        }

        $attachedAsMediaItem = DB::table('articles_media')
            ->join('articles', 'articles.id', '=', 'articles_media.article_id')
            ->where('articles_media.media_id', $media->id)
            ->where('articles.status', 'published')
            ->whereNull('articles.deleted_at')
            ->exists();

        if ($attachedAsMediaItem) {
            return true;
        }

        return DB::table('articles_gallery')
            ->join('articles', 'articles.id', '=', 'articles_gallery.article_id')
            ->where('articles_gallery.media_id', $media->id)
            ->where('articles.status', 'published')
            ->whereNull('articles.deleted_at')
            ->exists();
    }
}
