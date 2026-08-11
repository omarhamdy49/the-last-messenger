<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\YouTubePlaylistData;
use App\Domain\Media\DTOs\YouTubeVideoData;
use App\Domain\Media\Enums\YouTubeSyncStatus;
use App\Domain\Media\Exceptions\YouTubeException;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\YouTubeSyncRun;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Media\Support\YouTubeClient;
use App\Domain\Media\Support\YouTubeUrl;
use App\Jobs\SyncYouTubePlaylistJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Orchestrates importing YouTube data into the Media domain.
 *
 *  - analyzeSource():   read-only preview for the admin modal.
 *  - syncEpisode():     refresh one existing episode (synchronous).
 *  - processRun():      import a whole source (driven by the queued job),
 *                       advancing a YouTubeSyncRun for live progress.
 *
 * A "source" is either a playlist URL or a single video URL; the client
 * normalises a video to a one-entry playlist so both take the same path.
 *
 * Episodes are matched to playlist items by their YouTube video id (parsed
 * from the stored embed URL), so re-syncing updates in place instead of
 * duplicating. Program text + images are filled non-destructively.
 */
class YouTubeSyncService
{
    public function __construct(
        private readonly YouTubeClient $client,
        private readonly MediaEpisodeService $episodes,
        private readonly MediaEpisodeRepositoryInterface $episodeRepo,
        private readonly MediaProgramRepositoryInterface $programRepo,
    ) {}

    /**
     * Read-only preview for the confirmation modal. Accepts a playlist URL or
     * a single video URL — a video previews as a one-entry list.
     *
     * @return array{playlist_id:string,is_playlist:bool,title:string,channel:?string,thumbnail:?string,count:int,entries:list<array{video_id:string,title:string,position:int}>}
     *
     * @throws YouTubeException
     */
    public function analyzeSource(string $url): array
    {
        $playlist = $this->client->fetchSource($url);

        // Slug suggestion for the create form. Str::slug transliterates
        // Arabic (…الحصن → …alhsn), so the slug reads from the title rather
        // than falling back to the opaque playlist id.
        $slug = Str::limit(Str::slug($playlist->title), 80, '');

        return [
            'playlist_id' => $playlist->id,
            'is_playlist' => $playlist->id !== '',
            'suggested_slug' => $slug !== '' ? $slug : Str::slug($playlist->id ?: ($playlist->entries[0]->id ?? '')),
            'title' => $playlist->title,
            'channel' => $playlist->channel,
            'thumbnail' => $playlist->thumbnailUrl,
            'count' => $playlist->count(),
            'entries' => array_map(static fn ($e): array => [
                'video_id' => $e->id,
                'title' => $e->title,
                'position' => $e->position,
            ], $playlist->entries),
        ];
    }

    /**
     * Read-only fetch of one video's metadata for prefilling the episode
     * form (title, description, transcript, duration, thumbnail).
     *
     * @return array{video_id:string,embed_url:string,locale:string,title:string,description:string,transcript:?string,duration_seconds:?int,thumbnail_url:?string}
     *
     * @throws YouTubeException
     */
    public function previewVideo(string $url): array
    {
        $videoId = YouTubeUrl::videoId($url);
        if ($videoId === null) {
            throw new YouTubeException(__('admin.yt_video_url_invalid'));
        }

        $video = $this->fetchFullVideo($videoId);

        return [
            'video_id' => $video->id,
            'embed_url' => YouTubeUrl::embedUrl($video->id),
            'locale' => $this->detectLocale($video->title.' '.$video->description),
            'title' => $video->title,
            'description' => $video->description,
            'transcript' => $video->transcript,
            'duration_seconds' => $video->durationSeconds,
            'thumbnail_url' => $video->thumbnailUrl ?: 'https://i.ytimg.com/vi/'.$video->id.'/hqdefault.jpg',
        ];
    }

    /**
     * Register an import and queue the job that performs it. The returned run
     * is the handle the admin modal polls for progress.
     *
     * $items is the mapping the admin confirmed in the modal — one entry per
     * video with the episode number and title to use. NULL imports every
     * entry in playlist order with its YouTube title.
     *
     * @param  list<array{video_id:string, episode_number:int, title:string}>|null  $items
     */
    public function startRun(int $programId, string $playlistUrl, ?int $userId = null, ?array $items = null): YouTubeSyncRun
    {
        $run = YouTubeSyncRun::create([
            'program_id' => $programId,
            'playlist_url' => $playlistUrl,
            'items' => $items ?: null,
            'status' => YouTubeSyncStatus::Pending,
            'created_by' => $userId,
        ]);

        SyncYouTubePlaylistJob::dispatch($run->id);

        return $run;
    }

