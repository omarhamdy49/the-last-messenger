{{--
    Hadith Block Component
    Hadith text display with collection, grade, and reference
--}}

@props([
    'locale' => 'ar'
])

<div x-show="block.type === 'hadith'" class="p-4">
    <div class="space-y-3">
        <p class="text-base leading-relaxed"
           :class="darkMode ? 'text-blue-200' : 'text-blue-900'"
           :dir="'{{ in_array($locale, ['ar','ur']) ? 'rtl' : 'ltr' }}'"
           x-text="block.text"></p>
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t"
             :class="darkMode ? 'border-blue-800' : 'border-blue-200'">
            <span class="px-2 py-0.5 rounded text-xs font-semibold"
                  :class="darkMode ? 'bg-blue-800 text-blue-200' : 'bg-blue-200 text-blue-800'"
                  x-text="block.collection"></span>
            <span class="text-xs" :class="darkMode ? 'text-blue-400' : 'text-blue-600'"
                  x-text="'#' + block.number"></span>
            <span class="px-2 py-0.5 rounded text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': block.grade === 'sahih',
                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': block.grade === 'hasan',
                    'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': block.grade === 'daif' || block.grade === 'mawdu'
                  }"
                  x-text="block.grade"></span>
        </div>
    </div>
</div>
