{{--
    Person picker modal.

    One instance per page, shared by every field that needs to choose a person
    (father, mother, …). A field opens it by dispatching `open-muslim-picker`
    and receives the choice back on `muslim-picked`:

        $dispatch('open-muslim-picker', {
            field: 'father_id',            // echoed back so the field knows the pick is its own
            title: 'الأب',
            excludeId: 12,                 // person who must not be offered (never their own parent)
        })

        @muslim-picked.window="if ($event.detail.field === 'father_id') { ... }"

    Opens on the latest ten people rather than an empty box: an editor picking a
    parent usually wants someone recently added, and should not have to guess a
    name to type before seeing anything.

    @param \Illuminate\Support\Collection|null $roles  Islamic roles, for the filter
--}}

@props(['roles' => null])

@php
    $roleOptions = $roles ?? collect();
@endphp

<div x-data="muslimPickerModal()"
     x-show="isOpen"
     x-cloak
     @open-muslim-picker.window="open($event.detail)"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 overflow-hidden"
     style="display:none;"
     role="dialog" aria-modal="true" :aria-label="title">

    <!-- Backdrop. Strong enough to isolate the dialog from the form behind it. -->
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
             @keydown.arrow-down.prevent="move(1)"
             @keydown.arrow-up.prevent="move(-1)"
             @keydown.enter.prevent="chooseActive()"
             class="relative w-full max-w-3xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden pointer-events-auto"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="min-w-0">
                    <h2 class="font-bold text-lg truncate"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="title"></span>
                    </h2>
                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ __('sahaba.picker_modal.subtitle') }}
                    </p>
                </div>

                <button type="button" @click="close()"
                        class="p-2 rounded-lg transition-colors shrink-0"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                        aria-label="{{ __('sahaba.picker_modal.close') }}">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Search + filter -->
            <div class="px-5 py-4 border-b grid gap-3 sm:grid-cols-[1fr_auto]"
                 :class="darkMode ? 'border-gray-800 bg-gray-800/20' : 'border-gray-200 bg-gray-50'">
                <div class="relative">
                    {{-- Theme class on the wrapper, not the <i>: Lucide replaces the
                         <i> and the copied binding would evaluate out of scope. --}}
                    <span class="absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none"
                          :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="search" x-model="query" x-ref="search"
                           @input.debounce.300ms="page = 1; load()"
                           placeholder="{{ __('sahaba.picker_modal.search_placeholder') }}"
                           class="w-full ps-9 pe-3 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                           aria-label="{{ __('sahaba.picker_modal.search_placeholder') }}">
                </div>

                <select x-model="role" @change="page = 1; load()"
                        class="px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                        aria-label="{{ __('sahaba.role_scope') }}">
                    <option value="">{{ __('sahaba.all_roles') }}</option>
                    @foreach($roleOptions as $role)
                        <option value="{{ $role->slug }}">{{ $role->name() }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Results -->
            <div x-ref="results" class="flex-1 overflow-y-auto custom-scrollbar p-3 min-h-[18rem]">
                {{-- Skeleton rather than a spinner: the row count is known, so the
                     list does not jump when the data lands. --}}
                <template x-if="loading">
                    <ul class="space-y-2" aria-busy="true">
                        <template x-for="i in 5" :key="i">
                            <li class="h-16 rounded-xl animate-pulse"
                                :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'"></li>
                        </template>
                    </ul>
                </template>

                <template x-if="! loading && people.length === 0">
                    <div class="py-12 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-3"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                            <i data-lucide="user-x" class="w-7 h-7"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('sahaba.picker_modal.empty') }}
                        </p>
                    </div>
                </template>

                <template x-if="! loading && people.length > 0">
                    <ul class="space-y-2">
                        <template x-for="(person, i) in people" :key="person.id">
                            <li>
                                <button type="button" data-person-row
                                        @click="isTaken(person) || choose(person)"
                                        @mousemove="activeIndex = i"
                                        :disabled="isTaken(person)"
                                        :aria-disabled="isTaken(person)"
                                        class="w-full text-start flex items-center gap-3 px-4 py-3 rounded-xl border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed"
                                        :class="isTaken(person)
                                            ? (darkMode ? 'border-gray-800 opacity-40' : 'border-gray-200 opacity-50')
                                            : (activeIndex === i
                                                ? (darkMode ? 'border-accent bg-accent/10' : 'border-[#0056b3] bg-[#0056b3]/5')
                                                : (darkMode
                                                    ? 'border-gray-800 hover:bg-gray-800 hover:border-gray-700'
                                                    : 'border-gray-200 hover:bg-gray-50 hover:border-gray-300'))">
                                    <div class="w-9 h-9 shrink-0 rounded-full grid place-items-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                        <i data-lucide="user" class="w-4 h-4 text-white/80"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="font-semibold text-sm truncate"
                                                  :class="darkMode ? 'text-white' : 'text-gray-900'"
                                                  x-text="person.name || '—'"></span>

                                            {{-- An unpublished person is a valid ancestor, so they
                                                 are offered — but labelled, never just colour-coded. --}}
                                            <template x-if="! person.is_published">
                                                <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                                      :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700'"
                                                      x-text="'{{ __('sahaba.unpublished') }}'"></span>
                                            </template>

                                            {{-- Already on this event. Shown, not hidden: an editor
                                                 who cannot find the record types the name by hand,
                                                 which is the duplicate this picker prevents. --}}
                                            <template x-if="isTaken(person)">
                                                <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold inline-flex items-center gap-1"
                                                      :class="darkMode ? 'bg-emerald-500/15 text-emerald-300' : 'bg-emerald-50 text-emerald-700'">
                                                    مُضاف بالفعل
                                                </span>
                                            </template>

                                            <template x-for="roleName in person.roles" :key="roleName">
                                                <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                                      :class="darkMode ? 'bg-accent/15 text-accent' : 'bg-[#0056b3]/10 text-[#0056b3]'"
                                                      x-text="roleName"></span>
                                            </template>
                                        </div>

                                        <p class="text-xs truncate mt-0.5"
                                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                            <span x-text="person.kunya || person.name_en || person.slug"></span>
                                            <template x-if="person.lifespan">
                                                <span> · <span x-text="person.lifespan"></span> {{ __('sahaba.hijri') }}</span>
                                            </template>
                                        </p>
                                    </div>

                                    <i data-lucide="check" class="w-4 h-4 shrink-0 opacity-0 transition-opacity"
                                       :class="darkMode ? 'text-accent' : 'text-[#0056b3]'"></i>
                                </button>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            <!-- Pagination -->
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p class="text-xs tabular-nums" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        <template x-if="meta.total > 0">
                            <span>{{ __('sahaba.picker_modal.showing') }}
                                <span x-text="meta.from"></span>–<span x-text="meta.to"></span>
                                / <span x-text="meta.total"></span></span>
                        </template>
                    </p>
                    {{-- Discoverability: the arrow-key path is invisible otherwise. --}}
                    <p class="hidden sm:flex items-center gap-1 text-[11px]"
                       :class="darkMode ? 'text-gray-600' : 'text-gray-400'">
                        <kbd class="px-1.5 py-0.5 rounded border font-sans"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">↑</kbd>
                        <kbd class="px-1.5 py-0.5 rounded border font-sans"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">↓</kbd>
                        للتنقّل
                        <kbd class="px-1.5 py-0.5 rounded border font-sans ms-1"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">Enter</kbd>
                        للاختيار
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="prev()" :disabled="meta.current_page <= 1 || loading"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5
                                   disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        <i :data-lucide="'{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-right' : 'chevron-left' }}'" class="w-4 h-4"></i>
                        {{ __('sahaba.picker_modal.previous') }}
                    </button>

                    <span class="text-xs tabular-nums px-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="meta.current_page"></span> / <span x-text="Math.max(meta.last_page, 1)"></span>
                    </span>

                    <button type="button" @click="next()" :disabled="meta.current_page >= meta.last_page || loading"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5
                                   disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        {{ __('sahaba.picker_modal.next') }}
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
    function muslimPickerModal() {
        return {
            isOpen: false,
            loading: false,
            title: '',
            field: null,
            excludeId: null,
            takenIds: [],
            activeIndex: -1,
            query: '',
            role: '',
            page: 1,
            people: [],
            meta: { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 },

            open(detail) {
                this.field = detail?.field ?? null;
                this.title = detail?.title ?? '';
                this.excludeId = detail?.excludeId ?? null;
                // Ids already used by the caller (e.g. people already on this event).
                // They stay visible but are not selectable — hiding them would make the
                // editor think the record is missing and type the name by hand, which is
                // exactly the duplicate this picker exists to prevent.
                this.takenIds = (detail?.takenIds ?? []).map(Number).filter(Boolean);
                // Every open starts clean; a stale search from the previous
                // field would silently hide most of the list.
                this.query = '';
                this.role = '';
                this.page = 1;
                this.activeIndex = -1;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.load();
                this.$nextTick(() => this.$refs.search?.focus());
            },

            isTaken(person) {
                return this.takenIds.includes(Number(person.id));
            },

            /**
             * Roving selection with the arrow keys. The list can be long and paginated, so
             * an editor scanning it should never have to leave the search box to pick.
             */
            move(step) {
                if (this.people.length === 0) { return; }
                const next = this.activeIndex + step;
                this.activeIndex = next < 0
                    ? this.people.length - 1
                    : (next >= this.people.length ? 0 : next);
                this.$nextTick(() => {
                    this.$refs.results
                        ?.querySelectorAll('[data-person-row]')[this.activeIndex]
                        ?.scrollIntoView({ block: 'nearest' });
                });
            },

            chooseActive() {
                const person = this.people[this.activeIndex];
                if (person && ! this.isTaken(person)) { this.choose(person); }
            },

            close() {
                if (! this.isOpen) return;
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            async load() {
                this.loading = true;
                try {
                    const url = new URL('{{ route('admin.sahaba.browse') }}', window.location.origin);
                    if (this.query.length > 0) url.searchParams.set('q', this.query);
                    if (this.role) url.searchParams.set('role', this.role);
                    if (this.excludeId) url.searchParams.set('exclude', this.excludeId);
                    url.searchParams.set('page', this.page);

                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    this.people = json.data ?? [];
                    this.meta = json.meta ?? this.meta;
                    this.activeIndex = -1;
                } catch (e) {
                    this.people = [];
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

            choose(person) {
                this.$dispatch('muslim-picked', {
                    field: this.field,
                    id: person.id,
                    slug: person.slug,
                    name: person.name || person.name_ar || person.name_en || person.slug,
                    name_ar: person.name_ar,
                    roles: person.roles ?? [],
                    is_published: person.is_published,
                });
                this.close();
            },
        };
    }
</script>
@endpush
@endonce
