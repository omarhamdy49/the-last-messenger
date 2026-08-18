<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\FallbackChatClient;
use App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClient;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepository;
use App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface;
use App\Domain\Quran\Repositories\AiInquiryRepository;
use App\Domain\Quran\Repositories\AiInquiryRepositoryInterface;
use App\Domain\Quran\Repositories\RecitationRepository;
use App\Domain\Quran\Repositories\RecitationRepositoryInterface;
use App\Domain\Quran\Repositories\ReciterRepository;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use App\Domain\Quran\Repositories\RevelationContextRepository;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;
use App\Domain\Quran\Repositories\SurahRepository;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\TafseerRepository;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Quran\Repositories\TafsirResourceRepository;
use App\Domain\Quran\Repositories\TafsirResourceRepositoryInterface;
use App\Domain\Quran\Repositories\TranslationResourceRepository;
use App\Domain\Quran\Repositories\TranslationResourceRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepository;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Quran\Services\QuranPublicVisibility;
use App\Domain\Shared\Contracts\PublicVisibilityProviderInterface;
use Illuminate\Support\ServiceProvider;

class QuranServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuranPublicVisibility::class);
        $this->app->tag(QuranPublicVisibility::class, PublicVisibilityProviderInterface::class);

        $this->app->bind(
            SurahRepositoryInterface::class,
            SurahRepository::class
        );
        $this->app->bind(
            VerseRepositoryInterface::class,
            VerseRepository::class
        );
        $this->app->bind(
            ReciterRepositoryInterface::class,
            ReciterRepository::class
        );
        $this->app->bind(
            RecitationRepositoryInterface::class,
            RecitationRepository::class
        );
        $this->app->bind(
            TafseerRepositoryInterface::class,
            TafseerRepository::class
        );
        $this->app->bind(
            RevelationContextRepositoryInterface::class,
            RevelationContextRepository::class
        );

        // Edition/source metadata (the works), distinct from the verse text
        // that VerseRepository and TafseerRepository serve.
        $this->app->bind(
            TranslationResourceRepositoryInterface::class,
            TranslationResourceRepository::class
        );
        $this->app->bind(
            TafsirResourceRepositoryInterface::class,
            TafsirResourceRepository::class
        );

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
