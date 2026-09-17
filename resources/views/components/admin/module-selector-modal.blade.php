{{--
    Shared cross-module content picker (Quran verse / hadith / sahabi / seerah event).

    Extracted verbatim from admin/articles/_form-wizard.blade.php so any screen can
    reuse it. Communicates only through window events, so it has no coupling to the
    form it sits in:

      IN   open-module-selector  { type, locale, insertMode?: 'block'|'inline', quillBlockId? }
      OUT  module-block-add      { locale, block }
      OUT  module-inline-insert  { quillBlockId, locale, moduleType, data }

    Include it once per page (@once guards the markup) — x-admin.form.wysiwyg pulls
    it in automatically when the `modules` prop is set.
--}}
@once
{{-- ═══════════ MODULE SELECTOR MODAL ═══════════ --}}
<div x-data="moduleSelector()" x-show="show" x-cloak
     @open-module-selector.window="openModal($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>
    <div class="relative w-full max-w-2xl max-h-[85vh] rounded-xl shadow-2xl flex flex-col overflow-hidden"
         :class="darkMode ? 'bg-gray-800' : 'bg-white'" @click.stop>

        {{-- Modal Header --}}
        <div class="px-5 py-4 border-b flex items-center justify-between flex-shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <h3 class="text-lg font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i :data-lucide="moduleType === 'verse' ? 'book-open' : moduleType === 'hadith' ? 'quote' : moduleType === 'sahabi' ? 'users' : 'map-pin'"
                   class="w-5 h-5"></i>
                <span x-text="moduleType === 'verse' ? '{{ __('articles.modules.quran') }}' : moduleType === 'hadith' ? '{{ __('articles.modules.hadith') }}' : moduleType === 'sahabi' ? '{{ __('articles.modules.sahabah') }}' : '{{ __('articles.modules.seerah') }}'"></span>
            </h3>
            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Search & Filters --}}
        <div class="px-5 py-3 border-b flex-shrink-0" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            {{-- Verse: Surah selector --}}
            <div x-show="moduleType === 'verse'" class="space-y-2">
                <template x-if="!selectedSurah">
                    <input type="text" @keydown.enter.prevent x-model="surahFilter" placeholder="{{ __('articles.selector.search_surah') }}"
                           class="w-full px-4 py-2 rounded-lg border text-sm focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                           :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </template>
                <template x-if="selectedSurah">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-lg text-sm font-semibold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                                  x-text="surahName(selectedSurah)"></span>
                            <span class="text-xs text-gray-500" x-text="selectedSurah.verses_count + ' {{ __('articles.selector.verses') }}'"></span>
                        </div>
                        <button type="button" @click="selectedSurah = null; verses = []; selectedVerses = []"
                                class="text-xs text-blue-500 hover:underline">{{ __('articles.selector.change_surah') }}</button>
                    </div>
                </template>
            </div>
            {{-- Hadith: Collection filter + search --}}
            <div x-show="moduleType === 'hadith'" class="flex gap-2">
                <select x-model="hadithCollectionFilter" @change="searchHadith()"
                        class="px-3 py-2 rounded-lg border text-sm flex-shrink-0"
                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('articles.selector.all_collections') }}</option>
                    <template x-for="col in hadithCollections" :key="col.id">
                        <option :value="col.id" x-text="hadithCollectionLabel(col)"></option>
                    </template>
                </select>
                <input type="text" @keydown.enter.prevent x-model="searchQuery" @input.debounce.400ms="searchHadith()"
                       placeholder="{{ __('articles.selector.search_hadith') }}"
                       class="flex-1 px-4 py-2 rounded-lg border text-sm focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            {{-- Sahabi / Seerah: search --}}
            <div x-show="moduleType === 'sahabi' || moduleType === 'seerah'">
                <input type="text" @keydown.enter.prevent x-model="searchQuery" @input.debounce.400ms="searchModule()"
                       :placeholder="moduleType === 'sahabi' ? '{{ __('articles.selector.search_sahabi') }}' : '{{ __('articles.selector.search_seerah') }}'"
                       class="w-full px-4 py-2 rounded-lg border text-sm focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
        </div>

        {{-- Results Area --}}
        <div class="flex-1 overflow-y-auto px-5 py-3 space-y-2" style="max-height: 50vh;">
            {{-- Loading --}}
            <div x-show="loading" class="text-center py-8">
                <div class="w-6 h-6 border-2 border-gray-300 border-t-[#0056b3] rounded-full animate-spin mx-auto"></div>
            </div>

            {{-- Verse: Surah Grid --}}
            <template x-if="moduleType === 'verse' && !selectedSurah && !loading">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <template x-for="surah in filteredSurahs" :key="surah.id">
                        <button type="button" @click="selectSurah(surah)"
                                class="text-start p-3 rounded-lg border transition-colors hover:shadow-sm"
                                :class="darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-green-50'">
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 flex-shrink-0"
                                      x-text="surah.id"></span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm truncate" :dir="contentDirection" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="surahName(surah)"></p>
                                    <p class="text-xs text-gray-500" x-text="surah.verses_count + ' {{ __('articles.selector.ayat') }}'"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </template>

            {{-- Verse: Verse List --}}
            <template x-if="moduleType === 'verse' && selectedSurah && !loading">
                <div class="space-y-1">
                    <template x-for="verse in verses" :key="verse.id">
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                               :class="selectedVerses.includes(verse.id)
                                   ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                                   : (darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-gray-50')">
                            <input type="checkbox" :value="verse.id" x-model.number="selectedVerses"
                                   class="w-4 h-4 mt-1 rounded text-green-600 focus:ring-green-500 flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-mono text-gray-400 me-2" x-text="verse.verse_key"></span>
                                <span class="text-sm leading-relaxed" :dir="contentDirection"
                                      :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
                                      x-text="verse.text ? verse.text.substring(0, 120) + (verse.text.length > 120 ? '...' : '') : ''"></span>
                            </div>
                        </label>
                    </template>
                </div>
            </template>

            {{-- Hadith Results --}}
            <template x-if="moduleType === 'hadith' && !loading">
                <div class="space-y-2">
                    <template x-for="item in results" :key="item.id">
                        <button type="button" @click="selectItem(item)"
                                class="w-full text-start p-3 rounded-lg border transition-colors"
                                :class="selectedItem?.id === item.id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-1 ring-blue-500'
                                    : (darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-blue-50')">
                            <p class="text-sm mb-2 line-clamp-2" :dir="contentDirection" :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
                               x-text="hadithText(item) || '{{ __('articles.selector.no_translation') }}'"></p>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-0.5 rounded font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                      x-text="hadithCollectionName(item)"></span>
                                <span class="text-gray-500" x-text="'#' + (item.number || item.hadith_number || '')"></span>
                                <span class="px-2 py-0.5 rounded font-medium"
                                      :class="{
                                        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': (item.grade || item.grading_status) === 'sahih',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': (item.grade || item.grading_status) === 'hasan',
                                        'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': (item.grade || item.grading_status) === 'daif' || (item.grade || item.grading_status) === 'mawdu'
                                      }"
                                      x-text="item.grade || item.grading_status || ''"></span>
                            </div>
                        </button>
                    </template>
                    <p x-show="!results.length && searchQuery" class="text-sm text-center py-6 text-gray-500">{{ __('articles.selector.no_results') }}</p>
                </div>
            </template>

            {{-- Sahabi Results --}}
            <template x-if="moduleType === 'sahabi' && !loading">
                <div class="space-y-2">
                    <template x-for="item in results" :key="item.id">
                        <button type="button" @click="selectItem(item)"
                                class="w-full text-start p-3 rounded-lg border transition-colors flex items-center gap-3"
                                :class="selectedItem?.id === item.id
                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30 ring-1 ring-amber-500'
                                    : (darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-amber-50')">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 :class="darkMode ? 'bg-amber-900/50' : 'bg-amber-100'">
                                <i data-lucide="user" class="w-5 h-5 text-amber-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm" :dir="contentDirection" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="personName(item)"></p>
                                <p class="text-xs text-gray-500" :dir="contentDirection" x-text="personKunya(item)"></p>
                            </div>
                        </button>
                    </template>
                    <p x-show="!results.length && searchQuery" class="text-sm text-center py-6 text-gray-500">{{ __('articles.selector.no_results') }}</p>
                </div>
            </template>

            {{-- Seerah Results --}}
            <template x-if="moduleType === 'seerah' && !loading">
                <div class="space-y-2">
                    <template x-for="item in results" :key="item.id">
                        <button type="button" @click="selectItem(item)"
                                class="w-full text-start p-3 rounded-lg border transition-colors flex items-start gap-3"
                                :class="selectedItem?.id === item.id
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 ring-1 ring-purple-500'
                                    : (darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-purple-50')">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 :class="darkMode ? 'bg-purple-900/50' : 'bg-purple-100'">
                                <i data-lucide="calendar" class="w-5 h-5 text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm" :dir="contentDirection" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="localizedValue(item.title)"></p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-500" x-text="item.hijri_year ? (item.hijri_year + ' {{ __('articles.blocks.hijri') }}') : ''"></span>
                                    <span x-show="item.event_type" class="px-1.5 py-0.5 rounded text-xs bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300" x-text="item.event_type"></span>
                                </div>
                            </div>
                        </button>
                    </template>
                    <p x-show="!results.length && searchQuery" class="text-sm text-center py-6 text-gray-500">{{ __('articles.selector.no_results') }}</p>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="totalPages > 1" class="flex justify-center gap-2 pt-3">
                <button type="button" @click="prevPage()" :disabled="currentPage <= 1"
                        class="px-3 py-1.5 rounded text-sm border disabled:opacity-30"
                        :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <span class="px-3 py-1.5 text-sm text-gray-500" x-text="currentPage + ' / ' + totalPages"></span>
                <button type="button" @click="nextPage()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1.5 rounded text-sm border disabled:opacity-30"
                        :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="px-5 py-3 border-t flex items-center justify-between flex-shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <span class="text-xs text-gray-500" x-show="moduleType === 'verse' && selectedVerses.length"
                  x-text="selectedVerses.length + ' {{ __('articles.selector.selected') }}'"></span>
            <span x-show="moduleType !== 'verse'"></span>
            <div class="flex gap-2">
                <button type="button" @click="close()"
                        class="px-4 py-2 rounded-lg text-sm"
                        :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" @click="confirmSelection()"
                        :disabled="(moduleType === 'verse' && !selectedVerses.length) || (moduleType !== 'verse' && !selectedItem)"
                        class="px-4 py-2 rounded-lg text-sm bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-text="insertMode === 'inline' ? '{{ __('articles.modules.insert') }}' : '{{ __('articles.selector.add_block') }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function moduleSelector() {
    const surahBrowseUrl = @js(route('admin.articles.modules.surahs'));
    const verseBySurahUrl = @js(url('su/portal/v1/admin/articles/module-options/surahs'));
    const hadithCollectionsUrl = @js(route('admin.articles.modules.hadith-collections'));
    const hadithSearchUrl = @js(route('admin.articles.modules.hadiths'));
    const sahabaBrowseUrl = @js(route('admin.sahaba.browse'));
    const seerahSearchUrl = @js(route('admin.seerah-events.search'));
    return {
        show: false,
        darkMode: window.resolveDarkMode(),
        moduleType: '',
        targetLocale: '',
        insertMode: 'block', // 'block' or 'inline'
        quillBlockId: null,
        loading: false,
        searchQuery: '',
        results: [],
        selectedItem: null,
        currentPage: 1,
        totalPages: 1,

        // Verse-specific
        surahs: [],
        surahFilter: '',
        selectedSurah: null,
        verses: [],
        selectedVerses: [],

        // Hadith-specific
        hadithCollections: [],
        hadithCollectionFilter: '',

        openModal(detail) {
            this.moduleType = detail.type;
            this.targetLocale = detail.locale;
            this.insertMode = detail.insertMode || 'block';
            this.quillBlockId = detail.quillBlockId || null;
            this.show = true;
            this.searchQuery = '';
            this.results = [];
            this.selectedItem = null;
            this.currentPage = 1;
            this.totalPages = 1;
            this.selectedSurah = null;
            this.verses = [];
            this.selectedVerses = [];
            this.surahFilter = '';

            if (this.moduleType === 'verse' && !this.surahs.length) {
                this.loadSurahs();
            }
            if (this.moduleType === 'hadith') {
                if (!this.hadithCollections.length) this.loadHadithCollections();
                this.searchHadith();
            }
            if (this.moduleType === 'sahabi') {
                this.searchModule();
            }
            this.$nextTick(() => lucide.createIcons());
        },

        close() {
            this.show = false;
        },

        get filteredSurahs() {
            if (!this.surahFilter) return this.surahs;
            const q = this.surahFilter.toLowerCase();
            return this.surahs.filter(s =>
                (s.name_ar && s.name_ar.includes(q)) ||
                (s.name_en && s.name_en.toLowerCase().includes(q)) ||
                String(s.id).includes(q)
            );
        },

        get contentDirection() {
            return ['ar', 'ur', 'fa', 'he'].includes(this.targetLocale) ? 'rtl' : 'ltr';
        },

        async loadSurahs() {
            this.loading = true;
            try {
                const url = new URL(surahBrowseUrl, window.location.origin);
                url.searchParams.set('locale', this.targetLocale);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(`Surah lookup failed with status ${res.status}`);
                const json = await res.json();
                this.surahs = Array.isArray(json.data) ? json.data : [];
            } catch (e) { console.error('Failed to load surahs', e); }
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async selectSurah(surah) {
            this.selectedSurah = surah;
            this.loading = true;
            try {
                const url = new URL(`${verseBySurahUrl}/${surah.id}/verses`, window.location.origin);
                url.searchParams.set('locale', this.targetLocale);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(`Verse lookup failed with status ${res.status}`);
                const json = await res.json();
                this.verses = json.data || [];
            } catch (e) { console.error('Failed to load verses', e); }
            this.loading = false;
        },

        async loadHadithCollections() {
            try {
                const res = await fetch(hadithCollectionsUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(`Hadith collection lookup failed with status ${res.status}`);
                const json = await res.json();
                this.hadithCollections = Array.isArray(json.data) ? json.data : [];
            } catch (e) { console.error('Failed to load collections', e); }
        },

        async searchHadith(resetPage = true) {
            this.loading = true;
            if (resetPage) this.currentPage = 1;
            try {
                const url = new URL(hadithSearchUrl, window.location.origin);
                url.searchParams.set('per_page', '10');
                url.searchParams.set('page', this.currentPage);
                url.searchParams.set('locale', this.targetLocale);
                if (this.searchQuery) url.searchParams.set('q', this.searchQuery);
                if (this.hadithCollectionFilter) url.searchParams.set('collection_id', this.hadithCollectionFilter);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(`Hadith lookup failed with status ${res.status}`);
                const json = await res.json();
                this.results = json.data || [];
                this.totalPages = json.meta?.last_page || 1;
            } catch (e) { console.error('Failed to search hadith', e); }
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async searchModule(resetPage = true) {
            if (this.moduleType !== 'sahabi' && (!this.searchQuery || this.searchQuery.length < 2)) {
                this.results = [];
                return;
            }
            this.loading = true;
            if (resetPage) this.currentPage = 1;
            try {
                const url = new URL(this.moduleType === 'sahabi' ? sahabaBrowseUrl : seerahSearchUrl, window.location.origin);
                url.searchParams.set('page', this.currentPage);
                url.searchParams.set('locale', this.targetLocale);
                if (this.searchQuery) url.searchParams.set('q', this.searchQuery);
                if (this.moduleType === 'sahabi') url.searchParams.set('role', 'sahabi');

                const res = await fetch(url);
                if (!res.ok) throw new Error(`Module search failed with status ${res.status}`);
                const json = await res.json();
                this.results = json.data || [];
                this.totalPages = json.meta?.last_page || 1;
            } catch (e) { console.error('Failed to search', e); }
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        selectItem(item) {
            this.selectedItem = this.selectedItem?.id === item.id ? null : item;
        },

        personName(item) {
            return this.targetLocale === 'ar'
                ? (item.name_ar || item.name || item.name_en || '')
                : (item.name_en || item.name || item.name_ar || '');
        },

        personKunya(item) {
            return this.targetLocale === 'ar'
                ? (item.kunya_ar || item.kunya || item.kunya_en || '')
                : (item.kunya_en || item.kunya || item.kunya_ar || '');
        },

        localizedValue(value) {
            if (!value) return '';
            if (typeof value === 'string') return value;
            return value[this.targetLocale] || value.en || value.ar || Object.values(value)[0] || '';
        },

        surahName(surah) {
            return this.targetLocale === 'ar'
                ? (surah.name_ar || surah.name_en || '')
                : (surah.name_en || surah.name_ar || '');
        },

        hadithText(item) {
            return this.targetLocale === 'ar'
                ? (item.text_ar || item.translations?.[0]?.text || item.text_en || '')
                : (item.text_en || item.translations?.[0]?.text || item.text_ar || '');
        },

        hadithCollectionName(item) {
            return this.targetLocale === 'ar'
                ? (item.collection || item.book?.collection?.name_ar || item.book?.title_ar || '')
                : (item.collection_en || item.collection || item.book?.collection?.name_en || item.book?.title_en || '');
        },

        hadithCollectionLabel(collection) {
            return this.targetLocale === 'ar'
                ? (collection.name_ar || collection.name_en || '')
                : (collection.name_en || collection.name_ar || '');
        },

        async prevPage() {
            if (this.currentPage <= 1) return;
            this.currentPage--;
            if (this.moduleType === 'hadith') await this.searchHadith(false);
            else await this.searchModule(false);
        },

        async nextPage() {
            if (this.currentPage >= this.totalPages) return;
            this.currentPage++;
            if (this.moduleType === 'hadith') await this.searchHadith(false);
            else await this.searchModule(false);
        },

        confirmSelection() {
            let block = null;

            if (this.moduleType === 'verse' && this.selectedVerses.length && this.selectedSurah) {
                const selectedVersesData = this.verses
                    .filter(v => this.selectedVerses.includes(v.id))
                    .sort((a, b) => a.verse_number - b.verse_number)
                    .map(v => ({ number: v.verse_number, key: v.verse_key, text: v.text }));
                block = {
                    type: 'verse',
                    surah_id: this.selectedSurah.id,
                    surah_name: this.surahName(this.selectedSurah),
                    verses: selectedVersesData
                };
            } else if (this.moduleType === 'hadith' && this.selectedItem) {
                const item = this.selectedItem;
                block = {
                    type: 'hadith',
                    hadith_id: item.id,
                    text: this.hadithText(item),
                    collection: this.hadithCollectionName(item),
                    number: item.number || item.hadith_number || '',
                    reference_key: item.reference_key || '',
                    grade: item.grade || item.grading_status || ''
                };
            } else if (this.moduleType === 'sahabi' && this.selectedItem) {
                const item = this.selectedItem;
                block = {
                    type: 'sahabi',
                    sahabi_id: item.id,
                    name: this.personName(item),
                    kunya: this.personKunya(item),
                    slug: item.slug || ''
                };
            } else if (this.moduleType === 'seerah' && this.selectedItem) {
                const item = this.selectedItem;
                block = {
                    type: 'seerah',
                    event_id: item.id,
                    title: this.localizedValue(item.title),
                    hijri_year: item.hijri_year || null,
                    event_type: item.event_type || '',
                    slug: item.slug || ''
                };
            }

            if (block) {
                if (this.insertMode === 'inline' && this.quillBlockId) {
                    // Inline insert into existing Quill editor
                    window.dispatchEvent(new CustomEvent('module-inline-insert', {
                        detail: {
                            quillBlockId: this.quillBlockId,
                            locale: this.targetLocale,
                            moduleType: this.moduleType,
                            data: block
                        }
                    }));
                } else {
                    // Add as separate block
                    window.dispatchEvent(new CustomEvent('module-block-add', {
                        detail: { locale: this.targetLocale, block }
                    }));
                }
                this.close();
            }
        }
    };
}
</script>
@endpush
@endonce

@once
@php
    // Multi-line @json(...) breaks Blade's directive-argument parser, so the map is
    // built here and injected as a ready-made JSON literal.
    $moduleLabelsJson = \Illuminate\Support\Js::from([
        'verse' => __('articles.modules.quran'),
        'hadith' => __('articles.modules.hadith'),
        'sahabi' => __('articles.modules.sahabah'),
        'seerah' => __('articles.modules.seerah'),
        'verseWord' => __('articles.blocks.verse'),
        'hijri' => __('articles.blocks.hijri'),
        'viewEvent' => __('articles.embed.view_event'),
    ])->toHtml();
@endphp
@push('scripts')
<script>
    // Labels for the cross-module toolbar buttons and embed cards (every module-enabled editor includes this modal). Injected from
    // Blade because rich-text.js is plain JS with no access to the translator.
    (function applyModuleLabels() {
        const labels = {!! $moduleLabelsJson !!};
        if (window.AdminWysiwygModules) {
            window.AdminWysiwygModules.setLabels(labels);
        } else {
            window.addEventListener('rich-text:ready', () => window.AdminWysiwygModules.setLabels(labels), { once: true });
        }
    })();
</script>
@endpush
@endonce
