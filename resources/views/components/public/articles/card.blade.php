{{-- Article → editorial card. @props article (ArticleController::mapCard), variant tile|row, eager --}}
@props(['article', 'variant' => 'tile', 'eager' => false])

<x-public.editorial.card
    :url="$article['url']" :title="$article['title']" :excerpt="$article['excerpt'] ?: null"
    :image="$article['image'] ?? null" icon="newspaper" :badge="$article['category'] ?: null" :featured="$article['isFeatured'] ?? false"
    :meta="[
        ['icon' => 'calendar', 'text' => ($article['hijriDate'] ?? null) ?: ($article['dateLabel'] ?? null)],
        ['icon' => 'clock', 'text' => ! empty($article['readTime']) ? $article['readTime'].' '.__('articles.public.min_read') : null],
        ['icon' => 'eye', 'text' => isset($article['views']) ? number_format($article['views']) : null],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }} />
