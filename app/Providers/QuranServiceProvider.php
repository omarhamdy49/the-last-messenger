<?php

declare(strict_types=1);

namespace App\Providers;

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
            \App\Domain\Quran\Repositories\SurahRepositoryInterface::class,
            \App\Domain\Quran\Repositories\SurahRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\VerseRepositoryInterface::class,
            \App\Domain\Quran\Repositories\VerseRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\ReciterRepositoryInterface::class,
            \App\Domain\Quran\Repositories\ReciterRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\RecitationRepositoryInterface::class,
            \App\Domain\Quran\Repositories\RecitationRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\TafseerRepositoryInterface::class,
            \App\Domain\Quran\Repositories\TafseerRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\RevelationContextRepositoryInterface::class,
            \App\Domain\Quran\Repositories\RevelationContextRepository::class
        );

        // Edition/source metadata (the works), distinct from the verse text
        // that VerseRepository and TafseerRepository serve.
        $this->app->bind(
            \App\Domain\Quran\Repositories\TranslationResourceRepositoryInterface::class,
            \App\Domain\Quran\Repositories\TranslationResourceRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\TafsirResourceRepositoryInterface::class,
            \App\Domain\Quran\Repositories\TafsirResourceRepository::class
        );

        // Ask Quran AI (DeepSeek + quran.ai MCP)
        $this->app->bind(
            \App\Domain\Quran\Repositories\AiInquiryRepositoryInterface::class,
            \App\Domain\Quran\Repositories\AiInquiryRepository::class
        );
        $this->app->bind(
            \App\Domain\Quran\Repositories\AiInquiryQuotaRepositoryInterface::class,
            \App\Domain\Quran\Repositories\AiInquiryQuotaRepository::class
        );

        // Ask Quran AI infrastructure clients (bound per-resolve; the MCP client
        // carries per-request session state, so it must not be a singleton).
        $this->app->bind(
            \App\Domain\Quran\Infrastructure\Llm\ChatClientInterface::class,
            function (): \App\Domain\Quran\Infrastructure\Llm\ChatClientInterface {
                $provider = (string) config('services.quran_ai.provider', 'deepseek');
                $fallback = (string) config('services.quran_ai.fallback_provider', '');

                $primary = new \App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient($provider);

                // An unset (or self-referencing) fallback means no fallback at all.
                if ($fallback === '' || $fallback === $provider) {
                    return $primary;
                }

                return new \App\Domain\Quran\Infrastructure\Llm\FallbackChatClient(
                    $primary,
                    new \App\Domain\Quran\Infrastructure\Llm\OpenAiCompatChatClient($fallback),
                );
            }
        );
        $this->app->bind(
            \App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface::class,
            \App\Domain\Quran\Infrastructure\Mcp\QuranMcpClient::class
        );
    }
}
