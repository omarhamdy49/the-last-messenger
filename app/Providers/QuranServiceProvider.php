<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class QuranServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
            \App\Domain\Quran\Infrastructure\Llm\DeepSeekChatClient::class
        );
        $this->app->bind(
            \App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface::class,
            \App\Domain\Quran\Infrastructure\Mcp\QuranMcpClient::class
        );
    }
}
