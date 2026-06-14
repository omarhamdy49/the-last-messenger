<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Media\Repositories\LiveChannelRepository;
use App\Domain\Media\Repositories\LiveChannelRepositoryInterface;
use App\Domain\Media\Repositories\MediaEpisodeRepository;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepository;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Media\Support\YouTubeClient;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MediaProgramRepositoryInterface::class,
            MediaProgramRepository::class
        );

        $this->app->bind(
            MediaEpisodeRepositoryInterface::class,
            MediaEpisodeRepository::class
        );

        $this->app->bind(
            LiveChannelRepositoryInterface::class,
            LiveChannelRepository::class
        );

        // yt-dlp adapter — config-driven, swappable with a fake in tests.
        $this->app->singleton(YouTubeClient::class, static fn (): YouTubeClient => new YouTubeClient(
            binary: (string) config('youtube.yt_dlp_path', 'yt-dlp'),
            timeout: (int) config('youtube.process_timeout', 600),
            subtitleLanguages: (array) config('youtube.subtitle_languages', ['ar', 'en']),
        ));
    }
}
