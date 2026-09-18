{{-- ═══════════ EPISODE PICKER MODAL ═══════════
     Lives outside the <form> like the module selector, so it hands its
     selection back through a window event rather than reaching into the
     form's Alpine scope. Server-side paginated: a synced channel runs to
     hundreds of episodes, so the list is never fetched whole. --}}
<div x-data="episodePicker()" x-show="show" x-cloak
     @open-episode-picker.window="open()"
     @keydown.escape.window="show && close()"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

    <div class="relative w-full max-w-3xl max-h-[85vh] rounded-xl shadow-2xl flex flex-col overflow-hidden"
         :class="darkMode ? 'bg-gray-800' : 'bg-white'" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <h3 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('articles.actions.assign_episodes') }}
            </h3>
            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    aria-label="{{ __('articles.public.close') }}">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Search + filter --}}
        <div class="p-4 grid gap-3 sm:grid-cols-[1fr_14rem] shrink-0 border-b"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="relative">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                <input type="search" x-model="q" @input.debounce.300ms="page = 1; fetch()"
                       placeholder="{{ __('articles.placeholders.search_episodes') }}"
                       class="w-full ps-9 pe-3 py-2 rounded-lg border text-sm"
                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'">
            </div>
            <select x-model="program" @change="page = 1; fetch()"
                    aria-label="{{ __('articles.fields.program') }}"
                    class="w-full px-3 py-2 rounded-lg border text-sm"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'">
                <option value="">{{ __('articles.fields.all_programs') }}</option>
                <template x-for="p in programs" :key="p.id">
                    <option :value="p.id" x-text="p.label"></option>
                </template>
            </select>
        </div>

        {{-- Results --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-2 min-h-[16rem]">
            <template x-if="loading">
                <div class="py-16 text-center text-sm text-gray-500">{{ __('articles.public.loading') }}</div>
            </template>

            <template x-if="!loading && rows.length === 0">
                <div class="py-16 text-center">
                    <i data-lucide="list-video" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                    <p class="text-sm text-gray-500">{{ __('articles.empty.episodes_search') }}</p>
                </div>
            </template>

            <template x-for="row in rows" :key="row.id">
                <label class="flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-colors"
                       :class="isPicked(row.id)
                            ? 'border-[#0056b3] bg-[#0056b3]/5'
                            : (darkMode ? 'border-gray-700 hover:bg-gray-700/50' : 'border-gray-200 hover:bg-gray-50')">
                    <input type="checkbox" class="w-4 h-4 shrink-0"
                           :checked="isPicked(row.id)" @change="toggle(row)">
                    <img :src="row.thumb" x-show="row.thumb" alt=""
                         class="w-24 h-14 rounded object-cover bg-gray-200 dark:bg-gray-700 shrink-0">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="row.title"></span>
                        <span class="block text-xs text-gray-500 truncate">
                            <span x-text="row.program"></span>
                            <span x-show="row.season">· S<span x-text="row.season"></span></span>
                            <span x-show="row.episode">E<span x-text="row.episode"></span></span>
                        </span>
                    </span>
                    {{-- Unpublished episodes stay pickable but are flagged: the
                         reader filters them out until they go live. --}}
                    <span x-show="!row.is_published"
                          class="shrink-0 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        {{ __('articles.status.draft') }}
                    </span>
                </label>
            </template>
        </div>

        {{-- Pagination + confirm --}}
        <div class="p-4 border-t flex items-center justify-between gap-3 shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="flex items-center gap-2">
                <button type="button" @click="go(-1)" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40"
                        :class="darkMode ? 'border-gray-600' : 'border-gray-300'">
                    {{ __('articles.public.previous') }}
                </button>
                <span class="text-xs text-gray-500 tabular-nums">
                    <span x-text="meta.current_page"></span> / <span x-text="meta.last_page || 1"></span>
                </span>
                <button type="button" @click="go(1)" :disabled="meta.current_page >= (meta.last_page || 1)"
                        class="px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40"
                        :class="darkMode ? 'border-gray-600' : 'border-gray-300'">
                    {{ __('articles.public.next') }}
                </button>
            </div>

            <button type="button" @click="confirm()" :disabled="picked.length === 0"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] disabled:opacity-40">
                {{ __('articles.actions.add_selected') }}
                <span x-show="picked.length > 0">(<span x-text="picked.length"></span>)</span>
            </button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function episodePicker() {
    return {
        show: false,
        loading: false,
        q: '',
        program: '',
        programs: [],
        rows: [],
        picked: [],
        page: 1,
        meta: { current_page: 1, last_page: 1, total: 0 },
        get darkMode() { return document.documentElement.classList.contains('dark'); },

        open() {
            this.show = true;
            this.picked = [];
            if (this.programs.length === 0) this.loadPrograms();
            this.fetch();
        },

        close() { this.show = false; },

        async loadPrograms() {
            try {
                const res = await fetch('{{ route('admin.articles.modules.episode-programs') }}', { headers: { 'Accept': 'application/json' } });
                this.programs = (await res.json()).data || [];
            } catch (e) { this.programs = []; }
        },

        async fetch() {
            this.loading = true;
            try {
                const url = new URL('{{ route('admin.articles.modules.episodes') }}', window.location.origin);
                url.searchParams.set('page', this.page);
                if (this.q) url.searchParams.set('q', this.q);
                if (this.program) url.searchParams.set('program', this.program);

                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || { current_page: 1, last_page: 1, total: 0 };
            } catch (e) {
                this.rows = [];
            } finally {
                this.loading = false;
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },

        go(delta) {
            const next = this.page + delta;
            if (next < 1 || next > (this.meta.last_page || 1)) return;
            this.page = next;
            this.fetch();
        },

        isPicked(id) { return this.picked.some(e => e.id === id); },

        // Selection survives paging and re-searching, so an editor can gather
        // episodes from several pages before confirming once.
        toggle(row) {
            const i = this.picked.findIndex(e => e.id === row.id);
            i === -1 ? this.picked.push(row) : this.picked.splice(i, 1);
        },

        confirm() {
            window.dispatchEvent(new CustomEvent('episodes-selected', { detail: this.picked }));
            this.close();
        },
    };
}
</script>
@endpush
@endonce
