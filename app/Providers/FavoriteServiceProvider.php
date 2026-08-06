<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Quran\Models\Verse;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Contracts\FavorableRegistryInterface;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Repositories\FavoriteRepositoryInterface;
use App\Domain\Shared\Services\FavorableRegistry;
use App\Models\Article;
use App\Repositories\FavoriteRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the favorites (bookmarks) feature.
 *
 * This provider is the cross-module composition point: it is the only place
 * that knows every bookmarkable model class and how to render it. The Shared
 * favorites service/registry stay decoupled from domain modules. (Bootstrap
 * layer — allowed to depend on every module by design.)
 */
final class FavoriteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FavoriteRepositoryInterface::class, FavoriteRepository::class);

        $this->app->singleton(FavorableRegistryInterface::class, function (): FavorableRegistry {
            $registry = new FavorableRegistry;

            $registry->register(FavorableType::ARTICLE, Article::class, $this->articleMapper());
            $registry->register(FavorableType::QURAN, Verse::class, $this->quranMapper());
            $registry->register(FavorableType::SEERAH, SeerahEvent::class, $this->seerahMapper());
            $registry->register(FavorableType::MEDIA, MediaProgram::class, $this->mediaMapper());

            return $registry;
        });
    }

    public function boot(): void
    {
        // Tell the favorites repository which nested relations to eager-load per
        // morph class so listing mixed bookmarks never N+1s on translations.
        $repository = $this->app->make(FavoriteRepositoryInterface::class);

        if ($repository instanceof FavoriteRepository) {
            $repository->eagerLoadFor(Article::class, ['locales']);
            $repository->eagerLoadFor(Verse::class, ['translations', 'surah']);
            $repository->eagerLoadFor(SeerahEvent::class, ['translations']);
            $repository->eagerLoadFor(MediaProgram::class, ['translations']);
        }
    }

    /**
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function articleMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var Article $model */
            $localeRow = $model->getArticleLocale($locale) ?? $model->getArticleLocale('ar');

            $title = (string) ($localeRow->title ?? '');
            $excerpt = $localeRow->excerpt ?? $localeRow->description ?? null;
            $slug = $localeRow->slug ?? null;

            return [
                'title' => $title,
                'excerpt' => $excerpt !== null ? mb_substr((string) $excerpt, 0, 160) : null,
                'url' => ($slug !== null && Route::has('public.articles.show'))
                    ? route('public.articles.show', $slug)
                    : null,
            ];
        };
    }

    /**
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function hadithMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var Hadith $model */
            // hadith_texts carries one row per locale that HAS text; Arabic is
            // guaranteed present, English is absent for ~7% of records.
            $text = $model->texts->firstWhere('locale', $locale)
                ?? $model->texts->firstWhere('locale', 'ar')
                ?? $model->texts->first();

            $body = (string) ($text->body ?? '');
            $bookTitle = $model->book?->getTranslation('title', $locale)
                ?? $model->book?->getTranslation('title', 'ar')
                ?? null;

            $title = $bookTitle !== null
                ? trim($bookTitle.' — '.__('profile.hadith_number', ['number' => $model->hadith_number]))
                : __('profile.hadith_number', ['number' => $model->hadith_number]);

            $collectionCode = $model->collection?->code;

            return [
                'title' => $title,
                'excerpt' => $body !== '' ? mb_substr($body, 0, 160) : null,
                'url' => ($collectionCode !== null && Route::has('public.hadith.show'))
                    ? route('public.hadith.show', [
                        'collection' => $collectionCode,
                        'number' => $model->hadith_number,
                    ])
                    : null,
            ];
        };
    }

    /**
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function quranMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var Verse $model */
            $translation = $model->translations->firstWhere('lang', $locale)
                ?? $model->translations->firstWhere('lang', 'ar')
                ?? $model->translations->first();

            $text = (string) ($translation->text ?? '');
            $surahNumber = $model->surah?->revelation_order ?? $model->surah?->getKey();

            return [
                'title' => __('profile.verse_reference', ['key' => (string) $model->verse_key]),
                'excerpt' => $text !== '' ? mb_substr($text, 0, 160) : null,
                'url' => ($surahNumber !== null && Route::has('public.quran.surah'))
                    ? route('public.quran.surah', $surahNumber).'#verse-'.$model->verse_number
                    : null,
            ];
        };
    }

    /**
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function seerahMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var SeerahEvent $model */
            $title = $model->getTranslation('title', $locale)
                ?: $model->getTranslation('title', 'ar');
            $description = $model->getTranslation('description', $locale)
                ?: $model->getTranslation('description', 'ar');

            return [
                'title' => (string) $title,
                'excerpt' => $description !== '' && $description !== null
                    ? mb_substr((string) $description, 0, 160)
                    : null,
                'url' => ($model->slug !== null && Route::has('public.seerah.event'))
                    ? route('public.seerah.event', $model->slug)
                    : null,
            ];
        };
    }

    /**
     * Media programs: translations live in media_program_translations via
     * HasLocalizedContent (no Spatie media) — the bookmark card renders the
     * type icon, so no poster/thumbnail is needed here.
     *
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function mediaMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var MediaProgram $model */
            $title = $model->getTranslation('title', $locale)
                ?? $model->getTranslation('title', 'ar')
                ?? '';
            $description = $model->getTranslation('description', $locale)
                ?? $model->getTranslation('description', 'ar');

            return [
                'title' => (string) $title,
                'excerpt' => filled($description) ? mb_substr((string) $description, 0, 160) : null,
                'url' => Route::has('public.media.program')
                    ? route('public.media.program', $model->slug)
                    : null,
            ];
        };
    }
}
