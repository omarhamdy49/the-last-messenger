{{--
    Verse Block Component
    Quranic verse display block
--}}

<div x-show="block.type === 'verse'" class="p-4" dir="rtl">
    <template x-if="block.verses && block.verses.length">
        <div class="space-y-3">
            <template x-for="verse in block.verses" :key="verse.number">
                <p class="text-lg leading-loose font-arabic" style="font-family: 'Amiri', 'Cairo', serif;"
                   :class="darkMode ? 'text-green-200' : 'text-green-900'">
                    <span x-text="verse.text"></span>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold ms-1"
                          :class="darkMode ? 'bg-green-800 text-green-200' : 'bg-green-200 text-green-800'"
                          x-text="verse.number"></span>
                </p>
            </template>
            <div class="flex items-center gap-2 pt-2 border-t"
                 :class="darkMode ? 'border-green-800' : 'border-green-200'">
                <i data-lucide="book-open" class="w-4 h-4 text-green-600"></i>
                <span class="text-sm font-semibold" :class="darkMode ? 'text-green-300' : 'text-green-700'"
                      x-text="block.surah_name"></span>
                <span class="text-xs" :class="darkMode ? 'text-green-400' : 'text-green-600'"
                      x-text="block.verses.length > 1 ? ('{{ __('articles.blocks.verses') }} ' + block.verses[0].number + '-' + block.verses[block.verses.length-1].number) : ('{{ __('articles.blocks.verse') }} ' + block.verses[0].number)"></span>
            </div>
        </div>
    </template>
</div>
