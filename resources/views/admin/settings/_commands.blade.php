{{--
    Settings → Artisan Commands tab.

    Every command comes from config/artisan-commands.php; its fields are
    generated from the command's own signature, so nothing here needs editing
    when a signature changes.

    Commands flagged `queued` never run in the request: the browser gets a run
    id back immediately and polls it, which is what keeps a twenty-minute
    Quran/Hadith import off PHP's max_execution_time.
--}}
<div x-data="artisanCommands({
        catalog: @js($catalog),
        runs: @js($runs),
        runRoute: '{{ route('admin.settings.commands.run') }}',
        historyRoute: '{{ route('admin.settings.commands.history') }}',
        statusTemplate: '{{ route('admin.settings.commands.status', ['run' => '__RUN__']) }}',
        pollInterval: {{ $pollInterval }},
        csrf: '{{ csrf_token() }}',
     })">

    {{-- Command groups --}}
    <div class="space-y-4">
        <template x-for="group in catalog" :key="group.key">
            <div class="rounded-2xl border overflow-hidden"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

                <button type="button" @click="toggleGroup(group.key)"
                        class="w-full px-6 py-4 flex items-center justify-between gap-3 text-start transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                    <span class="flex items-center gap-3">
                        <span class="p-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838]">
                            <i :data-lucide="group.icon" class="w-5 h-5 text-white"></i>
                        </span>
                        <span>
                            <span class="block font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                  x-text="groupLabel(group.key)"></span>
                            <span class="block text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                                  x-text="group.commands.length + ' {{ __('admin.cmd_command_count_suffix') }}'"></span>
                        </span>
                    </span>
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform"
                       :class="[openGroups.includes(group.key) ? 'rotate-180' : '', darkMode ? 'text-gray-400' : 'text-gray-500']"></i>
                </button>

                <div x-show="openGroups.includes(group.key)" x-collapse class="divide-y"
                     :class="darkMode ? 'divide-gray-800 border-t border-gray-800' : 'divide-gray-100 border-t border-gray-200'">
                    <template x-for="command in group.commands" :key="command.name">
                        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <code dir="ltr" class="text-sm font-semibold px-2 py-0.5 rounded-lg"
                                          :class="darkMode ? 'bg-gray-800 text-accent' : 'bg-gray-100 text-[#0056b3]'"
                                          x-text="command.name"></code>

                                    <span x-show="command.queued"
                                          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-500">
                                        <i data-lucide="clock" class="w-3 h-3"></i>{{ __('admin.cmd_badge_queued') }}
                                    </span>

                                    <span x-show="command.danger"
                                          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-500/10 text-red-500">
                                        <i data-lucide="alert-triangle" class="w-3 h-3"></i>{{ __('admin.cmd_badge_danger') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                   x-text="command.description"></p>
                            </div>

                            <button type="button" @click="openRunner(command)"
                                    class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="play" class="w-4 h-4"></i>{{ __('admin.cmd_run') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Recent runs --}}
    <div class="mt-6 rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="history" class="w-5 h-5 text-accent"></i>{{ __('admin.cmd_recent_runs') }}
            </h2>
            <button type="button" @click="refreshHistory()"
                    class="p-2 rounded-lg" :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'"
                    aria-label="{{ __('admin.cmd_refresh') }}">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
        </div>

        <p x-show="!runs.length" class="px-6 py-8 text-center text-sm"
           :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.cmd_no_runs') }}</p>

        <div class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
            <template x-for="run in runs" :key="run.id">
                <button type="button" @click="openRun(run.id)"
                        class="w-full px-6 py-3 flex items-center gap-3 text-start transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="statusDot(run.status)"></span>
                    <span class="min-w-0 flex-1">
                        <code dir="ltr" class="text-sm font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'" x-text="run.command"></code>
                        <span class="block text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                              x-text="[run.user, run.finished_human, run.message].filter(Boolean).join(' • ')"></span>
                    </span>
                    <span class="text-xs font-semibold shrink-0" :class="statusText(run.status)" x-text="statusLabel(run.status)"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- ============================ Runner modal ============================ --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="cmd-modal-title"
         @keydown.escape.window="if (!busy) close()">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="if (!busy) close()"></div>

        <div class="relative w-full max-w-2xl max-h-[88vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

            <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <h3 id="cmd-modal-title" class="text-lg font-semibold flex items-center gap-2 min-w-0"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="terminal" class="w-5 h-5 text-accent shrink-0"></i>
                    <code dir="ltr" class="truncate" x-text="current?.name || ''"></code>
                </h3>
                <button type="button" @click="close()" :disabled="busy"
                        aria-label="{{ __('admin.cmd_close') }}"
                        class="p-2 rounded-lg disabled:opacity-40 min-w-11 min-h-11 flex items-center justify-center"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar p-6">

                {{-- STEP: form --}}
                <div x-show="step === 'form'">
                    <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="current?.description"></p>

                    <p x-show="current?.queued" class="mb-4 text-xs rounded-xl px-3 py-2 flex items-start gap-2 bg-blue-500/10 text-blue-500">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
                        <span>{{ __('admin.cmd_queued_hint') }}</span>
                    </p>

                    {{-- Arguments --}}
                    <template x-for="argument in (current?.arguments || [])" :key="'a-' + argument.name">
                        <div class="mb-4">
                            <label :for="'arg-' + argument.name" class="block text-sm font-medium mb-1.5"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <code dir="ltr" x-text="argument.name"></code>
                                <span x-show="argument.required" class="text-red-500">*</span>
                            </label>
                            <input :id="'arg-' + argument.name" type="text" dir="ltr"
                                   x-model="form.arguments[argument.name]" :placeholder="argument.default"
                                   class="w-full text-base sm:text-sm rounded-lg border px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="argument.description"></p>
                        </div>
                    </template>

                    {{-- Options --}}
                    <template x-for="option in (current?.options || [])" :key="'o-' + option.name">
                        <div class="mb-4">
                            {{-- Flag: present or absent, never a value --}}
                            <template x-if="!option.accepts_value">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" x-model="form.options[option.name]"
                                           class="mt-0.5 w-4 h-4 rounded text-[#0056b3] shrink-0">
                                    <span>
                                        <code dir="ltr" class="text-sm font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
                                              x-text="'--' + option.name"></code>
                                        <span class="block text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="option.description"></span>
                                    </span>
                                </label>
                            </template>

                            <template x-if="option.accepts_value">
                                <div>
                                    <label :for="'opt-' + option.name" class="block text-sm font-medium mb-1.5"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        <code dir="ltr" x-text="'--' + option.name"></code>
                                    </label>
                                    <input :id="'opt-' + option.name" type="text" dir="ltr"
                                           x-model="form.options[option.name]" :placeholder="option.default"
                                           class="w-full text-base sm:text-sm rounded-lg border px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        <span x-text="option.description"></span>
                                        <span x-show="option.is_array">{{ __('admin.cmd_comma_separated') }}</span>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <label x-show="current?.danger" class="mt-2 flex items-start gap-2.5 cursor-pointer rounded-xl px-3 py-2.5 bg-red-500/10">
                        <input type="checkbox" x-model="acknowledged" class="mt-0.5 w-4 h-4 rounded text-red-600 shrink-0">
                        <span class="text-sm text-red-500">{{ __('admin.cmd_danger_ack') }}</span>
                    </label>

                    <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm text-red-500"></p>
                </div>

                {{-- STEP: running / done / error all share the progress panel --}}
                <div x-show="step !== 'form'" x-cloak>
                    <div class="flex items-center justify-between text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        <span class="font-medium" x-text="statusLabel(run?.status)"></span>
                        <span class="tabular-nums" x-show="run?.total" x-text="(run?.current ?? 0) + ' / ' + (run?.total ?? 0)"></span>
                    </div>

                    {{-- Determinate when the command prints "n/total"; otherwise a
                         moving stripe, because a fake 0% reads as "stuck". --}}
                    <div class="w-full h-3 rounded-full overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"
                         role="progressbar" :aria-valuenow="run?.percent ?? 0" aria-valuemin="0" aria-valuemax="100">
                        <div x-show="run?.percent !== null && run?.percent !== undefined"
                             class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-500"
                             :style="'width:' + (run?.percent ?? 0) + '%'"></div>
                        <div x-show="(run?.percent === null || run?.percent === undefined) && step === 'running'"
                             class="h-full w-1/3 bg-gradient-to-r from-[#0056b3] to-[#218838] animate-pulse"></div>
                    </div>

                    <p class="mt-2 text-xs break-words" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="run?.message || ''"></p>

                    <div x-show="run?.error" x-cloak class="mt-3 text-sm rounded-xl px-3 py-2 bg-red-500/10 text-red-500 break-words" x-text="run?.error"></div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.cmd_output') }}</span>
                            <button type="button" @click="copyOutput()" class="text-xs px-2 py-1 rounded-lg border"
                                    :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                                <span x-text="copied ? @js(__('admin.cmd_copied')) : @js(__('admin.cmd_copy'))"></span>
                            </button>
                        </div>
                        <pre dir="ltr" x-ref="output"
                             class="text-xs leading-relaxed p-3 rounded-xl overflow-auto max-h-72 custom-scrollbar whitespace-pre-wrap break-words"
                             :class="darkMode ? 'bg-black/60 text-gray-300' : 'bg-gray-900 text-gray-100'"
                             x-text="output || '…'"></pre>
                    </div>

                    <p x-show="step === 'running' && run?.is_queued" class="mt-3 text-xs"
                       :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.cmd_safe_to_leave') }}</p>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex items-center justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <template x-if="step === 'form'">
                    <div class="flex gap-3">
                        <button type="button" @click="close()" class="px-4 py-2.5 rounded-xl text-sm font-semibold border"
                                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ __('admin.cancel') }}</button>
                        <button type="button" @click="submit()" :disabled="!canSubmit"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 flex items-center gap-2">
                            <i data-lucide="play" class="w-4 h-4"></i>{{ __('admin.cmd_execute') }}
                        </button>
                    </div>
                </template>
                <template x-if="step !== 'form'">
                    <div class="flex gap-3">
                        <button type="button" @click="step = 'form'" x-show="step === 'error'"
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold border"
                                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ __('admin.cmd_retry') }}</button>
                        <button type="button" @click="close()" :disabled="busy"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50">{{ __('admin.cmd_close') }}</button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function artisanCommands(config) {
    return {
        catalog: config.catalog,
        runs: config.runs,
        openGroups: config.catalog.length ? [config.catalog[0].key] : [],

        modal: false,
        step: 'form',
        current: null,
        form: { arguments: {}, options: {} },
        acknowledged: false,
        error: '',

        run: null,
        output: '',
        poll: null,
        statusUrl: null,
        copied: false,

        labels: @js([
            'quran' => __('admin.cmd_group_quran'),
            'hadith' => __('admin.cmd_group_hadith'),
            'people' => __('admin.cmd_group_people'),
            'media' => __('admin.cmd_group_media'),
            'content' => __('admin.cmd_group_content'),
            'cache' => __('admin.cmd_group_cache'),
            'system' => __('admin.cmd_group_system'),
        ]),
        statusLabels: @js([
            'queued' => __('admin.cmd_status_queued'),
            'running' => __('admin.cmd_status_running'),
            'completed' => __('admin.cmd_status_completed'),
            'failed' => __('admin.cmd_status_failed'),
        ]),

        // The modal is locked while a command is in flight — closing it is the
        // only way back to the live output, and there is no second door.
        get busy() { return this.step === 'running'; },

        get canSubmit() {
            if (!this.current) return false;
            if (this.current.danger && !this.acknowledged) return false;
            return (this.current.arguments || []).every(a => !a.required || (this.form.arguments[a.name] || '').toString().trim() !== '');
        },

        // Full command metadata by name — the history list only carries a name.
        findCommand(name) {
            for (const group of this.catalog) {
                const match = group.commands.find(c => c.name === name);
                if (match) return match;
            }
            return null;
        },

        groupLabel(key) { return this.labels[key] || key; },
        statusLabel(status) { return this.statusLabels[status] || status || ''; },

        statusDot(status) {
            return {
                completed: 'bg-green-500',
                failed: 'bg-red-500',
                running: 'bg-blue-500 animate-pulse',
            }[status] || 'bg-gray-400';
        },

        statusText(status) {
            return {
                completed: 'text-green-500',
                failed: 'text-red-500',
                running: 'text-blue-500',
            }[status] || 'text-gray-400';
        },

        toggleGroup(key) {
            const i = this.openGroups.indexOf(key);
            if (i === -1) { this.openGroups.push(key); } else { this.openGroups.splice(i, 1); }
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },

        openRunner(command) {
            this.current = command;
            this.step = 'form';
            this.error = '';
            this.acknowledged = false;
            this.run = null;
            this.output = '';

            // Seed every field so x-model has a reactive key to bind to.
            this.form = { arguments: {}, options: {} };
            command.arguments.forEach(a => { this.form.arguments[a.name] = ''; });
            command.options.forEach(o => { this.form.options[o.name] = o.accepts_value ? '' : false; });

            this.modal = true;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },

        close() {
            if (this.busy) return;
            this.modal = false;
            this.stopPolling();
            if (this.run && this.run.is_terminal) this.refreshHistory();
        },

        stopPolling() {
            if (this.poll) { clearInterval(this.poll); this.poll = null; }
        },

        async submit() {
            if (!this.canSubmit) return;
            this.step = 'running';
            this.error = '';
            this.output = '';
            this.run = { status: 'queued', percent: null, current: 0, total: 0, is_queued: this.current.queued };

            try {
                const response = await fetch(config.runRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({
                        command: this.current.name,
                        arguments: this.form.arguments,
                        options: this.form.options,
                    }),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.error = data?.error?.message || 'Error';
                    this.step = 'error';
                    this.run = { status: 'failed', error: this.error, percent: null };
                    return;
                }

                this.startPolling(data.data.status_url);
            } catch (e) {
                this.error = String(e);
                this.step = 'error';
            }
        },

        // Reopen a past run from the history list — same panel, no re-execution.
        // The full command is resolved so "retry" lands on a usable form; a
        // command since removed from the whitelist degrades to a name only.
        openRun(id) {
            const name = this.runs.find(r => r.id === id)?.command || '';
            const command = this.findCommand(name);

            if (command) {
                this.openRunner(command);
            } else {
                this.current = { name, arguments: [], options: [] };
                this.form = { arguments: {}, options: {} };
                this.modal = true;
            }

            this.step = 'running';
            this.output = '';
            this.run = null;
            this.startPolling(config.statusTemplate.replace('__RUN__', id));
        },

        startPolling(statusUrl) {
            this.statusUrl = statusUrl;
            this.stopPolling();
            this.poll = setInterval(() => this.tick(), config.pollInterval);
            this.tick();
        },

        async tick() {
            try {
                const response = await fetch(this.statusUrl, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (!data.success) return;

                this.run = data.data;
                if (data.data.output != null) {
                    const atBottom = this.isOutputAtBottom();
                    this.output = data.data.output;
                    if (atBottom) this.$nextTick(() => this.scrollOutput());
                }

                if (data.data.is_terminal) {
                    this.stopPolling();
                    this.step = data.data.status === 'completed' ? 'done' : 'error';
                    this.refreshHistory();
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                } else {
                    this.step = 'running';
                }
            } catch (e) { /* transient — keep polling */ }
        },

        isOutputAtBottom() {
            const el = this.$refs.output;
            if (!el) return true;
            return el.scrollHeight - el.scrollTop - el.clientHeight < 40;
        },

        scrollOutput() {
            const el = this.$refs.output;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async copyOutput() {
            try {
                await navigator.clipboard.writeText(this.output || '');
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) { /* clipboard blocked — nothing useful to say */ }
        },

        async refreshHistory() {
            try {
                const response = await fetch(config.historyRoute, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.runs = data.data;
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                }
            } catch (e) { /* leave the stale list rather than blanking it */ }
        },
    };
}
</script>
@endpush
