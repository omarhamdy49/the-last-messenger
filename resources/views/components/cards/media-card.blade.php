{{--
    Premium Media Card Component (Netflix-style)

    Props:
    - $program (MediaProgram): Media program model instance
    - $variant (string): 'netflix' (vertical poster) or 'standard' (horizontal thumbnail)

    Usage:
    <x-cards.media-card :program="$program" variant="netflix" />
--}}

@props([
    'program',
    'variant' => 'standard'
])

<article class="{{ $variant === 'netflix' ? 'flex-none w-64' : 'flex-none w-80' }}">
    <a href="{{ route('media.show', $program->slug) }}"
       class="premium-card group block rounded-2xl overflow-hidden shadow-xl"
       :class="darkMode ? 'bg-gray-900' : 'bg-white'">

        {{-- Poster/Thumbnail --}}
        <div class="relative {{ $variant === 'netflix' ? 'aspect-[2/3]' : 'aspect-video' }} overflow-hidden">
            <img src="{{ $variant === 'netflix' ? $program->poster : $program->thumbnail }}"
                 alt="{{ $program->title }}"
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                 loading="lazy">

            {{-- Netflix Rank Badge (for netflix variant) --}}
            @if($variant === 'netflix' && isset($program->rank))
            <div class="absolute -top-4 -start-2 leading-none pointer-events-none z-10">
                <span class="text-[8rem] font-black tracking-tighter"
                      style="color: rgba(0,0,0,0.8); -webkit-text-stroke: 3px rgba(0,86,179,0.9);">
                    {{ $program->rank }}
                </span>
            </div>
            @endif

            {{-- Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>

            {{-- Play Button Overlay --}}
            <div class="card-overlay absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center z-20">
                <div class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform">
                    <i class="w-8 h-8 text-black" data-lucide="play"></i>
                </div>
            </div>

            {{-- Meta Badges --}}
            <div class="absolute top-3 start-3 flex flex-col gap-2 z-10">
                {{-- Category --}}
                @if($program->category)
                <span class="glass px-3 py-1 rounded-full text-white text-xs font-semibold">
                    {{ $program->category->name }}
                </span>
                @endif

                {{-- Duration (for standard variant) --}}
                @if($variant === 'standard' && $program->duration)
                <span class="glass px-3 py-1 rounded-full text-white text-xs font-semibold">
                    <i class="w-3 h-3 inline-block me-1" data-lucide="clock"></i>
                    {{ $program->duration }}
                </span>
                @endif
            </div>

            {{-- Rating Badge --}}
            @if($program->rating)
            <div class="absolute top-3 end-3 flex items-center gap-1 px-2 py-1 rounded-full bg-black/60 backdrop-blur-sm">
                <i class="w-3 h-3 text-yellow-400" data-lucide="star"></i>
                <span class="text-white text-xs font-bold">{{ number_format($program->rating, 1) }}</span>
            </div>
            @endif

            {{-- Title Overlay (always visible) --}}
            <div class="absolute bottom-0 start-0 end-0 p-4 z-10">
                <h3 class="font-bold text-white text-sm mb-2 line-clamp-2">
                    {{ $program->title }}
                </h3>
                <div class="flex items-center gap-2 text-xs text-gray-300">
                    @if($program->year)
                    <span>{{ $program->year }}</span>
                    <span>•</span>
                    @endif
                    @if($program->episodes_count)
                    <span>{{ $program->episodes_count }} {{ __('media.episodes') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Content (for standard variant) --}}
        @if($variant === 'standard')
        <div class="p-5">
            <h3 class="font-bold text-lg mb-2 line-clamp-1 group-hover:text-[#64CCC5] transition-colors"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $program->title }}
            </h3>

            <p class="text-sm mb-3 line-clamp-2"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ $program->description }}
            </p>

            {{-- Meta Info --}}
            <div class="flex items-center justify-between text-xs"
                 :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                <div class="flex items-center gap-3">
                    @if($program->views_count)
                    <div class="flex items-center gap-1">
                        <i class="w-3.5 h-3.5" data-lucide="eye"></i>
                        <span>{{ number_format($program->views_count) }}</span>
                    </div>
                    @endif

                    @if($program->created_at)
                    <div class="flex items-center gap-1">
                        <i class="w-3.5 h-3.5" data-lucide="calendar"></i>
                        <span>{{ $program->created_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                <button @click.prevent="toggleWatchlist({{ $program->id }})"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition"
                        :class="watchlist.includes({{ $program->id }}) ? 'text-[#64CCC5]' : ''">
                    <i class="w-4 h-4" :data-lucide="watchlist.includes({{ $program->id }}) ? 'check' : 'plus'"></i>
                </button>
            </div>
        </div>
        @endif
    </a>
</article>
