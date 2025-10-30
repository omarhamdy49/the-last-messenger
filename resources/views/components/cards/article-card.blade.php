{{--
    Article Card Component

    Props:
    - $article (Article): Article model instance
    - $showCategory (bool): Show category badge
    - $showStats (bool): Show view count and reading time
    - $variant (string): 'grid' or 'list'

    Usage:
    <x-cards.article-card :article="$article" :showCategory="true" :showStats="true" variant="grid" />
--}}

@props([
    'article',
    'showCategory' => true,
    'showStats' => true,
    'variant' => 'grid'
])

@php
    $readingTime = ceil(str_word_count(strip_tags($article->content)) / 200);
@endphp

<article {{ $attributes->merge(['class' => $variant === 'list' ? 'flex gap-6' : 'block']) }}>
    <a href="{{ route('articles.show', $article->slug) }}"
       class="group {{ $variant === 'list' ? 'flex-shrink-0 w-64' : 'block' }} rounded-2xl overflow-hidden shadow-xl hover-lift transition-all duration-300"
       :class="darkMode ? 'bg-gray-900' : 'bg-white'">

        {{-- Image --}}
        <div class="relative {{ $variant === 'list' ? 'aspect-video' : 'aspect-[16/9]' }} overflow-hidden">
            <img src="{{ $article->featured_image }}"
                 alt="{{ $article->title }}"
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                 loading="lazy">

            {{-- Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

            {{-- Category Badge --}}
            @if($showCategory && $article->category)
            <div class="absolute top-3 start-3 flex items-center gap-2 px-3 py-1.5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold shadow-lg backdrop-blur-sm">
                <i class="w-3 h-3" data-lucide="tag"></i>
                <span>{{ $article->category->name }}</span>
            </div>
            @endif

            {{-- Favorite Button --}}
            <button @click.prevent="toggleFavorite({{ $article->id }})"
                    class="absolute top-3 end-3 w-9 h-9 rounded-full glass flex items-center justify-center hover:scale-110 transition-transform"
                    :class="favorites.includes({{ $article->id }}) ? 'text-red-500' : 'text-white'">
                <i class="w-5 h-5" :data-lucide="favorites.includes({{ $article->id }}) ? 'heart-fill' : 'heart'"></i>
            </button>
        </div>

        {{-- Content --}}
        <div class="p-5 {{ $variant === 'list' ? '' : 'space-y-3' }}">
            {{-- Title --}}
            <h3 class="font-bold text-lg line-clamp-2 group-hover:text-[#64CCC5] transition-colors {{ $variant === 'list' ? 'mb-2' : 'mb-3' }}"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $article->title }}
            </h3>

            {{-- Excerpt --}}
            @if($variant === 'grid')
            <p class="text-sm line-clamp-3 leading-relaxed"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ Str::limit(strip_tags($article->content), 150) }}
            </p>
            @endif

            {{-- Meta --}}
            <div class="flex items-center justify-between text-xs {{ $variant === 'list' ? 'mt-3' : 'mt-4 pt-4 border-t' }}"
                 :class="darkMode ? 'border-gray-800 text-gray-500' : 'border-gray-200 text-gray-600'">
                <div class="flex items-center gap-3">
                    {{-- Author --}}
                    <div class="flex items-center gap-1.5">
                        <i class="w-3.5 h-3.5" data-lucide="user"></i>
                        <span>{{ $article->author->name ?? __('site.admin') }}</span>
                    </div>

                    {{-- Date --}}
                    <div class="flex items-center gap-1.5">
                        <i class="w-3.5 h-3.5" data-lucide="calendar"></i>
                        <span>{{ $article->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                @if($showStats)
                <div class="flex items-center gap-3">
                    {{-- Views --}}
                    <div class="flex items-center gap-1">
                        <i class="w-3.5 h-3.5" data-lucide="eye"></i>
                        <span>{{ number_format($article->views_count ?? 0) }}</span>
                    </div>

                    {{-- Reading Time --}}
                    <div class="flex items-center gap-1">
                        <i class="w-3.5 h-3.5" data-lucide="clock"></i>
                        <span>{{ $readingTime }} {{ __('site.min_read') }}</span>
                    </div>
                </div>
                @endif
            </div>

            {{-- Tags (Grid only) --}}
            @if($variant === 'grid' && $article->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-3">
                @foreach($article->tags->take(3) as $tag)
                <span class="px-2 py-1 rounded-full text-xs font-medium"
                      :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-700'">
                    #{{ $tag->name }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </a>
</article>
