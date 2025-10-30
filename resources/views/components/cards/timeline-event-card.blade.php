{{--
    Timeline Event Card Component

    Props:
    - $event (SeerahEvent): Timeline event model instance
    - $showPhase (bool): Show phase indicator

    Usage:
    <x-cards.timeline-event-card :event="$event" :showPhase="true" />
--}}

@props([
    'event',
    'showPhase' => true
])

@php
    $phaseColors = [
        'mecca_early' => ['from' => '#3b82f6', 'to' => '#60a5fa', 'name' => __('timeline.mecca_early')],
        'mecca_middle' => ['from' => '#8b5cf6', 'to' => '#a78bfa', 'name' => __('timeline.mecca_middle')],
        'mecca_late' => ['from' => '#ec4899', 'to' => '#f472b6', 'name' => __('timeline.mecca_late')],
        'migration' => ['from' => '#0056b3', 'to' => '#218838', 'name' => __('timeline.migration')],
        'medina_early' => ['from' => '#10b981', 'to' => '#34d399', 'name' => __('timeline.medina_early')],
        'medina_middle' => ['from' => '#f59e0b', 'to' => '#fbbf24', 'name' => __('timeline.medina_middle')],
        'medina_late' => ['from' => '#ef4444', 'to' => '#f87171', 'name' => __('timeline.medina_late')],
    ];
    $phase = $phaseColors[$event->phase] ?? $phaseColors['migration'];
@endphp

<article {{ $attributes->merge(['class' => 'relative']) }}>
    {{-- Timeline Dot --}}
    <div class="absolute start-0 top-0 w-4 h-4 rounded-full bg-gradient-to-br shadow-lg z-10"
         style="background: linear-gradient(135deg, {{ $phase['from'] }} 0%, {{ $phase['to'] }} 100%);">
    </div>

    {{-- Card --}}
    <div class="ms-8 p-6 rounded-2xl shadow-xl hover-lift transition-all duration-300 group"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                {{-- Phase Badge --}}
                @if($showPhase)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold text-white mb-3 shadow-md"
                     style="background: linear-gradient(135deg, {{ $phase['from'] }} 0%, {{ $phase['to'] }} 100%);">
                    <i class="w-3 h-3" data-lucide="flag"></i>
                    <span>{{ $phase['name'] }}</span>
                </div>
                @endif

                {{-- Title --}}
                <h3 class="text-xl font-bold mb-2 group-hover:text-[#64CCC5] transition-colors"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $event->title }}
                </h3>

                {{-- Dates --}}
                <div class="flex flex-wrap items-center gap-3 text-sm"
                     :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{-- Hijri Date --}}
                    @if($event->hijri_date)
                    <div class="flex items-center gap-1.5">
                        <i class="w-4 h-4" data-lucide="calendar"></i>
                        <span dir="rtl">{{ $event->hijri_date }}</span>
                    </div>
                    @endif

                    {{-- Gregorian Date --}}
                    @if($event->gregorian_date)
                    <div class="flex items-center gap-1.5">
                        <span>•</span>
                        <span>{{ $event->gregorian_date->format('Y-m-d') }}</span>
                    </div>
                    @endif

                    {{-- Location --}}
                    @if($event->location)
                    <div class="flex items-center gap-1.5">
                        <span>•</span>
                        <i class="w-4 h-4" data-lucide="map-pin"></i>
                        <span>{{ $event->location }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Event Type Icon --}}
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg"
                 style="background: linear-gradient(135deg, {{ $phase['from'] }} 0%, {{ $phase['to'] }} 100%);">
                <i class="w-6 h-6 text-white" data-lucide="{{ $event->icon ?? 'star' }}"></i>
            </div>
        </div>

        {{-- Description --}}
        <p class="text-sm leading-relaxed mb-4"
           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            {{ Str::limit($event->description, 200) }}
        </p>

        {{-- Participants --}}
        @if($event->participants->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-xs font-semibold mb-2 flex items-center gap-2"
                :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <i class="w-3.5 h-3.5" data-lucide="users"></i>
                {{ __('timeline.participants') }}
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($event->participants->take(5) as $participant)
                <span class="px-3 py-1 rounded-full text-xs font-medium"
                      :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    {{ $participant->name }}
                </span>
                @endforeach
                @if($event->participants->count() > 5)
                <span class="px-3 py-1 rounded-full text-xs font-medium text-[#64CCC5]">
                    +{{ $event->participants->count() - 5 }} {{ __('timeline.more') }}
                </span>
                @endif
            </div>
        </div>
        @endif

        {{-- Sources --}}
        @if($event->sources->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-xs font-semibold mb-2 flex items-center gap-2"
                :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <i class="w-3.5 h-3.5" data-lucide="book-open"></i>
                {{ __('timeline.sources') }}
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($event->sources->take(3) as $source)
                <span class="px-3 py-1 rounded-full text-xs font-medium"
                      :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    {{ $source->name }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Tags --}}
        @if($event->tags->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($event->tags as $tag)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                  :class="darkMode ? 'bg-[#0056b3]/20 text-[#64CCC5]' : 'bg-[#0056b3]/10 text-[#0056b3]'">
                <i class="w-3 h-3" data-lucide="tag"></i>
                {{ $tag }}
            </span>
            @endforeach
        </div>
        @endif

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-4 border-t"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center gap-2">
                {{-- Bookmark --}}
                <button @click="toggleBookmark({{ $event->id }})"
                        class="p-2 rounded-lg hover:bg-white/10 transition"
                        :class="bookmarks.includes({{ $event->id }}) ? 'text-[#64CCC5]' : (darkMode ? 'text-gray-400' : 'text-gray-600')">
                    <i class="w-4 h-4" :data-lucide="bookmarks.includes({{ $event->id }}) ? 'bookmark-check' : 'bookmark'"></i>
                </button>

                {{-- Share --}}
                <button @click="share({{ $event->id }})"
                        class="p-2 rounded-lg hover:bg-white/10 transition"
                        :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                    <i class="w-4 h-4" data-lucide="share-2"></i>
                </button>
            </div>

            {{-- View Details --}}
            <a href="{{ route('timeline.event', $event->id) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-[#0056b3] hover:bg-[#0056b3]/10 transition-colors">
                <span>{{ __('timeline.view_details') }}</span>
                <i class="w-4 h-4" data-lucide="arrow-right"></i>
            </a>
        </div>
    </div>
</article>
