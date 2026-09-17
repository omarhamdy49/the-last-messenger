<?php

declare(strict_types=1);

namespace App\Providers\Composition;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Contracts\ContentReference;
use App\Domain\Shared\Contracts\ContentReferenceLookup;
use App\Domain\Shared\Enums\LinkedContentType;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resolves cross-module link ids (verses, hadiths, companions, articles,
 * episodes, tags, categories) to display-ready references.
 *
 * Lives in the Bootstrap layer on purpose: it is the one place allowed to know
 * every module's models (like TopicsServiceProvider and FavoriteServiceProvider),
 * so domain modules can link to each other through the Shared contract alone.
 */
final class ContentReferenceLookupService implements ContentReferenceLookup
{
    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {}

    public function resolve(array $idsByType, string $locale, bool $publicOnly = false): array
    {
        $resolved = [];

        foreach ($idsByType as $typeKey => $ids) {
            $type = LinkedContentType::tryFrom((string) $typeKey);
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));

            if ($type === null || $ids === []) {
                continue;
            }

            $byId = match ($type) {
                LinkedContentType::VERSE => $this->verses($ids),
                LinkedContentType::HADITH => $this->hadiths($ids, $locale, $publicOnly),
                LinkedContentType::MUSLIM => $this->muslims($ids, $locale, $publicOnly),
                LinkedContentType::ARTICLE => $this->articles($ids, $locale, $publicOnly),
                LinkedContentType::EPISODE => $this->episodes($ids, $locale, $publicOnly),
                LinkedContentType::TAG => $this->labelled(Tag::query(), 'tag_id', $ids, $locale, $type),
                LinkedContentType::CATEGORY => $this->labelled(Category::query(), 'category_id', $ids, $locale, $type),
            };