    /**
     * Progress snapshot for a run, or null when the run does not exist.
     *
     * @return array{status:string,is_terminal:bool,total:int,processed:int,created:int,updated:int,percent:int,message:?string,error:?string}|null
     */
    public function getRunStatus(int $runId): ?array
    {
        $run = YouTubeSyncRun::find($runId);

        if (! $run instanceof YouTubeSyncRun) {
            return null;
        }

        return [
            'status' => $run->status->value,
            'is_terminal' => $run->status->isTerminal(),
            'total' => $run->total,
            'processed' => $run->processed,
            'created' => $run->created_count,
            'updated' => $run->updated_count,
            'percent' => $run->progressPercent(),
            'message' => $run->message,
            'error' => $run->error,
        ];
    }

    /**
     * Refresh a single existing episode from its own YouTube video.
     *
     * @throws YouTubeException when the episode has no YouTube URL to sync from.
     */
    public function syncEpisode(MediaEpisode $episode): void
    {
        $videoId = YouTubeUrl::videoId($episode->video_url);
        if ($videoId === null) {
            throw new YouTubeException(__('admin.yt_episode_no_url'));
        }

        $video = $this->fetchFullVideo($videoId);
        $this->applyVideoToEpisode($episode, $video);
    }

    /**
     * Import an entire playlist. Entry point for SyncYouTubePlaylistJob.
     * Never throws — failures are recorded on the run.
     */
    public function processRun(YouTubeSyncRun $run): void
    {
        try {
            $program = $this->programRepo->find($run->program_id);
            if (! $program instanceof MediaProgram) {
                $run->markFailed(__('admin.yt_program_missing'));

                return;
            }

            $playlist = $this->client->fetchSource($run->playlist_url);

            // The admin-confirmed mapping, keyed by video id. Anything absent
            // from a non-empty mapping was deselected in the modal.
            $overrides = $this->overridesFor($run);
            $entries = $playlist->entries;
            if ($overrides !== null) {
                $entries = array_values(array_filter(
                    $entries,
                    static fn ($entry): bool => isset($overrides[$entry->id]),
                ));
            }

            // Safety cap — never process an unbounded playlist in one run.
            $cap = (int) config('youtube.max_playlist_episodes', 100);
            if ($cap > 0 && count($entries) > $cap) {
                Log::warning('YouTube playlist truncated to cap', [
                    'run' => $run->id, 'total' => count($entries), 'cap' => $cap,
                ]);
                $entries = array_slice($entries, 0, $cap);
            }

            $run->markRunning(count($entries), __('admin.yt_fetching_playlist'));

            // Index existing episodes by video id for idempotent re-sync.
            $byVideoId = [];
            $maxNumber = 0;
            foreach ($this->episodeRepo->getByProgramForSync($program->id) as $existing) {
                $maxNumber = max($maxNumber, (int) $existing->episode_number);
                $vid = YouTubeUrl::videoId($existing->video_url);
                if ($vid !== null) {
                    $byVideoId[$vid] = $existing;
                }
            }

            $firstVideo = null;
            foreach ($entries as $entry) {
                try {
                    $video = $this->fetchFullVideo($entry->id);
                    $firstVideo ??= $video;

                    // Admin edits win over YouTube's own title/ordering.
                    $override = $overrides[$entry->id] ?? null;
                    if (filled($override['title'] ?? null)) {
                        $video = $video->withTitle((string) $override['title']);
                    }

                    if (isset($byVideoId[$entry->id])) {
                        $existing = $byVideoId[$entry->id];
                        $this->applyVideoToEpisode($existing, $video, $override['episode_number'] ?? null);
                        $run->recordItem(created: false, message: $this->itemMessage($entry->position, count($entries), $video->title));
                    } else {
                        $number = $override['episode_number'] ?? ++$maxNumber;
                        $maxNumber = max($maxNumber, (int) $number);
                        $byVideoId[$entry->id] = $this->createEpisode($program, $video, (int) $number);
                        $run->recordItem(created: true, message: $this->itemMessage($entry->position, count($entries), $video->title));
                    }
                } catch (\Throwable $e) {
                    Log::warning('YouTube episode sync skipped', ['video' => $entry->id, 'error' => $e->getMessage()]);
                    $run->recordSkip($this->itemMessage($entry->position, count($entries), $entry->title).' — '.__('admin.yt_item_skipped'));
                }
            }

            $this->updateProgramFromPlaylist($program, $playlist, $firstVideo);
            $run->markCompleted(__('admin.yt_done'));
        } catch (\Throwable $e) {
            Log::error('YouTube playlist sync failed', ['run' => $run->id, 'error' => $e->getMessage()]);
            $run->markFailed($e->getMessage());
        }
    }

