<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Events\PlaylistPlayed;
use App\Domain\Quran\Import\QuranFoundation\Client as QfClient;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\ChapterRecitationsHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\MushafsHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\RecitationsHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\TafsirsHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\TranslationsHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\Handlers\WordGlossesHandler;
use App\Domain\Quran\Import\QuranFoundation\Sync\MutationApplier;
use App\Domain\Quran\Import\QuranFoundation\TokenProvider;
use App\Domain\Quran\Import\Support\Throttle;
use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\FallbackChatClient;
use App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClient;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepository;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepository;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\ContentRowRepositoryInterface;
use App\Domain\Quran\Repositories\EloquentAudioRepository;
use App\Domain\Quran\Repositories\EloquentChapterRepository;
use App\Domain\Quran\Repositories\EloquentContentRowRepository;
use App\Domain\Quran\Repositories\EloquentImportRepository;
use App\Domain\Quran\Repositories\EloquentLanguageRepository;
use App\Domain\Quran\Repositories\EloquentMushafRepository;
use App\Domain\Quran\Repositories\EloquentQuranApiRepository;
use App\Domain\Quran\Repositories\EloquentQuranExportRepository;
use App\Domain\Quran\Repositories\EloquentResourceRepository;
use App\Domain\Quran\Repositories\EloquentSearchRepository;
use App\Domain\Quran\Repositories\EloquentSectionRepository;
use App\Domain\Quran\Repositories\EloquentSyncRepository;
use App\Domain\Quran\Repositories\EloquentVerseRepository;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Repositories\LanguageRepositoryInterface;
use App\Domain\Quran\Repositories\MushafRepositoryInterface;
use App\Domain\Quran\Repositories\QuranApiRepositoryInterface;
use App\Domain\Quran\Repositories\QuranExportRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\SearchRepositoryInterface;
use App\Domain\Quran\Repositories\SectionRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Quran\Services\QuranPublicVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use App\Jobs\RecordPlaylistPlayJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class QuranServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Compose cross-boundary relations here so neither model imports the other.
        Playlist::resolveRelationUsing('owner', fn (Playlist $playlist): BelongsTo => $playlist
            ->belongsTo(User::class, 'user_id', 'id', 'owner'));
        User::resolveRelationUsing('quranPlaylists', fn (User $user): HasMany => $user
            ->hasMany(Playlist::class, 'user_id', 'id'));

        Event::listen(PlaylistPlayed::class, function (PlaylistPlayed $event): void {
            RecordPlaylistPlayJob::dispatch($event->playlistId);
        });
    }

    public function register(): void
    {
        // Quran Foundation client (006). The token provider and throttle are
        // singletons so one process shares one token and one rate-limit clock.
        $this->app->singleton(TokenProvider::class);
        $this->app->singleton(QfClient::class, fn ($app): QfClient => new QfClient(
            $app->make(TokenProvider::class),
            new Throttle((int) config('quran.qf.rate_limit_per_second', 5)),
        ));
        $this->app->singleton(Endpoints::class);
        $this->app->bind(ImportRepositoryInterface::class, EloquentImportRepository::class);
        $this->app->bind(SyncRepositoryInterface::class, EloquentSyncRepository::class);
        $this->app->bind(ChapterRepositoryInterface::class, EloquentChapterRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, EloquentSectionRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, EloquentLanguageRepository::class);
        $this->app->bind(MushafRepositoryInterface::class, EloquentMushafRepository::class);
        $this->app->bind(VerseRepositoryInterface::class, EloquentVerseRepository::class);
        $this->app->bind(ResourceRepositoryInterface::class, EloquentResourceRepository::class);
        $this->app->bind(ContentRowRepositoryInterface::class, EloquentContentRowRepository::class);
        $this->app->bind(AudioRepositoryInterface::class, EloquentAudioRepository::class);
        $this->app->bind(QuranExportRepositoryInterface::class, EloquentQuranExportRepository::class);
        $this->app->bind(SearchRepositoryInterface::class, EloquentSearchRepository::class);
        $this->app->bind(QuranApiRepositoryInterface::class, EloquentQuranApiRepository::class);
        $this->app->bind(MutationApplier::class, fn ($app): MutationApplier => new MutationApplier(
            $app->make(Endpoints::class),
            [
                $app->make(TranslationsHandler::class),
                $app->make(TafsirsHandler::class),
                new WordGlossesHandler($app->make(ImportRepositoryInterface::class), $app->make(SyncRepositoryInterface::class), SyncResourceGroup::WORD_BY_WORD_TRANSLATIONS),
                new WordGlossesHandler($app->make(ImportRepositoryInterface::class), $app->make(SyncRepositoryInterface::class), SyncResourceGroup::WORD_BY_WORD_TRANSLITERATIONS),
                $app->make(RecitationsHandler::class),
                $app->make(ChapterRecitationsHandler::class),
                $app->make(MushafsHandler::class),
            ],
        ));

        $this->app->singleton(QuranPublicVisibility::class);
        $this->app->tag(QuranPublicVisibility::class, PublicVisibilityProviderInterface::class);

        // Ask Quran AI (DeepSeek + quran.ai MCP)
        $this->app->bind(
            AiInquiryRepositoryInterface::class,
            AiInquiryRepository::class
        );
        $this->app->bind(
            AiInquiryQuotaRepositoryInterface::class,
            AiInquiryQuotaRepository::class
        );

        // Ask Quran AI infrastructure clients (bound per-resolve; the MCP client
        // carries per-request session state, so it must not be a singleton).
        $this->app->bind(
            ChatClientInterface::class,
            function (): ChatClientInterface {
                $provider = (string) config('services.quran_ai.provider', 'deepseek');
                $fallback = (string) config('services.quran_ai.fallback_provider', '');

                $primary = new OpenAiCompatChatClient($provider);

                // An unset (or self-referencing) fallback means no fallback at all.
                if ($fallback === '' || $fallback === $provider) {
                    return $primary;
                }

                return new FallbackChatClient(
                    $primary,
                    new OpenAiCompatChatClient($fallback),
                );
            }
        );
        $this->app->bind(
            QuranMcpClientInterface::class,
            QuranMcpClient::class
        );
    }
}
