<?php

declare(strict_types=1);

namespace App\Domain\Topics\Repositories;

use App\Domain\Topics\Models\Topic;
use Illuminate\Database\Eloquent\Collection;

interface TopicRepositoryInterface
{
    /**
     * All topics, ordered (featured first), with per-type item counts loaded.
     *
     * @return Collection<int, Topic>
     */
    public function allOrderedWithCounts(string $locale): Collection;

    /**
     * A single topic by slug with its translations loaded, or null.
     */
    public function findBySlug(string $slug): ?Topic;

    /**
     * Eager-load a topic's items of one morph type with their itemable content
     * and the minimal relations needed to render a card. Returns the items
     * ordered by sort_order.
     *
     * @return Collection<int, \App\Domain\Topics\Models\TopicItem>
     */
    public function itemsForTopic(int $topicId, string $itemableType, string $locale): Collection;

    /**
     * Per-type item counts for a single topic, keyed by morph alias.
     *
     * @return array<string, int>
     */
    public function itemCounts(int $topicId, string $locale): array;

    /**
     * Replace a topic's items for a given morph type with the supplied ids,
     * preserving order. Idempotent — used by the seeder.
     *
     * @param  array<int, int>  $itemableIds
     */
    public function syncItems(int $topicId, string $itemableType, array $itemableIds): void;
}