    /**
     * The admin-confirmed mapping keyed by video id, or null when the run
     * carries none (import everything, YouTube order and titles).
     *
     * @return array<string, array{episode_number:int|null, title:string|null}>|null
     */
    private function overridesFor(YouTubeSyncRun $run): ?array
    {
        if (blank($run->items)) {
            return null;
        }

        $map = [];
        foreach ($run->items as $item) {
            if (blank($item['video_id'] ?? null)) {
                continue;
            }
            $map[(string) $item['video_id']] = [
                'episode_number' => isset($item['episode_number']) ? (int) $item['episode_number'] : null,
                'title' => $item['title'] ?? null,
            ];
        }

        return $map !== [] ? $map : null;
    }

    /** Fetch metadata + best-effort transcript for one video. */
    private function fetchFullVideo(string $videoId): YouTubeVideoData
    {
        $video = $this->client->fetchVideo($videoId);

        return $video->withTranscript($this->client->fetchTranscript($videoId));
    }

    private function createEpisode(MediaProgram $program, YouTubeVideoData $video, int $number): MediaEpisode
    {
        // Core fields go through the service (handles slug generation).
        $episode = $this->episodes->createEpisode(new CreateMediaEpisodeData(
            programId: $program->id,
            seasonNumber: 1,
            episodeNumber: $number,
            slug: null,
            title: [],
            description: [],
            transcript: [],
            videoUrl: YouTubeUrl::embedUrl($video->id),
            durationSeconds: $video->durationSeconds,
            isFeatured: false,
            thumbnail: null,
            thumbnailId: null,
            isPublished: true,
            publishAt: null,
            seoTitle: [],
            seoDescription: [],
            seoKeywords: [],
        ));

        $this->writeTranslations($episode, $video);

        // HQ thumbnail saved once per episode (cards use the small conversion).
        try {
            $this->attachMissingEpisodeThumbnail($episode);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->episodeRepo->clearCacheForProgram($program->id);
        $this->episodeRepo->clearCacheForEpisodeNumber($program->id, 1, $number);

        return $episode;
    }

    private function applyVideoToEpisode(MediaEpisode $episode, YouTubeVideoData $video, ?int $episodeNumber = null): void
    {
        $core = ['video_url' => YouTubeUrl::embedUrl($video->id)];
        if ($video->durationSeconds !== null) {
            $core['duration_seconds'] = $video->durationSeconds;
        }
        if ($episodeNumber !== null) {
            $core['episode_number'] = $episodeNumber;
        }
        $this->episodeRepo->update($episode->id, $core);

        $this->writeTranslations($episode, $video);

        // Re-syncs pick up thumbnails for episodes imported before HQ artwork existed.
        try {
            $this->attachMissingEpisodeThumbnail($episode);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->episodeRepo->clearCacheForProgram($episode->program_id);
        $this->episodeRepo->clearCacheForEpisodeNumber($episode->program_id, $episode->season_number, $episode->episode_number);
    }

    /**
     * Upsert title/description/transcript for the detected content locale
     * only — other languages are preserved (syncTranslations skips them).
     */
    private function writeTranslations(MediaEpisode $episode, YouTubeVideoData $video): void
    {
        $locale = $this->detectLocale($video->title.' '.$video->description);

        $episode->syncTranslations([
            'title' => [$locale => $video->title],
            'description' => [$locale => $video->description],
            'transcript' => $video->transcript !== null && $video->transcript !== ''
                ? [$locale => $video->transcript]
                : [],
        ]);
    }

    private function updateProgramFromPlaylist(MediaProgram $program, YouTubePlaylistData $playlist, ?YouTubeVideoData $firstVideo): void
    {
        $locale = $this->detectLocale($playlist->title.' '.($playlist->channel ?? ''));

        $core = [
            'total_episodes' => $this->episodeRepo->getByProgramForSync($program->id)->count(),
            'total_seasons' => 1,
        ];
        // A single-video import carries no playlist id — never clear an
        // existing one by writing the empty placeholder over it.
        if ($playlist->id !== '') {
            $core['youtube_playlist_id'] = $playlist->id;
        }
        if (empty($program->year) && $firstVideo?->year() !== null) {
            $core['year'] = $firstVideo->year();
        }
        $this->programRepo->update($program->id, $core);

        // Translatable text — fill only when empty, never overwrite edits.
        $program->load('translations');
        $translations = [];
        if (blank($program->getTranslation('title', $locale, false))) {
            $translations['title'] = [$locale => $playlist->title];
        }
        if (filled($playlist->description) && blank($program->getTranslation('description', $locale, false))) {
            $translations['description'] = [$locale => $playlist->description];
        }
        if (filled($playlist->channel) && blank($program->getTranslation('host', $locale, false))) {
            $translations['host'] = [$locale => $playlist->channel];
        }
        if ($translations !== []) {
            $program->syncTranslations($translations);
        }

        // Episode thumbnails stay on the YouTube CDN (cards render small),
        // but the program banner is materialized as a real HQ file: the
        // full-bleed hero renders it at 1200px+ and the 480px hqdefault
        // fallback pixelates there. Best-effort — artwork never fails a sync.
        try {
            $this->attachMissingArtwork($program);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->programRepo->clearCacheForModel($program->id);
    }

    /**
     * Download the best-resolution YouTube thumbnail (maxres → sd → hq) of
     * the program's first episode once, and attach it to every missing
     * artwork collection: 'banner' (hero/cover renders the HQ original) and
     * 'poster' (Spatie generates the 300x450 'thumbnail' + 'medium'
     * conversions cards use). Collections that already have media are left
     * untouched — admin uploads are never overwritten. True when attached.
     */
    public function attachMissingArtwork(MediaProgram $program): bool
    {
        $missing = array_values(array_filter(
            ['banner', 'poster'],
            fn (string $collection): bool => $program->getFirstMedia($collection) === null,
        ));
        if ($missing === []) {
            return false;
        }

        $videoId = YouTubeUrl::videoId(
            $this->episodeRepo->getByProgramForSync($program->id)->first()?->video_url
        );
        if ($videoId === null) {
            return false;
        }

        $bytes = $this->downloadBestThumbnail($videoId);
        if ($bytes === null) {
            return false;
        }

        $attached = false;
        foreach ($missing as $collection) {
            $attached = $this->attachImage($program, $bytes, "{$videoId}-{$collection}.jpg", $collection) || $attached;
        }

        if ($attached) {
            $this->programRepo->clearCacheForModel($program->id);
        }

        return $attached;
    }

    /**
     * Same for one episode: HQ original into the 'thumbnail' collection
     * (cards read the 400x225 'thumb' conversion; the player poster facade
     * reads the original). No-op when media already exists or the episode
     * is not a YouTube video. Returns true when attached.
     */
    public function attachMissingEpisodeThumbnail(MediaEpisode $episode): bool
    {
        if ($episode->getFirstMedia('thumbnail') !== null) {
            return false;
        }

        $videoId = YouTubeUrl::videoId($episode->video_url);
        if ($videoId === null) {
            return false;
        }

        $bytes = $this->downloadBestThumbnail($videoId);
        if ($bytes === null || ! $this->attachImage($episode, $bytes, "{$videoId}-thumbnail.jpg", 'thumbnail')) {
            return false;
        }

        $this->episodeRepo->clearCacheForProgram($episode->program_id);
        $this->episodeRepo->clearCacheForEpisodeNumber($episode->program_id, $episode->season_number, $episode->episode_number);

        return true;
    }

    /** Best-available CDN thumbnail bytes (maxres → sd → hq), or null. */
    private function downloadBestThumbnail(string $videoId): ?string
    {
        foreach (YouTubeUrl::thumbnailCandidates($videoId) as $url) {
            try {
                $response = Http::timeout(10)->get($url);
            } catch (\Throwable) {
                continue; // network hiccup — try the next resolution
            }

            if ($response->successful()) {
                return $response->body();
            }
            // maxres/sd 404 on videos without an HD upload — fall through.
        }

        return null;
    }

    private function attachImage(HasMedia $model, string $bytes, string $fileName, string $collection): bool
    {
        try {
            // addMedia() moves the file, so each collection gets its own temp copy.
            $temp = tempnam(sys_get_temp_dir(), 'yt-art-');
            file_put_contents($temp, $bytes);
            $model->addMedia($temp)
                ->usingFileName($fileName)
                ->toMediaCollection($collection);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Arabic content → 'ar', otherwise 'en'. */
    private function detectLocale(string $text): string
    {
        return preg_match('/\p{Arabic}/u', $text) === 1 ? 'ar' : 'en';
    }

    private function itemMessage(int $position, int $total, string $title): string
    {
        $title = Str::limit($title, 80);

        return "({$position}/{$total}) {$title}";
    }
}
