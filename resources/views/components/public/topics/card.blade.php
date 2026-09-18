{{--
    Topic → editorial card. Counts are the ones the payload carries; a count of
    zero is not printed, so a card never advertises an empty tab.

    @props topic (TopicService::listTopics payload row), eager
--}}
@props(['topic', 'eager' => false])

<x-public.editorial.card
    :url="$topic['url']"
    :title="$topic['title']"
    :excerpt="$topic['description'] ?: null"
    :icon="$topic['icon'] ?: 'bookmark'"
    :featured="(bool) $topic['isFeatured']"
    :meta="[
        ['icon' => 'book-open', 'text' => $topic['versesCount'] > 0 ? $topic['versesCount'].' '.__('topics.stat_verses') : null],
        ['icon' => 'scroll-text', 'text' => $topic['hadithsCount'] > 0 ? $topic['hadithsCount'].' '.__('topics.stat_hadiths') : null],
        ['icon' => 'file-text', 'text' => $topic['articlesCount'] > 0 ? $topic['articlesCount'].' '.__('topics.stat_articles') : null],
    ]"
    :eager="$eager" {{ $attributes }} />
