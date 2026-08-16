<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Media\Repositories\LiveChannelRepository;
use App\Domain\Media\Repositories\LiveChannelRepositoryInterface;
use App\Domain\Media\Repositories\MediaEpisodeRepository;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepository;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Media\Services\LiveChannelService;
use App\Domain\Media\Services\MediaPublicVisibility;
use App\Domain\Media\Support\YouTubeClient;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MediaPublicVisibility::class);
        $this->app->tag(MediaPublicVisibility::class, PublicVisibilityProviderInterface::class);

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
            cookiesPath: config('youtube.cookies_path'),
        ));
    }

    public function boot(): void
    {
        // The header's live quick-link points at the first published live channel
        // (display order), rather than a hardcoded slug that 404s if that channel
        // is unpublished/renamed. Reads the service's own 10-min cache — no
        // per-request query — and the header hides the icon when none exists.
        ViewFacade::composer('components.site.header', function (View $view): void {
            $view->with('navLiveChannel', $this->app->make(LiveChannelService::class)->getFirstPublished());
        });
    }
}
