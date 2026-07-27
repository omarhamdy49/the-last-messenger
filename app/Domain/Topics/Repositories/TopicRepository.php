<?php

declare(strict_types=1);

namespace App\Domain\Topics\Repositories;

use App\Domain\Shared\Services\PublicVisibilityRegistry;
use App\Domain\Topics\Enums\TopicItemType;
use App\Domain\Topics\Models\Topic;
use App\Domain\Topics\Models\TopicItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Eloquent implementation of the Topic repository.
 *
 * All DB access for the Topics module lives here. Itemable content (verses,
 * hadiths, articles) is loaded polymorphically through the morph map; the
 * concrete domain model classes are never imported in this layer.
 */
class TopicRepository implements TopicRepositoryInterface
{
    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {}

    /**
     * Per-morph-type eager-load relations + column selects for itemable content.
     * Keyed by morph alias. Relation strings are resolved by Eloquent against the
     * concrete model the morph map points at — no compile-time class import needed.
     *
     * @return array<string, array<int, string>>
     */
    private function itemableRelations(): array
    {
        return [
            TopicItemType::VERSE->value => [
                'surah:id,name_ar,name_en,revelation_order',
                'translations:id,verse_id,lang,text',
            ],
            TopicItemType::HADITH->value => [
                'book:id,collection_id,title_ar,title_en',
                'book.collection:id,name_ar,name_en',
                'translations:id,hadith_id,language_code,text,is_primary',
            ],
            TopicItemType::ARTICLE->value => [
                'category:id,slug,icon,color',
                'category.translations:id,category_id,locale,name',
                'locales:id,article_id,locale,title,slug,excerpt,is_ready',
            ],
        ];
    }

    public function allOrderedWithCounts(string $locale): Collection
    {
        return Topic::query()
            ->with('translations:id,topic_id,locale,title,description')
            ->withCount([
                'items as verses_count' => fn (Builder $query) => $this->visibleItems(
                    $query->where('itemable_type', TopicItemType::VERSE->value),
                    [TopicItemType::VERSE->value],
                    $locale,
                ),
                'items as hadiths_count' => fn (Builder $query) => $this->visibleItems(
                    $query->where('itemable_type', TopicItemType::HADITH->value),
                    [TopicItemType::HADITH->value],
                    $locale,
                ),
                'items as articles_count' => fn (Builder $query) => $this->visibleItems(
                    $query->where('itemable_type', TopicItemType::ARTICLE->value),
                    [TopicItemType::ARTICLE->value],
                    $locale,
                ),
            ])
            ->ordered()
            ->get(['id', 'slug', 'icon', 'color', 'is_featured', 'display_order']);
    }

    public function findBySlug(string $slug): ?Topic
    {
        return Topic::query()
            ->with('translations:id,topic_id,locale,title,description')
            ->where('slug', $slug)
            ->first(['id', 'slug', 'icon', 'color', 'is_featured', 'display_order']);
    }

    public function itemsForTopic(int $topicId, string $itemableType, string $locale): Collection
    {
        $relations = $this->itemableRelations()[$itemableType] ?? [];

        // Resolve the concrete class for this single morph type from the morph map
        // (registered at boot in TopicsServiceProvider) — no compile-time import.
        $morphClass = Relation::getMorphedModel($itemableType);

        $query = TopicItem::query()
            ->where('topic_id', $topicId)
            ->where('itemable_type', $itemableType);

        $this->visibleItems($query, [$itemableType], $locale);

        return $query
            ->with(['itemable' => function ($morphTo) use ($morphClass, $relations) {
                if ($morphClass !== null && $relations !== []) {
                    $morphTo->morphWith([$morphClass => $relations]);
                }
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'topic_id', 'itemable_type', 'itemable_id', 'sort_order']);
    }

    public function itemCounts(int $topicId, string $locale): array
    {
        $query = TopicItem::query()->where('topic_id', $topicId);
        $this->visibleItems($query, TopicItemType::values(), $locale);

        $rows = $query
            ->select('itemable_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('itemable_type')
            ->pluck('aggregate', 'itemable_type');

        $counts = [];
        foreach (TopicItemType::values() as $type) {
            $counts[$type] = (int) ($rows[$type] ?? 0);
        }

        return $counts;
    }

    /**
     * @param  list<string>  $itemableTypes
     */
    private function visibleItems(Builder $query, array $itemableTypes, string $locale): Builder
    {
        $morphClasses = array_map(
            fn (string $type): string => Relation::getMorphedModel($type)
                ?? throw new LogicException("No topic morph model registered for [{$type}]."),
            $itemableTypes,
        );

        return $query->whereHasMorph(
            'itemable',
            $morphClasses,
            fn (Builder $itemableQuery) => $this->publicVisibility->apply($itemableQuery, $locale),
        );
    }

    public function syncItems(int $topicId, string $itemableType, array $itemableIds): void
    {
        DB::transaction(function () use ($topicId, $itemableType, $itemableIds): void {
            // Remove items of this type that are no longer in the set (idempotency).
            TopicItem::query()
                ->where('topic_id', $topicId)
                ->where('itemable_type', $itemableType)
                ->when($itemableIds !== [], fn ($q) => $q->whereNotIn('itemable_id', $itemableIds))
                ->delete();

            $sort = 0;
            foreach ($itemableIds as $itemableId) {
                TopicItem::query()->updateOrCreate(
                    [
                        'topic_id' => $topicId,
                        'itemable_type' => $itemableType,
                        'itemable_id' => $itemableId,
                    ],
                    ['sort_order' => $sort++],
                );
            }
        });
    }
}
