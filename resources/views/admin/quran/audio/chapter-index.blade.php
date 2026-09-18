@extends('layouts.admin')

@php
    use App\Domain\Quran\Services\AudioService;

    $canEdit = auth('admin')->user()?->can('edit quran') ?? false;
    $timed = $files->where('has_timestamps', true)->count();
    $focus = (int) request()->query('chapter', 0);

    // What the editor needs per surah, keyed by chapter number.
    $state = $chapters->mapWithKeys(function ($chapter) use ($files) {
        $file = $files->get($chapter->id);

        return [$chapter->id => [
            'id' => $chapter->id,
            'name' => $chapter->displayName(),
            'verses' => (int) $chapter->verses_count,
            'fileId' => $file?->id,
            'url' => $file?->url,
            'duration' => $file?->duration_ms,
            'timed' => (bool) $file?->has_timestamps,
        ]];
    });

    $clock = static fn (?int $ms): ?string => $ms === null ? null : ($ms >= 3600000 ? gmdate('G:i:s', intdiv($ms, 1000)) : ltrim(gmdate('i:s', intdiv($ms, 1000)), '0'));
@endphp

@section('page-header')
    <x-admin.quran.page-header :title="$resource->displayName()" icon="disc-3" :subtitle="__('quran.manage.chapter_audio_help', ['count' => $files->count()])"
        :crumbs="[$resource->kind->label() => route('admin.quran.resources.index', $resource->kind->routeSegment()), $resource->name => route('admin.quran.resources.show', $resource->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <div x-data="{
            rows: @js($state),
            q: '', show: 'all', visible: {{ $chapters->count() }}, tools: false,
            editing: null, original: { url: '', timings: '' },
            form: { url: '', duration: '', timings: '' },
            busy: false, loadingTimings: false, error: null, notice: null,
            urls: {
                store: @js(route('admin.quran.resources.chapter-audio.store', $resource->id)),
                file: @js(route('admin.quran.resources.chapter-audio.update', [$resource->id, '__FILE__'])),
                timings: @js(route('admin.quran.resources.chapter-audio.timestamps.update', [$resource->id, '__FILE__'])),
                page: @js(route('admin.quran.resources.chapter-audio.index', $resource->id)),
            },
            csrf: @js(csrf_token()),

            match(el) {
                const s = el.dataset.state, t = el.dataset.timed === '1';
                const pass = this.show === 'all' || (this.show === 'have' && s === 'have') || (this.show === 'none' && s === 'none') || (this.show === 'untimed' && s === 'have' && !t);
                return pass && (!this.q.trim() || el.dataset.search.includes(this.q.trim().toLowerCase()));
            },
            recount() { this.$nextTick(() => this.visible = [...this.$refs.list.children].filter((el) => this.match(el)).length); },

            get urlChanged() { return !!this.editing?.fileId && this.form.url.trim() !== (this.original.url || ''); },

            async open(id) {
                const row = this.rows[id];
                this.editing = row; this.error = null; this.notice = null;
                this.form = { url: row.url || '', duration: row.duration ?? '', timings: '' };
                this.original = { url: row.url || '', timings: '' };
                this.$nextTick(() => this.$refs.url?.focus());
                if (row.fileId && row.timed) {
                    this.loadingTimings = true;
                    try {
                        const r = await fetch(this.urls.timings.replace('__FILE__', row.fileId), { headers: { Accept: 'application/json' } });
                        const json = await r.json();
                        if (this.editing?.id === id) { this.form.timings = this.original.timings = JSON.stringify(json.data ?? [], null, 2); }
                    } catch (e) { /* the editor still works without the current timings */ }
                    this.loadingTimings = false;
                }
            },
            close() { if (!this.busy) { this.editing = null; } },

            audition() {
                if (!this.form.url.trim()) { return; }
                this.$dispatch('quran-track:load', { id: this.editing.id, title: this.editing.id + '. ' + this.editing.name, url: this.form.url.trim() });
            },
            heard(detail) {
                // A link that plays tells us its length; offer it instead of making the admin look it up.
                if (this.editing && detail.id === this.editing.id && detail.duration && !this.form.duration) { this.form.duration = Math.round(detail.duration * 1000); }
            },

            async send(url, data, method = 'POST') {
                if (method !== 'POST') { data.append('_method', method); }
                const r = await fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf }, body: data });
                const json = await r.json().catch(() => ({}));
                if (!r.ok) { throw new Error(Object.values(json.errors || {}).flat()[0] || json.message || @js(__('quran.manage.save_failed'))); }
                return json;
            },

            async save() {
                const upload = this.$refs.file.files[0], timingsFile = this.$refs.timingsFile.files[0];
                this.busy = true; this.error = null; this.notice = null;
                try {
                    const data = new FormData();
                    if (upload) { data.append('file', upload); } else { data.append('url', this.form.url.trim()); }
                    if (this.form.duration !== '' && this.form.duration !== null) { data.append('duration_ms', this.form.duration); }
                    let fileId = this.editing.fileId;
                    if (fileId) { await this.send(this.urls.file.replace('__FILE__', fileId), data, 'PUT'); }
                    else { data.append('chapter_id', this.editing.id); fileId = (await this.send(this.urls.store, data)).data.id; this.editing.fileId = fileId; }

                    // Old timings never follow a new recording: only JSON the admin actually changed is sent.
                    const fresh = this.form.timings.trim() !== '' && this.form.timings !== this.original.timings;
                    if (timingsFile || fresh) {
                        const timings = new FormData();
                        if (timingsFile) { timings.append('timestamps_file', timingsFile); } else { timings.append('timestamps', this.form.timings); }
                        try { await this.send(this.urls.timings.replace('__FILE__', fileId), timings, 'PUT'); }
                        catch (e) { this.original.url = this.form.url.trim(); throw new Error(@js(__('quran.manage.audio_saved_timings_failed')) + ' ' + e.message); }
                    }
                    window.location = this.urls.page + '?chapter=' + this.editing.id;
                } catch (e) { this.error = e.message; this.busy = false; }
            },

            async remove() {
                if (!this.editing?.fileId || !window.confirm(@js(__('quran.manage.confirm_delete')))) { return; }
                this.busy = true; this.error = null;
                try { await this.send(this.urls.file.replace('__FILE__', this.editing.fileId), new FormData(), 'DELETE'); window.location = this.urls.page + '?chapter=' + this.editing.id; }
                catch (e) { this.error = e.message; this.busy = false; }
            },

            init() {
                const focus = {{ $focus }};
                if (focus && this.rows[focus]) {
                    this.$nextTick(() => document.getElementById('surah-' + focus)?.scrollIntoView({ block: 'center' }));
                }
            },
         }"
         @quran-track:state.window="heard($event.detail)"
         @keydown.escape.window="close()">

        <x-admin.quran.panel class="!p-4 sm:!p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,18rem)] lg:items-center">
                <dl class="flex flex-wrap gap-x-8 gap-y-3">
                    <div>
                        <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('quran.manage.files_count') }}</dt>
                        <dd class="mt-0.5 text-xl font-bold tabular-nums text-gray-900 dark:text-white" dir="ltr">{{ $files->count() }} <span class="text-sm font-medium text-gray-500">/ {{ $chapters->count() }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('quran.manage.timestamps') }}</dt>
                        <dd class="mt-0.5 text-xl font-bold tabular-nums text-gray-900 dark:text-white" dir="ltr">{{ $timed }} <span class="text-sm font-medium text-gray-500">/ {{ $files->count() }}</span></dd>
                    </div>
                </dl>

                <div class="inline-flex w-fit flex-wrap rounded-xl border border-gray-200 p-1 dark:border-gray-700" role="group" aria-label="{{ __('quran.manage.filter') }}">
                    @foreach (['all' => __('quran.manage.coverage_all'), 'have' => __('quran.manage.coverage_available'), 'none' => __('quran.manage.missing'), 'untimed' => __('quran.manage.without_timestamps')] as $value => $label)
                        <button type="button" @click="show = '{{ $value }}'; recount()" :aria-pressed="(show === '{{ $value }}').toString()"
                                class="min-h-[2.5rem] rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                :class="show === '{{ $value }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'">{{ $label }}</button>
                    @endforeach
                </div>

                <label class="relative block">
                    <span class="sr-only">{{ __('quran.manage.search') }}</span>
                    <i data-lucide="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input type="search" x-model="q" @input="recount()" placeholder="{{ __('quran.manage.search') }}"
                           class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pe-3 ps-9 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </label>
            </div>

            @if ($canEdit)
                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <button type="button" @click="tools = !tools" :aria-expanded="tools.toString()"
                            class="inline-flex min-h-[2.75rem] items-center gap-2 rounded-lg px-2 text-sm font-semibold text-accent hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                        <i data-lucide="wand-2" class="h-4 w-4" aria-hidden="true"></i>{{ __('quran.manage.fill_template') }}
                    </button>
                    <form x-show="tools" x-collapse x-cloak method="POST" action="{{ route('admin.quran.resources.chapter-audio.fill', $resource->id) }}" class="mt-2 flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-72 flex-1">
                            <x-admin.quran.field name="url_template" :label="__('quran.manage.url_template')" dir="ltr" required
                                placeholder="https://server8.mp3quran.net/afs/{surah:03}.mp3" :help="__('quran.manage.url_template_chapter_help')" />
                        </div>
                        <x-admin.quran.button variant="secondary" icon="wand-2">{{ __('quran.manage.fill') }}</x-admin.quran.button>
                    </form>
                </div>
            @endif
        </x-admin.quran.panel>

        <ul x-ref="list" class="mb-24 grid gap-2">
            @foreach ($chapters as $chapter)
                @php
                    $file = $files->get($chapter->id);
                    $name = $chapter->displayName();
                @endphp
                <li id="surah-{{ $chapter->id }}" x-show="match($el)"
                    data-state="{{ $file ? 'have' : 'none' }}" data-timed="{{ $file?->has_timestamps ? '1' : '0' }}"
                    data-search="{{ mb_strtolower($chapter->id.' '.$chapter->name_arabic.' '.$chapter->name_simple.' '.$name) }}"
                    @class([
                        'flex flex-wrap items-center gap-3 rounded-xl border bg-white p-2.5 transition-colors hover:border-accent/60 dark:bg-gray-900/50 sm:flex-nowrap',
                        'border-accent ring-2 ring-accent/30' => $focus === $chapter->id,
                        'border-gray-200 dark:border-gray-800' => $focus !== $chapter->id,
                    ])
                    :class="editing?.id === {{ $chapter->id }} && 'border-accent ring-2 ring-accent/30'">

                    <span @class([
                        'grid h-11 w-11 shrink-0 place-items-center rounded-xl text-sm font-bold tabular-nums',
                        'bg-gradient-to-br from-[#0056b3] to-[#218838] text-white' => $file,
                        'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' => ! $file,
                    ])>{{ $chapter->id }}</span>

                    <div class="min-w-0 flex-1 basis-40">
                        <a href="{{ route('admin.quran.chapters.show', $chapter->id) }}" title="{{ $name }}"
                           class="block truncate font-semibold text-gray-900 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-white">{{ $name }}</a>
                        <p class="mt-0.5 truncate font-mono text-xs text-gray-500 dark:text-gray-400" dir="ltr" title="{{ $file?->url }}">{{ $file?->url ?? '—' }}</p>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-1.5 text-xs font-semibold">
                        @if ($file)
                            @if ($clock($file->duration_ms))
                                <span class="rounded-full bg-gray-100 px-2 py-1 tabular-nums text-gray-700 dark:bg-gray-800 dark:text-gray-300" dir="ltr">{{ $clock($file->duration_ms) }}</span>
                            @endif
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <i data-lucide="{{ $file->is_local ? 'hard-drive' : 'globe' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ $file->is_local ? __('quran.manage.stored_locally') : __('quran.manage.streamed') }}
                            </span>
                            <span @class([
                                'inline-flex items-center gap-1 rounded-full px-2 py-1',
                                'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-300' => $file->has_timestamps,
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => ! $file->has_timestamps,
                            ])>
                                <i data-lucide="{{ $file->has_timestamps ? 'timer' : 'timer-off' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ $file->has_timestamps ? __('quran.manage.timestamps') : __('quran.manage.without_timestamps') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                <i data-lucide="minus" class="h-3.5 w-3.5" aria-hidden="true"></i>{{ __('quran.manage.missing') }}
                            </span>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        @if ($file)
                            <x-admin.quran.track-button :id="$chapter->id" :label="$name" />
                            <a href="{{ route('admin.quran.resources.chapter-audio.timestamps.edit', [$resource->id, $file->id]) }}"
                               aria-label="{{ __('quran.manage.timestamps') }} — {{ $name }}" title="{{ __('quran.manage.timestamps') }}"
                               class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                                <i data-lucide="list-ordered" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($canEdit)
                            <button type="button" @click="open({{ $chapter->id }})"
                                    aria-label="{{ $file ? __('quran.manage.edit') : __('quran.manage.add_audio') }} — {{ $name }}" title="{{ $file ? __('quran.manage.edit') : __('quran.manage.add_audio') }}"
                                    class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                                <i data-lucide="{{ $file ? 'pencil' : 'plus' }}" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <p x-show="visible === 0" x-cloak class="-mt-20 mb-24 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('quran.manage.empty') }}</p>

        @if ($canEdit)
            {{-- Editor: one slide-over for whichever surah is open. The link and its timings
                 are saved together because timings only ever describe one recording. --}}
            <div x-show="editing" x-cloak class="fixed inset-0 z-40" role="dialog" aria-modal="true" :aria-label="editing ? editing.id + '. ' + editing.name : ''">
                <div class="absolute inset-0 bg-gray-950/50" @click="close()" x-show="editing" x-transition.opacity></div>

                <form @submit.prevent="save()" x-show="editing"
                      x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="ltr:translate-x-full rtl:-translate-x-full"
                      x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="ltr:translate-x-full rtl:-translate-x-full"
                      class="absolute inset-y-0 end-0 flex w-full max-w-xl flex-col bg-white shadow-2xl dark:bg-gray-900 motion-reduce:transition-none">

                    <header class="flex items-center gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-sm font-bold tabular-nums text-white" x-text="editing?.id"></span>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-lg font-bold text-gray-900 dark:text-white" x-text="editing?.name"></h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><span x-text="editing?.verses"></span> {{ __('quran.manage.verses') }}</p>
                        </div>
                        <button type="button" @click="close()" aria-label="{{ __('quran.manage.close') }}"
                                class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:hover:bg-gray-800">
                            <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="flex-1 space-y-6 overflow-y-auto p-4">
                        <fieldset class="space-y-3">
                            <legend class="mb-1 flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                <i data-lucide="music" class="h-4 w-4 text-accent" aria-hidden="true"></i>{{ __('quran.manage.audio_file') }}
                            </legend>

                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.url') }}</span>
                                <span class="flex gap-2">
                                    <input x-ref="url" type="url" x-model="form.url" dir="ltr" maxlength="500" placeholder="https://…/017.mp3"
                                           class="w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <button type="button" @click="audition()" :disabled="!form.url.trim()" title="{{ __('quran.manage.audition') }}" aria-label="{{ __('quran.manage.audition') }}"
                                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-gray-300 text-[#0056b3] hover:border-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-[#7cc4ff]">
                                        <i data-lucide="play" class="h-4 w-4 fill-current" aria-hidden="true"></i>
                                    </button>
                                </span>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.upload_mp3') }}</span>
                                <input x-ref="file" type="file" accept="audio/mpeg"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('quran.manage.upload_replaces_url') }}</span>
                            </label>

                            <label class="block max-w-[14rem]">
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.duration_ms') }}</span>
                                <input type="number" min="0" x-model="form.duration" dir="ltr" inputmode="numeric"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm tabular-nums text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </label>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend class="mb-1 flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                <i data-lucide="timer" class="h-4 w-4 text-accent" aria-hidden="true"></i>{{ __('quran.manage.timestamps') }}
                            </legend>

                            <p x-show="urlChanged" x-cloak role="status"
                               class="flex gap-2 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
                                <span>{{ __('quran.manage.url_change_clears_timings') }}</span>
                            </p>

                            <label class="block">
                                <span class="mb-1 flex items-center justify-between gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('quran.manage.timestamps_json') }}
                                    <span x-show="loadingTimings" x-cloak class="text-xs font-normal text-gray-500">{{ __('quran.manage.loading') }}</span>
                                </span>
                                <textarea x-model="form.timings" rows="10" dir="ltr" spellcheck="false"
                                          placeholder='[{"verse_key": "17:1", "from": 0, "to": 12840, "segments": [[1, 0, 900]]}]'
                                          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 font-mono text-xs text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-text="@js(__('quran.manage.timestamps_help', ['count' => '__N__'])).replace('__N__', editing?.verses ?? '')"></span>
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.or_upload_json') }}</span>
                                <input x-ref="timingsFile" type="file" accept=".json,application/json"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </label>
                        </fieldset>

                        <p x-show="error" x-cloak x-text="error" role="alert"
                           class="rounded-xl border border-red-300 bg-red-50 p-3 text-sm font-medium text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"></p>
                    </div>

                    <footer class="flex items-center gap-3 border-t border-gray-200 p-4 dark:border-gray-800">
                        {{-- Destructive, so it sits apart from the way forward. --}}
                        <button type="button" x-show="editing?.fileId" @click="remove()" :disabled="busy"
                                class="inline-flex min-h-[2.75rem] items-center gap-2 rounded-xl border border-red-300 px-3 text-sm font-semibold text-red-600 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:opacity-40 dark:border-red-500/40 dark:hover:bg-red-500/10">
                            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>{{ __('quran.manage.delete') }}
                        </button>
                        <span class="flex-1"></span>
                        <button type="button" @click="close()" :disabled="busy"
                                class="min-h-[2.75rem] rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:border-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:opacity-40 dark:border-gray-700 dark:text-gray-200">{{ __('quran.manage.cancel') }}</button>
                        <button type="submit" :disabled="busy"
                                class="inline-flex min-h-[2.75rem] items-center gap-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-4 text-sm font-semibold text-white shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus-visible:ring-offset-gray-900">
                            <span x-show="!busy"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i></span>
                            <span x-show="busy" x-cloak><i data-lucide="loader-circle" class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true"></i></span>
                            {{ __('quran.manage.save') }}
                        </button>
                    </footer>
                </form>
            </div>
        @endif
    </div>

    <x-admin.quran.track-player :tracks="$chapters->filter(fn ($c) => $files->has($c->id))->map(fn ($c) => [
        'id' => $c->id,
        'title' => $c->id.'. '.$c->displayName(),
        'url' => AudioService::absolutize($files[$c->id]->url),
    ])->all()" />
@endsection
