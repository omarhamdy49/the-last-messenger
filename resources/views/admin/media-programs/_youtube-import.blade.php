{{--
    YouTube import modal — shared by the program create, edit and show pages.

    Accepts a playlist URL *or* a single video URL; the backend normalises a
    video to a one-entry playlist so both take the same path.

    @param string      $mode      'import' (a program exists — queue the import)
                                  'prefill' (create page — fill the form instead)
    @param string|null $syncRoute  required when $mode === 'import'
    @param string      $prefill    initial URL value
    @param int|null    $resumeRun  run id to resume polling (post-create redirect)

    Opened by dispatching: window.dispatchEvent(new CustomEvent('open-yt-import'))
    In 'prefill' mode it emits `yt-prefill` with the analysis payload.
--}}
@php
    $mode = $mode ?? 'import';
    $syncRoute = $syncRoute ?? null;
    $prefill = $prefill ?? '';
    $resumeRun = $resumeRun ?? null;
@endphp

<div x-data="youtubeImport({
        mode: @js($mode),
        analyzeRoute: '{{ route('admin.media.youtube.analyze') }}',
        syncRoute: @js($syncRoute),
        statusRouteTemplate: '{{ route('admin.media.youtube.sync-status', ['run' => '__RUN__']) }}',
        csrf: '{{ csrf_token() }}',
        prefill: @js($prefill),
        resumeRun: @js($resumeRun),
     })"
     x-show="open" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="yt-import-title"
     @keydown.escape.window="if (!busy) close()">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="if (!busy) close()"></div>

    <div class="relative w-full max-w-xl max-h-[88vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h3 id="yt-import-title" class="text-lg font-semibold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="youtube" class="w-5 h-5 text-red-600"></i>
                {{ __('admin.yt_import_title') }}
            </h3>
            <button type="button" @click="close()" :disabled="busy"
                    aria-label="{{ __('admin.yt_close') }}"
                    class="p-2 rounded-lg disabled:opacity-40 min-w-11 min-h-11 flex items-center justify-center"
                    :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
            {{-- STEP: input --}}
            <div x-show="step === 'input'">
                <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.yt_import_hint') }}</p>
                <label for="yt-source-url" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.yt_source_url_label') }}
                </label>
                <input id="yt-source-url" type="url" x-model="url" dir="ltr" placeholder="{{ __('admin.yt_source_url_placeholder') }}"
                       @keydown.enter.prevent="analyze()"
                       class="w-full text-base sm:text-sm rounded-lg border px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                <p class="mt-1.5 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.yt_source_url_hint') }}</p>
                <p x-show="error" x-text="error" role="alert" class="mt-2 text-sm text-red-500"></p>
            </div>

            {{-- STEP: analyzing --}}
            <div x-show="step === 'analyzing'" class="py-10 flex flex-col items-center justify-center gap-3 text-center">
                <svg class="animate-spin w-8 h-8 text-[#0056b3]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.yt_analyzing') }}</p>
            </div>

            {{-- STEP: preview --}}
            <div x-show="step === 'preview'" x-cloak>
                <div class="flex items-start gap-3 mb-3">
                    <template x-if="analysis?.thumbnail">
                        <img :src="analysis.thumbnail" alt="" class="w-28 aspect-video object-cover rounded-lg flex-shrink-0 border"
                             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    </template>
                    <div class="min-w-0">
                        <p class="font-semibold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="analysis?.title"></p>
                        <p class="text-sm mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            <span x-text="analysis?.channel"></span>
                        </p>
                        <span class="inline-flex items-center gap-1.5 mt-2 px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i :data-lucide="analysis?.is_playlist ? 'list-video' : 'video'" class="w-3.5 h-3.5"></i>
                            <span x-text="analysis?.is_playlist
                                ? @js(__('admin.yt_videos_found')).replace(':count', analysis?.count ?? 0)
                                : @js(__('admin.yt_single_video'))"></span>
                        </span>
                    </div>
                </div>
                {{-- Mapping review: every row is editable and can be excluded
                     before anything is imported. --}}
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.yt_mapping_hint') }}</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleAll(true)"
                                class="text-xs px-2 py-1 rounded-lg border"
                                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">{{ __('admin.yt_select_all') }}</button>
                        <button type="button" @click="toggleAll(false)"
                                class="text-xs px-2 py-1 rounded-lg border"
                                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">{{ __('admin.yt_select_none') }}</button>
                        <button type="button" @click="renumber()"
                                class="text-xs px-2 py-1 rounded-lg border flex items-center gap-1"
                                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                            <i data-lucide="list-ordered" class="w-3 h-3"></i>{{ __('admin.yt_renumber') }}
                        </button>
                    </div>
                </div>

                <ul class="rounded-xl border divide-y max-h-72 overflow-y-auto custom-scrollbar"
                    :class="darkMode ? 'border-gray-800 divide-gray-800' : 'border-gray-200 divide-gray-100'">
                    <template x-for="(row, i) in rows" :key="row.video_id">
                        <li class="flex items-center gap-2 px-3 py-2" :class="row.include ? '' : 'opacity-40'">
                            <input type="checkbox" x-model="row.include"
                                   :aria-label="@js(__('admin.yt_include_row')) + ' ' + row.title"
                                   class="w-4 h-4 rounded text-[#0056b3] flex-shrink-0">
                            <input type="number" min="1" max="10000" x-model.number="row.episode_number"
                                   :disabled="!row.include"
                                   :aria-label="@js(__('admin.yt_episode_number'))"
                                   class="w-16 flex-shrink-0 text-xs rounded-lg border px-2 py-1.5 text-center tabular-nums focus:outline-none focus:ring-2 focus:ring-[#0056b3] disabled:opacity-50"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <input type="text" maxlength="500" x-model="row.title"
                                   :disabled="!row.include"
                                   :aria-label="@js(__('admin.yt_episode_title'))"
                                   class="flex-1 min-w-0 text-sm rounded-lg border px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3] disabled:opacity-50"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </li>
                    </template>
                </ul>

                <p x-show="duplicateNumbers.length" x-cloak role="alert" class="mt-2 text-sm text-amber-500 flex items-center gap-1.5">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span x-text="@js(__('admin.yt_duplicate_numbers')).replace(':numbers', duplicateNumbers.join(', '))"></span>
                </p>
            </div>

            {{-- STEP: syncing --}}
            <div x-show="step === 'syncing'" x-cloak class="py-4">
                <div class="flex items-center justify-between text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <span class="font-medium">{{ __('admin.yt_importing') }}</span>
                    <span class="tabular-nums" x-text="progress.processed + ' / ' + progress.total"></span>
                </div>
                <div class="w-full h-3 rounded-full overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"
                     role="progressbar" :aria-valuenow="progress.percent" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-500"
                         :style="'width:' + progress.percent + '%'"></div>
                </div>
                <p class="mt-2 text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="progress.message"></p>
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5 text-green-500"><i data-lucide="plus-circle" class="w-4 h-4"></i>{{ __('admin.yt_episodes_created') }}: <span class="tabular-nums" x-text="progress.created"></span></span>
                    <span class="flex items-center gap-1.5 text-blue-500"><i data-lucide="refresh-cw" class="w-4 h-4"></i>{{ __('admin.yt_episodes_updated') }}: <span class="tabular-nums" x-text="progress.updated"></span></span>
                </div>
                <p class="mt-4 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.yt_keep_open_hint') }}</p>
            </div>

            {{-- STEP: done --}}
            <div x-show="step === 'done'" x-cloak class="py-8 flex flex-col items-center justify-center gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                    <i data-lucide="check" class="w-7 h-7 text-green-600"></i>
                </div>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.yt_import_done') }}</p>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.yt_episodes_created') }}: <span class="tabular-nums" x-text="progress.created"></span> •
                    {{ __('admin.yt_episodes_updated') }}: <span class="tabular-nums" x-text="progress.updated"></span>
                </p>
            </div>

            {{-- STEP: error --}}
            <div x-show="step === 'error'" x-cloak class="py-8 flex flex-col items-center justify-center gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-7 h-7 text-red-600"></i>
                </div>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.yt_import_failed') }}</p>
                <p class="text-sm break-words max-w-sm" role="alert" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="error"></p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t flex items-center justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <template x-if="step === 'input'">
                <div class="flex gap-3">
                    <button type="button" @click="close()" class="px-4 py-2.5 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ __('admin.cancel') }}</button>
                    <button type="button" @click="analyze()" :disabled="!url"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i>{{ __('admin.yt_analyze') }}
                    </button>
                </div>
            </template>
            <template x-if="step === 'preview'">
                <div class="flex gap-3">
                    <button type="button" @click="step = 'input'" class="px-4 py-2.5 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ __('admin.back') }}</button>
                    <button type="button" @click="confirm()" :disabled="!selected.length"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span x-text="confirmLabel()"></span>
                    </button>
                </div>
            </template>
            <template x-if="step === 'done' || step === 'error'">
                <div class="flex gap-3">
                    <button type="button" x-show="step === 'error'" @click="step = 'input'"
                            class="px-4 py-2.5 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ __('admin.yt_retry') }}</button>
                    <button type="button" @click="close()"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all">{{ __('admin.yt_close') }}</button>
                </div>
            </template>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function youtubeImport(config) {
    return {
        open: false,
        step: 'input',
        url: config.prefill || '',
        analysis: null,
        // Editable mapping shown on the preview step, one row per video.
        rows: [],
        error: '',
        statusUrl: null,
        poll: null,
        progress: { percent: 0, processed: 0, total: 0, created: 0, updated: 0, message: '' },

        // Modal is locked while an import is in flight — closing it would
        // hide progress the admin has no other way back to.
        get busy() { return this.step === 'syncing'; },

        get selected() { return this.rows.filter(r => r.include); },

        // Numbers used more than once among the selected rows. Warned about,
        // not blocked: two videos can legitimately share an episode number
        // across seasons, and the admin is the one confirming.
        get duplicateNumbers() {
            const seen = {}, dupes = [];
            for (const row of this.selected) {
                const n = row.episode_number;
                if (seen[n]) { if (!dupes.includes(n)) dupes.push(n); } else { seen[n] = true; }
            }
            return dupes.sort((a, b) => a - b);
        },

        toggleAll(include) { this.rows.forEach(r => { r.include = include; }); },

        // Renumber the selected rows 1..n in their current order.
        renumber() {
            let n = 1;
            this.rows.forEach(r => { if (r.include) r.episode_number = n++; });
        },

        init() {
            window.addEventListener('open-yt-import', () => this.openModal());

            // Resume a run started elsewhere (e.g. the create form redirect).
            if (config.resumeRun) {
                this.open = true;
                this.startPolling(config.resumeRun);
                // Drop ?yt_run from the URL straight away: on completion we
                // reload, and a surviving param would reopen the modal forever.
                const url = new URL(window.location.href);
                url.searchParams.delete('yt_run');
                window.history.replaceState({}, '', url);
            }
        },

        openModal() {
            this.open = true;
            if (this.busy) return; // don't reset an in-flight import
            this.step = 'input';
            this.error = '';
        },

        close() {
            if (this.busy) return;
            this.open = false;
            if (this.poll) { clearInterval(this.poll); this.poll = null; }
            if (this.step === 'done') { window.location.reload(); }
        },

        confirmLabel() {
            const count = this.selected.length;
            if (config.mode === 'prefill') {
                return @js(__('admin.yt_use_this')).replace(':count', count);
            }
            return @js(__('admin.yt_confirm_import')).replace(':count', count);
        },

        // Only the confirmed rows, stripped to what the backend stores.
        mapping() {
            return this.selected.map(r => ({
                video_id: r.video_id,
                episode_number: r.episode_number,
                title: r.title,
            }));
        },

        postHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
            };
        },

        async analyze() {
            if (!this.url) return;
            this.step = 'analyzing';
            this.error = '';
            try {
                const r = await fetch(config.analyzeRoute, {
                    method: 'POST', headers: this.postHeaders(),
                    body: JSON.stringify({ playlist_url: this.url }),
                });
                const d = await r.json();
                if (!r.ok || !d.success) {
                    this.error = d.message || (d.errors && Object.values(d.errors).flat().join(' ')) || 'Error';
                    this.step = 'input';
                    return;
                }
                this.analysis = d.data;
                // Seed the editable mapping from the analysis: playlist order
                // becomes the episode numbering, YouTube titles the defaults.
                this.rows = (d.data.entries || []).map(e => ({
                    video_id: e.video_id,
                    episode_number: e.position,
                    title: e.title,
                    include: true,
                }));
                this.step = 'preview';
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            } catch (e) {
                this.error = String(e);
                this.step = 'input';
            }
        },

        async confirm() {
            // Create page: hand the analysis back to the form instead of
            // queueing — there is no program row to import into yet.
            if (config.mode === 'prefill') {
                window.dispatchEvent(new CustomEvent('yt-prefill', {
                    detail: { url: this.url, analysis: this.analysis, items: this.mapping() },
                }));
                this.open = false;
                return;
            }

            this.step = 'syncing';
            this.error = '';
            this.progress = { percent: 0, processed: 0, total: this.selected.length, created: 0, updated: 0, message: '' };
            try {
                const r = await fetch(config.syncRoute, {
                    method: 'POST', headers: this.postHeaders(),
                    body: JSON.stringify({ playlist_url: this.url, items: this.mapping() }),
                });
                const d = await r.json();
                if (!r.ok || !d.success) {
                    this.error = d.message || 'Error';
                    this.step = 'error';
                    return;
                }
                this.startPolling(d.run_id, d.status_url);
            } catch (e) {
                this.error = String(e);
                this.step = 'error';
            }
        },

        startPolling(runId, statusUrl) {
            this.statusUrl = statusUrl || config.statusRouteTemplate.replace('__RUN__', runId);
            this.step = 'syncing';
            this.poll = setInterval(() => this.tick(), 2000);
            this.tick();
        },

        async tick() {
            try {
                const r = await fetch(this.statusUrl, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (!d.success) return;
                const x = d.data;
                this.progress = {
                    percent: x.percent, processed: x.processed, total: x.total,
                    created: x.created, updated: x.updated, message: x.message || '',
                };
                if (x.is_terminal) {
                    clearInterval(this.poll); this.poll = null;
                    if (x.status === 'completed') {
                        this.step = 'done';
                    } else {
                        this.error = x.error || 'Error';
                        this.step = 'error';
                    }
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                }
            } catch (e) { /* transient — keep polling */ }
        },
    };
}
</script>
@endpush
@endonce
