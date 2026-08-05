<div class="inline-flex items-center gap-1 rounded-xl bg-gray-100 p-1 dark:bg-[#0f3460]"
     role="group" aria-label="{{ __('hadith.text_language') }}">
    <button type="button" @click="textLanguage = 'ar'"
            :aria-pressed="textLanguage === 'ar' ? 'true' : 'false'"
            :class="textLanguage === 'ar'
                ? 'bg-white text-[#0056b3] shadow-sm dark:bg-[#16213e] dark:text-[#64CCC5]'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
            class="min-h-11 rounded-lg px-4 text-sm font-bold transition
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
        العربية
    </button>
    <button type="button" @click="textLanguage = 'en'"
            :aria-pressed="textLanguage === 'en' ? 'true' : 'false'"
            :class="textLanguage === 'en'
                ? 'bg-white text-[#0056b3] shadow-sm dark:bg-[#16213e] dark:text-[#64CCC5]'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
            class="min-h-11 rounded-lg px-4 text-sm font-bold transition
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
        English
    </button>
</div>
