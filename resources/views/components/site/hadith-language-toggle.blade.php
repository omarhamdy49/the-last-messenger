@props(['englishAvailable' => true])

<div class="sx-segment"
     role="group" aria-label="{{ __('hadith.text_language') }}">
    <button type="button" @click="textLanguage = 'ar'"
            :aria-pressed="textLanguage === 'ar' ? 'true' : 'false'"
            class="min-h-11 rounded-lg px-4 text-sm font-bold transition
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--sx-accent)]">
        العربية
    </button>
    <button type="button" @click="textLanguage = 'en'" @disabled(! $englishAvailable)
            :aria-pressed="textLanguage === 'en' ? 'true' : 'false'"
            @if (! $englishAvailable) title="{{ __('hadith.english_not_available') }}" @endif
            class="min-h-11 rounded-lg px-4 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-45
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--sx-accent)]">
        English
    </button>
</div>
