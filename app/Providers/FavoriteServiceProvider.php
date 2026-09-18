<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Quran\Models\QuranResource;
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
        // Singleton, not bind: boot() configures the per-morph eager loads on the
        // resolved instance. A transient binding would hand every later consumer
        // a fresh repository with an empty morph map, so morphWith([]) would
        // reintroduce the translations N+1 this wiring exists to prevent.
        $this->app->singleton(FavoriteRepositoryInterface::class, FavoriteRepository::class);

        $this->app->singleton(FavorableRegistryInterface::class, function (): FavorableRegistry {
            $registry = new FavorableRegistry;

            $registry->register(FavorableType::ARTICLE, Article::class, $this->articleMapper());
            $registry->register(FavorableType::QURAN, Verse::class, $this->quranMapper());
            $registry->register(FavorableType::SEERAH, SeerahEvent::class, $this->seerahMapper());
            $registry->register(FavorableType::MEDIA, MediaProgram::class, $this->mediaMapper());
            $registry->register(FavorableType::RECITER, QuranResource::class, $this->reciterMapper());

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
            $repository->eagerLoadFor(Verse::class, ['translations.resource', 'chapter']);
            $repository->eagerLoadFor(SeerahEvent::class, ['translations']);
            $repository->eagerLoadFor(MediaProgram::class, ['translations']);
            $repository->eagerLoadFor(QuranResource::class, ['translations']);
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
            // Translations are per resource, not per locale: prefer one whose resource
            // is in the reader's language, else any loaded row, else the Arabic script.
            $translation = $model->translations->first(
                static fn ($row): bool => ($row->resource->language_iso ?? null) === $locale
            ) ?? $model->translations->first();

            $text = (string) ($translation->text ?? $model->text_uthmani ?? '');

            return [
                'title' => __('profile.verse_reference', ['key' => (string) $model->verse_key]),
                'excerpt' => $text !== '' ? mb_substr(strip_tags($text), 0, 160) : null,
                'url' => Route::has('public.quran.surah')
                    ? route('public.quran.surah', $model->chapter_id).'#verse-'.$model->verse_key
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

    /**
     * @return callable(Model, string): array{title: string, excerpt: ?string, url: ?string}
     */
    private function reciterMapper(): callable
    {
        return static function (Model $model, string $locale): array {
            /** @var QuranResource $model */
            // A stale bookmark can point at a resource that is no longer a reciter.
            $isReciter = $model->kind->isReciter();

            return [
                'title' => $model->displayName($locale),
                'excerpt' => $model->style?->label($locale) ?? $model->qirat,
                'url' => ($isReciter && Route::has('public.quran.reciter')) ? route('public.quran.reciter', $model->slug) : null,
            ];
        };
    }
}
