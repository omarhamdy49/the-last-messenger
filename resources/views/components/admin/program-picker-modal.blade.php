{{--
    Program picker modal — paginated, searchable, loads programs on demand
    instead of dumping the whole table into a <select>.

    A field opens it by dispatching `open-program-picker` and receives the
    choice back on `program-picked`:

        $dispatch('open-program-picker')
        @program-picked.window="programId = $event.detail.id; programTitle = $event.detail.title"

    Data source: the existing admin.media-programs.index JSON endpoint
    (q / page / per_page), so search + pagination match the index screen.
--}}

<div x-data="programPickerModal()"
     x-show="isOpen"
     x-cloak
     @open-program-picker.window="open()"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 overflow-hidden"
     style="display:none;"
     role="dialog" aria-modal="true" aria-label="{{ __('admin.select_program') }}">

    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 backdrop-blur-sm"
         :class="darkMode ? 'bg-black/70' : 'bg-black/50'"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="relative w-full max-w-2xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden pointer-events-auto"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 shrink-0 rounded-xl grid place-items-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <i data-lucide="clapperboard" class="w-4 h-4 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-bold text-lg truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('admin.select_program') }}
                        </h2>
                        <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            {{ __('admin.program_picker_subtitle') }}
                        </p>
                    </div>
                </div>

                <button type="button" @click="close()"
                        class="p-2 rounded-lg transition-colors shrink-0"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                        aria-label="{{ __('common.close') }}">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Search --}}
            <div class="px-5 py-4 border-b"
                 :class="darkMode ? 'border-gray-800 bg-gray-800/20' : 'border-gray-200 bg-gray-50'">
                <div class="relative">
                    <span class="absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none"
                          :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="search" x-model="query" x-ref="search"
                           @input.debounce.300ms="page = 1; load()"
                           placeholder="{{ __('admin.program_picker_search') }}"
                           class="w-full ps-9 pe-3 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#0056b3]/50"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                           aria-label="{{ __('admin.program_picker_search') }}">
                </div>
            </div>

            {{-- Results --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar p-3 min-h-[18rem]">
                <template x-if="loading">
                    <ul class="space-y-2" aria-busy="true">
                        <template x-for="i in 6" :key="i">
                            <li class="h-16 rounded-xl animate-pulse"
                                :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'"></li>
                        </template>
                    </ul>
                </template>

                <template x-if="! loading && programs.length === 0">
                    <div class="py-12 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-3"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                            <i data-lucide="clapperboard" class="w-7 h-7"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.program_picker_empty') }}
                        </p>
                    </div>
                </template>

                <template x-if="! loading && programs.length > 0">
                    <ul class="space-y-2">
                        <template x-for="program in programs" :key="program.id">
                            <li>
                                <button type="button" @click="choose(program)"
                                        class="w-full text-start flex items-center gap-3 px-4 py-3 rounded-xl border transition-colors group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                        :class="darkMode
                                            ? 'border-gray-800 hover:bg-gray-800 hover:border-gray-700'
                                            : 'border-gray-200 hover:bg-gray-50 hover:border-gray-300'">
                                    {{-- Poster or fallback icon --}}
                                    <template x-if="posterOf(program)">
                                        <img :src="posterOf(program)" alt=""
                                             class="w-12 h-12 shrink-0 rounded-lg object-cover"
                                             loading="lazy">
                                    </template>
                                    <template x-if="! posterOf(program)">
                                        <div class="w-12 h-12 shrink-0 rounded-lg grid place-items-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                            <i data-lucide="clapperboard" class="w-5 h-5 text-white/80"></i>
                                        </div>
                                    </template>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="font-semibold text-sm truncate"
                                                  :class="darkMode ? 'text-white' : 'text-gray-900'"
                                                  x-text="titleOf(program)"></span>
                                            <template x-if="! program.is_published">
                                                <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                                      :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'">
                                                    {{ __('admin.draft') }}
                                                </span>
                                            </template>
                                        </div>
                                        <p class="text-xs truncate mt-0.5 tabular-nums"
                                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                            <span x-text="program.slug"></span>
                                            <template x-if="program.total_episodes">
                                                <span> · <span x-text="program.total_episodes"></span> {{ __('admin.episodes') }}</span>
                                            </template>
                                            <template x-if="program.year">
                                                <span> · <span x-text="program.year"></span></span>
                                            </template>
                                        </p>
                                    </div>

                                    <i data-lucide="check" class="w-4 h-4 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                       :class="darkMode ? 'text-[#64CCC5]' : 'text-[#0056b3]'"></i>
                                </button>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <p class="text-xs tabular-nums" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    <template x-if="meta.total > 0">
                        <span><span x-text="meta.total"></span> {{ __('admin.programs') }}</span>
                    </template>
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" @click="prev()" :disabled="meta.current_page <= 1 || loading"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        <i :data-lucide="'{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-right' : 'chevron-left' }}'" class="w-4 h-4"></i>
                        {{ __('admin.previous') }}
                    </button>

                    <span class="text-xs tabular-nums px-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="meta.current_page"></span> / <span x-text="Math.max(meta.last_page, 1)"></span>
                    </span>

                    <button type="button" @click="next()" :disabled="meta.current_page >= meta.last_page || loading"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        {{ __('admin.next') }}
                        <i :data-lucide="'{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function programPickerModal() {
        return {
            isOpen: false,
            loading: false,
            query: '',
            page: 1,
            programs: [],
            meta: { current_page: 1, last_page: 1, total: 0 },

            open() {
                this.query = '';
                this.page = 1;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.load();
                this.$nextTick(() => this.$refs.search?.focus());
            },

            close() {
                if (! this.isOpen) return;
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            titleOf(program) {
                const translations = program.translations || [];
                const preferred = translations.find(t => t.locale === 'ar') || translations[0];
                return (preferred && preferred.title) || program.slug || '—';
            },

            posterOf(program) {
                const media = program.media || [];
                const poster = media.find(m => m.collection_name === 'poster') || media[0];
                return (poster && (poster.original_url || poster.preview_url)) || null;
            },

            async load() {
                this.loading = true;
                try {
                    const url = new URL('{{ route('admin.media-programs.index') }}', window.location.origin);
                    if (this.query.length > 0) url.searchParams.set('q', this.query);
                    url.searchParams.set('per_page', 8);
                    url.searchParams.set('page', this.page);

                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    this.programs = json.data ?? [];
                    this.meta = json.meta ?? this.meta;
                } catch (e) {
                    this.programs = [];
                } finally {
                    this.loading = false;
                    this.$nextTick(() => window.lucide?.createIcons());
                }
            },

            prev() {
                if (this.meta.current_page <= 1) return;
                this.page = this.meta.current_page - 1;
                this.load();
            },

            next() {
                if (this.meta.current_page >= this.meta.last_page) return;
                this.page = this.meta.current_page + 1;
                this.load();
            },

            choose(program) {
                this.$dispatch('program-picked', {
                    id: program.id,
                    title: this.titleOf(program),
                    poster: this.posterOf(program),
                });
                this.close();
            },
        };
    }
</script>
@endpush
@endonce
