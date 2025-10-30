{{--
    Hadith Card Component

    Props:
    - $hadith (Hadith): Hadith model instance
    - $showNarrator (bool): Show narrator chain
    - $showActions (bool): Show action buttons

    Usage:
    <x-cards.hadith-card :hadith="$hadith" :showNarrator="true" :showActions="true" />
--}}

@props([
    'hadith',
    'showNarrator' => true,
    'showActions' => true
])

<article {{ $attributes->merge(['class' => 'p-6 rounded-2xl shadow-xl hover-lift transition-all duration-300']) }}
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4">
        {{-- Book Badge --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-bold shadow-md">
            <i class="w-4 h-4" data-lucide="book"></i>
            <span>{{ $hadith->book->name }}</span>
        </div>

        {{-- Authenticity Badge --}}
        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
             :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
            @if($hadith->authenticity === 'sahih')
                <i class="w-3 h-3 text-green-500" data-lucide="check-circle"></i>
                <span class="text-green-500">{{ __('hadith.sahih') }}</span>
            @elseif($hadith->authenticity === 'hasan')
                <i class="w-3 h-3 text-blue-500" data-lucide="info"></i>
                <span class="text-blue-500">{{ __('hadith.hasan') }}</span>
            @elseif($hadith->authenticity === 'daif')
                <i class="w-3 h-3 text-yellow-500" data-lucide="alert-triangle"></i>
                <span class="text-yellow-500">{{ __('hadith.daif') }}</span>
            @else
                <i class="w-3 h-3 text-red-500" data-lucide="x-circle"></i>
                <span class="text-red-500">{{ __('hadith.mawdu') }}</span>
            @endif
        </div>
    </div>

    {{-- Hadith Number --}}
    <div class="flex items-center gap-2 mb-3 text-sm"
         :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
        <i class="w-4 h-4" data-lucide="hash"></i>
        <span>{{ __('hadith.number') }}: {{ $hadith->hadith_number }}</span>
        @if($hadith->chapter)
        <span class="mx-2">•</span>
        <i class="w-4 h-4" data-lucide="folder"></i>
        <span>{{ $hadith->chapter->name }}</span>
        @endif
    </div>

    {{-- Arabic Text --}}
    <div class="mb-4 p-4 rounded-xl"
         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'"
         dir="rtl">
        <p class="text-lg leading-loose font-[Cairo] font-medium"
           :class="darkMode ? 'text-white' : 'text-gray-900'">
            {{ $hadith->text_ar }}
        </p>
    </div>

    {{-- English Translation --}}
    <div class="mb-4 p-4 rounded-xl"
         :class="darkMode ? 'bg-gray-800/30' : 'bg-blue-50/50'">
        <p class="text-sm leading-relaxed"
           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            {{ $hadith->text_en }}
        </p>
    </div>

    {{-- Narrator Chain --}}
    @if($showNarrator && $hadith->narratorChain->isNotEmpty())
    <div class="mb-4">
        <h4 class="text-sm font-semibold mb-2 flex items-center gap-2"
            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            <i class="w-4 h-4" data-lucide="users"></i>
            {{ __('hadith.narrator_chain') }}
        </h4>
        <div class="flex flex-wrap items-center gap-2">
            @foreach($hadith->narratorChain as $index => $narrator)
                <a href="{{ route('hadith.narrator', $narrator->id) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium hover:scale-105 transition-transform"
                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    {{ $narrator->name }}
                </a>
                @if(!$loop->last)
                <i class="w-3 h-3 text-gray-500" data-lucide="arrow-left"></i>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Topics/Tags --}}
    @if($hadith->topics->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($hadith->topics as $topic)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
              :class="darkMode ? 'bg-[#0056b3]/20 text-[#64CCC5]' : 'bg-[#0056b3]/10 text-[#0056b3]'">
            <i class="w-3 h-3" data-lucide="tag"></i>
            {{ $topic }}
        </span>
        @endforeach
    </div>
    @endif

    {{-- Actions --}}
    @if($showActions)
    <div class="flex items-center justify-between pt-4 border-t"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <div class="flex items-center gap-2">
            {{-- Bookmark --}}
            <button @click="toggleBookmark({{ $hadith->id }})"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all hover:scale-105"
                    :class="[
                        bookmarks.includes({{ $hadith->id }}) ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md' : '',
                        darkMode && !bookmarks.includes({{ $hadith->id }}) ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : '',
                        !darkMode && !bookmarks.includes({{ $hadith->id }}) ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : ''
                    ]">
                <i class="w-4 h-4" :data-lucide="bookmarks.includes({{ $hadith->id }}) ? 'bookmark-check' : 'bookmark'"></i>
                <span>{{ __('hadith.bookmark') }}</span>
            </button>

            {{-- Share --}}
            <button @click="share({{ $hadith->id }})"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all hover:scale-105"
                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i class="w-4 h-4" data-lucide="share-2"></i>
                <span>{{ __('hadith.share') }}</span>
            </button>
        </div>

        {{-- View Details --}}
        <a href="{{ route('hadith.show', $hadith->id) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-[#0056b3] hover:bg-[#0056b3]/10 transition-colors">
            <span>{{ __('hadith.view_details') }}</span>
            <i class="w-4 h-4" data-lucide="arrow-right"></i>
        </a>
    </div>
    @endif
</article>
