<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\YouTubePlaylistData;
use App\Domain\Media\DTOs\YouTubeVideoData;
use App\Domain\Media\Exceptions\YouTubeException;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\YouTubeSyncRun;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Media\Support\YouTubeClient;
use App\Domain\Media\Support\YouTubeUrl;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates importing YouTube data into the Media domain.
 *
 *  - analyzePlaylist(): read-only preview for the admin modal.
 *  - syncEpisode():     refresh one existing episode (synchronous).
 *  - processRun():      import a whole playlist (driven by the queued job),
 *                       advancing a YouTubeSyncRun for live progress.
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
     * Read-only playlist preview for the confirmation modal.
     *
     * @return array{playlist_id:string,title:string,channel:?string,thumbnail:?string,count:int,entries:list<array{video_id:string,title:string,position:int}>}
     *
     * @throws YouTubeException
     */
    public function analyzePlaylist(string $url): array
    {
        $playlist = $this->client->fetchPlaylist($url);

        return [
            'playlist_id' => $playlist->id,
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

            $playlist = $this->client->fetchPlaylist($run->playlist_url);

            // Safety cap — never process an unbounded playlist in one run.
            $entries = $playlist->entries;
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

                    if (isset($byVideoId[$entry->id])) {
                        $this->applyVideoToEpisode($byVideoId[$entry->id], $video);
                        $run->recordItem(created: false, message: $this->itemMessage($entry->position, count($entries), $video->title));
                    } else {
                        $byVideoId[$entry->id] = $this->createEpisode($program, $video, ++$maxNumber);
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
        $this->attachImage($episode, 'thumbnail', $video->thumbnailUrl, $video->id);

        $this->episodeRepo->clearCacheForProgram($program->id);
        $this->episodeRepo->clearCacheForEpisodeNumber($program->id, 1, $number);

        return $episode;
    }

    private function applyVideoToEpisode(MediaEpisode $episode, YouTubeVideoData $video): void
    {
        $core = ['video_url' => YouTubeUrl::embedUrl($video->id)];
        if ($video->durationSeconds !== null) {
            $core['duration_seconds'] = $video->durationSeconds;
        }
        $this->episodeRepo->update($episode->id, $core);

        $this->writeTranslations($episode, $video);
        $this->attachImage($episode, 'thumbnail', $video->thumbnailUrl, $video->id);

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
            'youtube_playlist_id' => $playlist->id,
            'total_episodes' => $this->episodeRepo->getByProgramForSync($program->id)->count(),
            'total_seasons' => 1,
        ];
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

        // Images — prefer the first episode's high-res thumbnail; only attach
        // when the program has none yet (non-destructive on re-sync).
        $thumbUrl = $firstVideo?->thumbnailUrl ?? $playlist->thumbnailUrl;
        $videoId = $firstVideo?->id;
        if (! $program->hasMedia('banner')) {
            $this->attachImage($program, 'banner', $thumbUrl, $videoId);
        }
        if (! $program->hasMedia('poster')) {
            $this->attachImage($program, 'poster', $thumbUrl, $videoId);
        }

        $this->programRepo->clearCacheForModel($program->id);
    }

    /**
     * Download a thumbnail and (re)attach it to a media collection. The temp
     * file is moved into the collection by Spatie; we clean up on failure.
     *
     * @param  MediaEpisode|MediaProgram  $model
     */
    private function attachImage($model, string $collection, ?string $thumbnailUrl, ?string $videoId): void
    {
        $path = $this->client->downloadThumbnail($thumbnailUrl, $videoId);
        if ($path === null) {
            return;
        }

        try {
            $model->clearMediaCollection($collection);
            $model->addMedia($path)->toMediaCollection($collection);
        } catch (\Throwable $e) {
            Log::warning('YouTube thumbnail attach failed', ['collection' => $collection, 'error' => $e->getMessage()]);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /** Arabic content → 'ar', otherwise 'en'. */
    private function detectLocale(string $text): string
    {
        return preg_match('/\p{Arabic}/u', $text) === 1 ? 'ar' : 'en';
    }

    private function itemMessage(int $position, int $total, string $title): string
    {
        $title = \Illuminate\Support\Str::limit($title, 80);

        return "({$position}/{$total}) {$title}";
    }
}