            $resolved[$type->value] = array_values(array_filter(array_map(
                fn (int $id): ?ContentReference => $byId[$id] ?? null,
                $ids,
            )));
        }

        return $resolved;
    }

    public function options(LinkedContentType $type, string $locale): array
    {
        $query = match ($type) {
            LinkedContentType::TAG => Tag::query(),
            LinkedContentType::CATEGORY => Category::query(),
            default => null,
        };

        if ($query === null) {
            return [];
        }

        return $this->withLabels($query, $type === LinkedContentType::TAG ? 'tag_id' : 'category_id', $locale)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Model $model): array => [(int) $model->id => $this->label($model, 'name', $locale)])
            ->all();
    }

    /**
     * Verses have no hidden state (QuranPublicVisibility is a no-op), so
     * $publicOnly changes nothing here.
     *
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function verses(array $ids): array
    {
        return Verse::query()
            ->select(['id', 'surah_id', 'verse_number', 'verse_key'])
            ->with([
                'surah:id,name_ar',
                // ponytail: first Arabic edition wins; prefer the is_default resource if several ever load.
                'translations' => fn ($q) => $q->select(['id', 'verse_id', 'lang', 'text'])->where('lang', 'ar'),
            ])
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Verse $verse): array => [
                (int) $verse->id => new ContentReference(
                    type: LinkedContentType::VERSE,
                    id: (int) $verse->id,
                    title: (string) ($verse->translations->first()?->text ?? $verse->verse_key),
                    subtitle: trim(($verse->surah?->name_ar ?? '').' '.$verse->verse_key),
                    // The surah reader scrolls to #v<n> (resources/views/public/quran/surah.blade.php).
                    url: route('public.quran.surah', $verse->surah_id).'#v'.$verse->verse_number,
                ),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function hadiths(array $ids, string $locale, bool $publicOnly): array
    {
        $query = Hadith::query()
            ->select(['id', 'collection_id', 'book_id', 'chapter_id', 'reference_key', 'number', 'text_ar', 'text_en', 'canonical_reference', 'is_published'])
            ->with([
                'collection:id,slug,title_ar,title_en,is_published',
                'book:id,is_published',
                'chapter:id,is_published',
            ])
            ->whereIn('hadiths.id', $ids);

        if ($publicOnly) {
            $query->publiclyVisible();
        }

        return $query->get()
            ->mapWithKeys(fn (Hadith $hadith): array => [
                (int) $hadith->id => new ContentReference(
                    type: LinkedContentType::HADITH,
                    id: (int) $hadith->id,
                    title: Str::limit((string) ($hadith->text_ar ?? $hadith->text_en), 160),
                    subtitle: $hadith->displayReference($locale),
                    // Same rule as scopePubliclyVisible, evaluated on the loaded ancestors.
                    url: $hadith->is_published
                        && $hadith->collection?->is_published
                        && ($hadith->book_id === null || $hadith->book?->is_published)
                        && ($hadith->chapter_id === null || $hadith->chapter?->is_published)
                            ? route('public.hadith.show', $hadith->collection->slug.':'.$hadith->reference_key)
                            : null,
                ),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function muslims(array $ids, string $locale, bool $publicOnly): array
    {
        $query = Muslim::query()
            ->select(['id', 'slug', 'name_ar', 'name_en', 'is_published'])
            ->whereIn('muslims.id', $ids);

        if ($publicOnly) {
            $this->publicVisibility->apply($query, $locale);
        }

        return $query->get()
            ->mapWithKeys(fn (Muslim $muslim): array => [
                (int) $muslim->id => new ContentReference(
                    type: LinkedContentType::MUSLIM,
                    id: (int) $muslim->id,
                    title: Muslim::preferredName($muslim->name_ar, $muslim->name_en, $locale),
                    url: $muslim->is_published ? route('public.sahaba.show', $muslim->slug) : null,
                ),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function articles(array $ids, string $locale, bool $publicOnly): array
    {
        $query = Article::query()
            ->select(['id', 'thumb', 'status'])
            ->with(['locales' => fn ($q) => $q
                ->select(['id', 'article_id', 'locale', 'title', 'slug', 'is_ready'])
                ->whereIn('locale', array_unique([$locale, 'ar']))])
            ->whereIn('articles.id', $ids);

        if ($publicOnly) {
            $this->publicVisibility->apply($query, $locale);
        }

        return $query->get()
            ->mapWithKeys(function (Article $article) use ($locale): array {
                $row = $article->locales->firstWhere('locale', $locale) ?? $article->locales->firstWhere('locale', 'ar');
                $ready = $article->locales->first(fn ($l): bool => $l->locale === $locale && $l->is_ready)
                    ?? $article->locales->first(fn ($l): bool => $l->locale === 'ar' && $l->is_ready);

                return [(int) $article->id => new ContentReference(
                    type: LinkedContentType::ARTICLE,
                    id: (int) $article->id,
                    title: (string) ($row?->title ?? ''),
                    url: $article->isPublished() && $ready !== null ? route('public.articles.show', $ready->slug) : null,
                    thumbUrl: $article->thumb ? asset('storage/'.$article->thumb) : null,
                )];
            })
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function episodes(array $ids, string $locale, bool $publicOnly): array
    {
        $locales = array_unique([$locale, 'ar']);

        $query = MediaEpisode::query()
            ->select(['id', 'program_id', 'season_number', 'episode_number', 'video_url', 'is_published'])
            ->with([
                'translations' => fn ($q) => $q->select(['id', 'media_episode_id', 'locale', 'title'])->whereIn('locale', $locales),
                'program' => fn ($q) => $q->select(['id', 'slug', 'is_published']),
                'program.translations' => fn ($q) => $q->select(['id', 'media_program_id', 'locale', 'title'])->whereIn('locale', $locales),
            ])
            ->whereIn('media_episodes.id', $ids);

        if ($publicOnly) {
            $this->publicVisibility->apply($query, $locale)
                ->whereHas('program', fn (Builder $program) => $this->publicVisibility->apply($program, $locale));
        }

        return $query->get()
            ->mapWithKeys(function (MediaEpisode $episode) use ($locale): array {
                $program = $episode->program;
                $programTitle = $program instanceof MediaProgram
                    ? ($this->label($program, 'title', $locale) ?: $program->slug)
                    : null;

                return [(int) $episode->id => new ContentReference(
                    type: LinkedContentType::EPISODE,
                    id: (int) $episode->id,
                    title: $this->label($episode, 'title', $locale),
                    subtitle: trim(($programTitle ?? '').' · '.__('media.public.episode_label', ['number' => $episode->episode_number], $locale), ' ·'),
                    url: $episode->is_published && $program?->is_published
                        ? route('public.media.episode', ['slug' => $program->slug, 'episode' => $episode->episode_number])
                            .($episode->season_number > 1 ? '?season='.$episode->season_number : '')
                        : null,
                    // YouTube CDN only: the media-library thumbnail would cost a query per episode.
                    thumbUrl: $episode->youtubeThumbnailUrl(),
                )];
            })
            ->all();
    }

    /**
     * Tags and categories: no public page filters articles by them via URL
     * (the articles index filters client-side), so url stays null.
     *
     * @param  list<int>  $ids
     * @return array<int, ContentReference>
     */
    private function labelled(Builder $query, string $foreignKey, array $ids, string $locale, LinkedContentType $type): array
    {
        return $this->withLabels($query, $foreignKey, $locale)
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Model $model): array => [
                (int) $model->id => new ContentReference(
                    type: $type,
                    id: (int) $model->id,
                    title: $this->label($model, 'name', $locale) ?: (string) $model->slug,
                ),
            ])
            ->all();
    }

    private function withLabels(Builder $query, string $foreignKey, string $locale): Builder
    {
        return $query
            ->select(['id', 'slug'])
            ->with(['translations' => fn ($q) => $q
                ->select(['id', $foreignKey, 'locale', 'name'])
                ->whereIn('locale', array_unique([$locale, 'ar']))]);
    }

    /** Loaded-translation read with Arabic fallback; never triggers a query. */
    private function label(Model $model, string $field, string $locale): string
    {
        /** @var Collection<int, Model> $translations */
        $translations = $model->getRelation('translations');

        return (string) ($translations->firstWhere('locale', $locale)?->{$field}
            ?: $translations->firstWhere('locale', 'ar')?->{$field}
            ?: '');
    }
}
