<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Media\Repositories\LiveChannelRepository;
use App\Domain\Media\Repositories\LiveChannelRepositoryInterface;
use App\Domain\Media\Repositories\MediaEpisodeRepository;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepository;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
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
    }
}
