<?php

declare(strict_types=1);

namespace App\Domain\Topics\Services;

use App\Domain\Topics\Enums\TopicItemType;
use App\Domain\Topics\Models\Topic;
use App\Domain\Topics\Models\TopicItem;
use App\Domain\Topics\Repositories\TopicRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Topic Service.
 *
 * Orchestrates topic listing and the cross-module aggregation page. All content
 * (verses / hadiths / articles) is real, linked via topic_items and resolved
 * through the morph map. Itemable models are read via their public relations only,
 * so no concrete domain-model class is imported in this layer.
 */
final class TopicService
{
    public function __construct(
        private readonly TopicRepositoryInterface $repository,
    ) {}

    /**
     * List all topics for the index page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listTopics(string $locale): array
    {
        return $this->repository->allOrderedWithCounts($locale)
            ->map(fn (Topic $topic): array => [
                'slug' => $topic->slug,
                'icon' => $topic->icon,
                'color' => $topic->color,
                'isFeatured' => $topic->is_featured,
                'title' => $topic->getTranslation('title', $locale) ?? $topic->slug,
                'description' => $topic->getTranslation('description', $locale),
                'versesCount' => (int) ($topic->verses_count ?? 0),
                'hadithsCount' => (int) ($topic->hadiths_count ?? 0),
                'articlesCount' => (int) ($topic->articles_count ?? 0),
                'totalCount' => (int) ($topic->verses_count ?? 0)
                    + (int) ($topic->hadiths_count ?? 0)
                    + (int) ($topic->articles_count ?? 0),
                'url' => route('public.topics.show', $topic->slug),
            ])
            ->all();
    }

    /**
     * Build the full aggregation payload for a topic, or null when not found.
     *
     * @return array<string, mixed>|null
     */
    public function getTopicBySlug(string $slug, string $locale): ?array
    {
        $topic = $this->repository->findBySlug($slug);

        if ($topic === null) {
            return null;
        }

        $verses = $this->repository->itemsForTopic($topic->id, TopicItemType::VERSE->value, $locale)
            ->map(fn (TopicItem $item): ?array => $this->mapVerse($item->itemable, $locale))
            ->filter()
            ->values()
            ->all();

        // The Hadith module is being rebuilt; topics carry no hadith items
        // until it returns. The shape is kept so the view needs no change.
        $hadiths = [];

        $articles = $this->repository->itemsForTopic($topic->id, TopicItemType::ARTICLE->value, $locale)
            ->map(fn (TopicItem $item): ?array => $this->mapArticle($item->itemable, $locale))
            ->filter()
            ->values()
            ->all();

        return [
            'slug' => $topic->slug,
            'icon' => $topic->icon,
            'color' => $topic->color,
            'title' => $topic->getTranslation('title', $locale) ?? $topic->slug,
            'description' => $topic->getTranslation('description', $locale),
            'verses' => $verses,
            'hadiths' => $hadiths,
            'articles' => $articles,
            'stats' => [
                'verses' => count($verses),
                'hadiths' => count($hadiths),
                'articles' => count($articles),
            ],
            'url' => route('public.topics.show', $topic->slug),
        ];
    }

    /**
     * Map a Verse itemable to a card array. Reads only public relations/attributes.
     *
     * @return array<string, mixed>|null
     */
    private function mapVerse(?Model $verse, string $locale): ?array
    {
        if ($verse === null) {
            return null;
        }

        $translations = $verse->getRelation('translations');
        $arabic = $translations->firstWhere('lang', 'ar') ?? $translations->first();
        $localized = $translations->firstWhere('lang', $locale);

        $surah = $verse->getRelation('surah');
        $surahName = $surah
            ? ($locale === 'ar' ? $surah->name_ar : ($surah->name_en ?: $surah->name_ar))
            : null;

        return [
            'verseKey' => $verse->verse_key,
            'verseNumber' => (int) $verse->verse_number,
            'text' => $arabic?->text,
            'translation' => $localized && $locale !== 'ar' ? $localized->text : null,
            'surahName' => $surahName,
            'surahId' => (int) $verse->surah_id,
        ];
    }

    /**
     * Map a Hadith itemable to a card array.
     *
     * @return array<string, mixed>|null
     */
    private function mapHadith(?Model $hadith, string $locale): ?array
    {
        if ($hadith === null) {
            return null;
        }

        $translations = $hadith->getRelation('translations');
        $arabic = $translations->firstWhere('language_code', 'ar')
            ?? $translations->firstWhere('is_primary', true)
            ?? $translations->first();
        $localized = $translations->firstWhere('language_code', $locale);

        $book = $hadith->getRelation('book');
        $collection = $book?->getRelation('collection');
        $source = null;
        if ($collection !== null) {
            $source = $locale === 'ar' ? $collection->name_ar : ($collection->name_en ?: $collection->name_ar);
        } elseif ($book !== null) {
            $source = $locale === 'ar' ? $book->title_ar : ($book->title_en ?: $book->title_ar);
        }

        return [
            'id' => (int) $hadith->id,
            'number' => $hadith->hadith_number,
            'text' => $arabic?->text,
            'translation' => $localized && $locale !== 'ar' ? $localized->text : null,
            'source' => $source,
            'grading' => $hadith->grading_status,
            'url' => route('public.hadith.show', $hadith->id),
        ];
    }

    /**
     * Map an Article itemable to a card array.
     *
     * @return array<string, mixed>|null
     */
    private function mapArticle(?Model $article, string $locale): ?array
    {
        if ($article === null) {
            return null;
        }

        $locales = $article->getRelation('locales');
        $localized = $locales->firstWhere('locale', $locale)
            ?? $locales->firstWhere('locale', 'ar')
            ?? $locales->first();

        if ($localized === null || empty($localized->title)) {
            return null;
        }

        $category = $article->getRelation('category');
        $categoryName = null;
        if ($category !== null) {
            $catTranslations = $category->getRelation('translations');
            $catLocalized = $catTranslations?->firstWhere('locale', $locale)
                ?? $catTranslations?->firstWhere('locale', 'ar');
            $categoryName = $catLocalized?->name;
        }

        return [
            'title' => $localized->title,
            'excerpt' => $localized->excerpt,
            'slug' => $localized->slug,
            'category' => $categoryName,
            // Full card-size URL — the raw storage path was unusable as an <img> src.
            'thumb' => $article->thumbUrl('card'),
            'url' => route('public.articles.show', $localized->slug),
        ];
    }
}
