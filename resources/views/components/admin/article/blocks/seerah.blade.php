{{--
    Seerah Block Component
    Seerah event block with title, Hijri year, and event type
--}}

<div x-show="block.type === 'seerah'" class="p-4">
    <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
             :class="darkMode ? 'bg-purple-900/50' : 'bg-purple-100'">
            <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
        </div>
        <div>
            <p class="font-bold text-base" :class="darkMode ? 'text-purple-200' : 'text-purple-900'" x-text="block.title"></p>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <span class="text-xs" :class="darkMode ? 'text-purple-400' : 'text-purple-600'"
                      x-text="block.hijri_year ? (block.hijri_year + ' {{ __('articles.blocks.hijri') }}') : ''"></span>
                <span x-show="block.event_type"
                      class="px-2 py-0.5 rounded text-xs font-medium"
                      :class="darkMode ? 'bg-purple-800 text-purple-200' : 'bg-purple-200 text-purple-800'"
                      x-text="block.event_type"></span>
            </div>
        </div>
    </div>
</div>
