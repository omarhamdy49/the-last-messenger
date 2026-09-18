{{--
    Verse List Component

    Displays verses grouped by mushaf page with page dividers.
    This component loops through verses and renders individual verse cards.

    Expected Alpine.js context:
    - versesByPage: array of { page: number, items: array of verses }
    - fmtNum(n): function to format numbers (Arabic-Indic for Arabic locale)

    Each verse should be rendered using the verse-card component.
--}}

<div x-show="viewMode === 'verses'" class="space-y-6 mb-12">
    <template x-for="grp in versesByPage" :key="'p' + grp.page">
    <div class="space-y-6">
        {{-- Mushaf page divider --}}
        <div class="flex items-center gap-3 pt-2">
            <div class="h-px flex-1 bg-[color:var(--sx-line)]"></div>
            <span class="sx-chip tabular-nums">
                {{ __('quran.nav.page') }} <span x-text="fmtNum(grp.page)"></span>
            </span>
            <div class="h-px flex-1 bg-[color:var(--sx-line)]"></div>
        </div>

        {{-- Verses for this page --}}
        <template x-for="verse in grp.items" :key="verse.id">
            {{-- Each verse card is rendered here via the verse-card component --}}
            <x-public.quran.verse-card />
        </template>
    </div>
    </template>
</div>
