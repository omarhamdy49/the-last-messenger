<?php

declare(strict_types=1);

namespace App\Domain\Media\Support;

use App\Domain\Media\DTOs\YouTubePlaylistData;
use App\Domain\Media\DTOs\YouTubePlaylistEntry;
use App\Domain\Media\DTOs\YouTubeVideoData;
use App\Domain\Media\Exceptions\YouTubeException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

/**
 * Thin adapter over the yt-dlp binary. Isolates all external-process and
 * HTTP concerns behind a typed interface so the sync service stays pure.
 *
 * Bound as a singleton in MediaServiceProvider; swap with a fake in tests.
 */
class YouTubeClient
{
    /**
     * @param  list<string>  $subtitleLanguages  ordered by preference
     */
    public function __construct(
        private readonly string $binary = 'yt-dlp',
        private readonly int $timeout = 600,
        private readonly array $subtitleLanguages = ['ar', 'en'],
        private readonly ?string $cookiesPath = null,
    ) {}

    /**
     * Args prepended to every yt-dlp invocation. Adds --cookies only when a
     * readable cookies file is configured — a missing/blank path silently
     * falls back to a cookieless run rather than breaking every call.
     *
     * @return list<string>
     */
    private function globalArgs(): array
    {
        return $this->cookiesPath !== null && $this->cookiesPath !== '' && is_readable($this->cookiesPath)
            ? ['--cookies', $this->cookiesPath]
            : [];
    }

    /**
     * Full metadata for one video. Pass a watch/embed/short URL or a bare id.
     *
     * @throws YouTubeException
     */
    public function fetchVideo(string $urlOrId): YouTubeVideoData
    {
        $videoId = YouTubeUrl::videoId($urlOrId);
        if ($videoId === null) {
            throw new YouTubeException("Not a valid YouTube video URL or id: {$urlOrId}");
        }

        $json = $this->runJson([
            '--dump-single-json', '--no-playlist', '--skip-download', '--no-warnings',
            YouTubeUrl::watchUrl($videoId),
        ]);

        return YouTubeVideoData::fromArray($json);
    }

    /**
     * Resolve any supported YouTube source to a playlist-shaped result, so
     * every caller downstream handles one type. A single video comes back as
     * a one-entry playlist with an empty playlist id.
     *
     * @throws YouTubeException
     */
    public function fetchSource(string $url): YouTubePlaylistData
    {
        if (YouTubeUrl::isPlaylist($url)) {
            return $this->fetchPlaylist($url);
        }

        $video = $this->fetchVideo($url);

        return new YouTubePlaylistData(
            id: '',
            title: $video->title,
            description: $video->description,
            channel: $video->channel,
            thumbnailUrl: $video->thumbnailUrl,
            entries: [new YouTubePlaylistEntry(id: $video->id, title: $video->title, position: 1)],
        );
    }

    /**
     * Flat playlist listing (id + title per entry, no per-video extraction).
     * Fast enough to run inside a web request for the "analyze" preview.
     *
     * @throws YouTubeException
     */
    public function fetchPlaylist(string $url): YouTubePlaylistData
    {
        $json = $this->runJson([
            '--flat-playlist', '--dump-single-json', '--no-warnings', $url,
        ]);

        if (empty($json['entries'])) {
            throw new YouTubeException('No videos found in this playlist (or the playlist is private).');
        }

        return YouTubePlaylistData::fromArray($json);
    }

    /**
     * Best-effort transcript: download subtitles (manual first, then
     * auto-generated), convert to VTT and flatten to plain text. Returns
     * null when no track exists — never throws, transcripts are optional.
     */
    public function fetchTranscript(string $urlOrId): ?string
    {
        $videoId = YouTubeUrl::videoId($urlOrId);
        if ($videoId === null) {
            return null;
        }

        $dir = storage_path('app/tmp/yt-'.$videoId.'-'.uniqid());
        File::ensureDirectoryExists($dir);

        try {
            // Match exact + regional variants, e.g. "ar.*,en.*" → ar, ar-orig, en-US…
            $langArg = implode(',', array_map(static fn (string $l): string => $l.'.*', $this->subtitleLanguages));

            // A non-zero exit here is expected and ignored: yt-dlp errors out
            // when *one* requested language is missing or rate-limited (HTTP
            // 429) even after another wrote its track. We judge success by the
            // files actually produced, not the exit code.
            $this->runAllowingFailure([
                '--skip-download', '--write-subs', '--write-auto-subs',
                '--sub-langs', $langArg, '--convert-subs', 'vtt', '--no-warnings',
                '-o', $dir.'/%(id)s.%(ext)s',
                YouTubeUrl::watchUrl($videoId),
            ]);

            // Prefer a track in our preferred language order.
            foreach ($this->subtitleLanguages as $lang) {
                $matches = glob($dir.'/*'.$lang.'*.vtt') ?: [];
                if ($matches !== []) {
                    return $this->parseVtt((string) file_get_contents($matches[0]));
                }
            }

            $any = glob($dir.'/*.vtt') ?: [];

            return $any !== [] ? $this->parseVtt((string) file_get_contents($any[0])) : null;
        } catch (\Throwable) {
            return null;
        } finally {
            File::deleteDirectory($dir);
        }
    }

    /**
     * @param  list<string>  $args
     * @return array<string, mixed>
     *
     * @throws YouTubeException
     */
    private function runJson(array $args): array
    {
        $data = json_decode($this->run($args), true);

        if (! is_array($data)) {
            throw new YouTubeException('yt-dlp returned malformed JSON.');
        }

        return $data;
    }

    /**
     * @param  list<string>  $args
     *
     * @throws YouTubeException
     */
    private function run(array $args): string
    {
        $process = new Process([$this->binary, ...$this->globalArgs(), ...$args]);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (\Throwable $e) {
            throw new YouTubeException("Unable to run yt-dlp: {$e->getMessage()}", previous: $e);
        }

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());

            throw new YouTubeException($error !== '' ? $error : 'yt-dlp exited with an error.');
        }

        return $process->getOutput();
    }

    /**
     * Run yt-dlp and ignore a non-zero exit. Used for subtitle downloads,
     * where partial success (one language written, another 429'd) is normal.
     *
     * @param  list<string>  $args
     */
    private function runAllowingFailure(array $args): void
    {
        $process = new Process([$this->binary, ...$this->globalArgs(), ...$args]);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (\Throwable) {
            // Best-effort: caller inspects produced files instead.
        }
    }

    /**
     * Flatten a WebVTT cue stream to readable text: drop the header, timing
     * lines, cue indices and inline tags, and collapse consecutive duplicate
     * lines (auto-generated captions repeat the rolling line).
     */
    private function parseVtt(string $vtt): string
    {
        $out = [];
        $previous = null;

        foreach (preg_split('/\r\n|\r|\n/', $vtt) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, 'WEBVTT')
                || str_starts_with($line, 'Kind:') || str_starts_with($line, 'Language:')
                || str_contains($line, '-->') || ctype_digit($line)) {
                continue;
            }

            $line = trim((string) preg_replace('/<[^>]+>/', '', $line));

            if ($line === '' || $line === $previous) {
                continue;
            }

            $out[] = $line;
            $previous = $line;
        }

        return trim(implode("\n", $out));
    }
}
