@extends('layouts.admin')

@php
    $rowsRoute = match ($kind) {
        \App\Domain\Quran\Enums\ResourceKind::TRANSLATION => 'admin.quran.resources.translations.index',
        \App\Domain\Quran\Enums\ResourceKind::TAFSIR => 'admin.quran.resources.tafsirs.index',
        \App\Domain\Quran\Enums\ResourceKind::CHAPTER_INFO => 'admin.quran.resources.chapter-infos.index',
        \App\Domain\Quran\Enums\ResourceKind::RECITATION => 'admin.quran.resources.audio-files.index',
        \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER => 'admin.quran.resources.chapter-audio.index',
        default => null,
    };
    $perChapter = $kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_INFO || $kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER;
@endphp

@section('page-header')
    <x-admin.quran.page-header :title="$resource->displayName()" icon="library" :subtitle="$resource->author_name"
        :crumbs="[$kind->label() => route('admin.quran.resources.index', $kind->routeSegment())]">
        @if ($rowsRoute)
            <x-admin.quran.button variant="secondary" icon="list" :href="route($rowsRoute, $resource->id)">{{ __('quran.manage.manage_content') }}</x-admin.quran.button>
        @endif
        @can('sync quran')
            @if ($resource->qf_id !== null)
                <form method="POST" action="{{ route('admin.quran.resources.sync', $resource->id) }}">@csrf
                    <x-admin.quran.button variant="secondary" icon="refresh-cw">{{ __('quran.manage.sync_now') }}</x-admin.quran.button>
                </form>
            @endif
        @endcan
        @can('edit quran')
            <x-admin.quran.button icon="edit-2" :href="route('admin.quran.resources.edit', $resource->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
            <form method="POST" action="{{ route('admin.quran.resources.destroy', $resource->id) }}"
                  x-data @submit="if (!window.confirm(@js($resource->isFromQf() ? __('quran.manage.confirm_hide') : __('quran.manage.confirm_delete_resource')))) $event.preventDefault()">
                @csrf @method('DELETE')
                <x-admin.quran.button variant="danger" icon="trash-2">{{ $resource->isFromQf() ? __('quran.manage.hide') : __('quran.manage.delete') }}</x-admin.quran.button>
            </form>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel :title="__('quran.manage.details')" icon="info">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-gray-500">{{ __('quran.manage.source') }}</dt><dd class="font-semibold">{{ $resource->isFromQf() ? 'Quran Foundation #'.$resource->qf_id : __('quran.manage.source_manual') }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.slug') }}</dt><dd class="font-mono" dir="ltr">{{ $resource->slug }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.language') }}</dt><dd dir="ltr">{{ $resource->language_iso ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.records') }}</dt><dd class="font-semibold">{{ number_format($coverage['total']) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.active') }}</dt><dd><x-admin.quran.badge :on="$resource->is_active" /></dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.default') }}</dt><dd><x-admin.quran.badge :on="$resource->is_default" /></dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.sync_enabled') }}</dt><dd><x-admin.quran.badge :on="$resource->sync_enabled" /></dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.last_synced') }}</dt><dd>{{ $resource->last_synced_at?->diffForHumans() ?? '—' }}</dd></div>
            @if ($kind->isReciter())
                <div><dt class="text-gray-500">{{ __('quran.manage.style') }}</dt><dd>{{ $resource->style?->label() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('quran.manage.qirat') }}</dt><dd>{{ $resource->qirat ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('quran.manage.external_source') }}</dt><dd dir="ltr">{{ $resource->external_source?->label() ?? '—' }} {{ $resource->external_ref_id }}</dd></div>
                <div><dt class="text-gray-500">{{ __('quran.manage.store_audio_locally') }}</dt><dd><x-admin.quran.badge :on="$resource->store_audio_locally" /></dd></div>
            @endif
        </dl>
        @if (($resource->metadata['hidden_by_admin'] ?? false) === true)
            <p class="mt-4 text-sm text-amber-700">{{ __('quran.manage.hidden_by_admin') }}</p>
        @endif
    </x-admin.quran.panel>

    @if ($kind->isReciter())
        {{-- Reciter grouping. The sources list a reciter once per recording, so the same
             person shows up as several rows; filing one under another keeps every id (and
             so every playlist and favourite) while the public directory shows one reciter. --}}
        <x-admin.quran.panel :title="__('quran.manage.recordings')" icon="layers">
            @if ($parent)
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                    <i data-lucide="corner-down-right" class="h-4 w-4 shrink-0 rtl:-scale-x-100" aria-hidden="true"></i>
                    <p class="min-w-0 flex-1">
                        {{ __('quran.manage.recording_of') }}
                        <a href="{{ route('admin.quran.resources.show', $parent->id) }}" class="font-bold underline underline-offset-4">{{ $parent->displayName() }}</a>
                    </p>
                    @can('edit quran')
                        <form method="POST" action="{{ route('admin.quran.resources.release', $resource->id) }}">@csrf
                            <x-admin.quran.button variant="secondary" icon="unlink" class="min-h-[2.75rem] bg-white dark:bg-transparent">{{ __('quran.manage.release_recording') }}</x-admin.quran.button>
                        </form>
                    @endcan
                </div>
            @else
                @if ($recordings->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('quran.manage.no_recordings') }}</p>
                @else
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($recordings as $recording)
                            <li class="flex items-center gap-3 rounded-xl border border-gray-200 p-2.5 dark:border-gray-800">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white"><i data-lucide="disc-3" class="h-5 w-5" aria-hidden="true"></i></span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.quran.resources.show', $recording->id) }}" class="block truncate font-semibold text-gray-900 hover:text-accent dark:text-white">{{ $recording->displayName() }}</a>
                                    <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ collect([$recording->style?->label(), $recording->qirat, number_format($recording->records_count).' '.__('quran.manage.records')])->filter()->implode(' · ') }}
                                        @unless ($recording->is_active) · {{ __('quran.manage.inactive') }} @endunless
                                    </p>
                                </div>
                                @can('edit quran')
                                    <form method="POST" action="{{ route('admin.quran.resources.release', $recording->id) }}">@csrf
                                        <button type="submit" title="{{ __('quran.manage.release_recording') }}" aria-label="{{ __('quran.manage.release_recording') }} — {{ $recording->displayName() }}"
                                                class="grid h-11 w-11 place-items-center rounded-xl text-gray-500 hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                                            <i data-lucide="unlink" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            @can('edit quran')
                @if (! $parent && $mergeTargets->isNotEmpty())
                    <form method="POST" action="{{ route('admin.quran.resources.merge', $resource->id) }}"
                          class="mt-4 grid gap-3 border-t border-gray-100 pt-4 dark:border-gray-800 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
                          x-data @submit="if (!window.confirm(@js(__('quran.manage.merge_confirm', ['name' => $resource->displayName()])))) $event.preventDefault()">
                        @csrf
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quran.manage.merge_into') }}</span>
                            <select name="into" required
                                    class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-accent dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">—</option>
                                @foreach ($mergeTargets as $target)
                                    <option value="{{ $target->id }}">{{ $target->displayName() }}{{ $target->style ? ' — '.$target->style->label() : '' }} (#{{ $target->id }})</option>
                                @endforeach
                            </select>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('quran.manage.merge_help') }}</span>
                        </label>
                        <x-admin.quran.button variant="secondary" icon="merge" class="h-[42px] justify-center sm:mb-5">{{ __('quran.manage.merge_as_recording') }}</x-admin.quran.button>
                    </form>
                @endif
            @endcan
        </x-admin.quran.panel>
    @endif

    @if ($kind->isReciter() && $resource->external_source !== null)
        @can('sync quran')
            <div x-data="{
                    status: null, report: @js($resource->last_fetch_report), timer: null,
                    async poll() {
                        const response = await fetch(@js(route('admin.quran.resources.fetch-status', $resource->id)), { headers: { 'Accept': 'application/json' } });
                        if (! response.ok) { return; }
                        const json = await response.json();
                        this.status = json.data.status; this.report = json.data.report;
                        if (this.status !== 'running' && this.timer) { clearInterval(this.timer); this.timer = null; }
                    },
                    init() { this.poll(); this.timer = setInterval(() => this.poll(), 4000); },
                }">
            <x-admin.quran.panel :title="__('quran.manage.source_actions')" icon="cloud-download">
                <p class="mb-4 text-sm text-gray-500">{{ __('quran.manage.source_actions_help', ['source' => $resource->external_source->label(), 'id' => $resource->external_ref_id]) }}</p>
                <form method="POST" action="{{ route('admin.quran.resources.fetch', $resource->id) }}" class="flex flex-wrap items-center gap-4">
                    @csrf
                    <x-admin.quran.field name="data" type="checkbox" :label="__('quran.manage.fetch_data')" :value="false" />
                    <x-admin.quran.field name="audio" type="checkbox" :label="__('quran.manage.fetch_audio')" :value="true" />
                    @if ($kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER)
                        <x-admin.quran.field name="timestamps" type="checkbox" :label="__('quran.manage.fetch_timestamps')" :value="true" />
                    @endif
                    <x-admin.quran.field name="download" type="checkbox" :label="__('quran.manage.download_locally')" :value="$resource->store_audio_locally" />
                    <x-admin.quran.button icon="cloud-download">{{ __('quran.manage.run_fetch') }}</x-admin.quran.button>
                </form>
                <div class="mt-4 rounded-xl border border-gray-200 p-4 text-sm" x-show="status || report" x-cloak>
                    <div><span class="text-gray-500">{{ __('quran.manage.status') }}:</span> <span class="font-semibold" x-text="status || '—'"></span></div>
                    <template x-if="report">
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2" dir="ltr">
                            <div>written: <span x-text="report.written ?? 0"></span></div>
                            <div>updated: <span x-text="report.updated ?? 0"></span></div>
                            <div>unchanged: <span x-text="report.unchanged ?? 0"></span></div>
                            <div>failed: <span x-text="(report.failed || []).length"></span></div>
                        </div>
                    </template>
                    <ul class="mt-2 list-disc ps-5 text-red-700" dir="auto" x-show="report && (report.failed || []).length">
                        <template x-for="failure in (report?.failed || []).slice(0, 20)" :key="failure.key">
                            <li><span class="font-mono text-xs" x-text="failure.key"></span>: <span x-text="failure.reason"></span></li>
                        </template>
                    </ul>
                </div>
            </x-admin.quran.panel>
            </div>
        @endcan
    @endif

    @if ($kind->isReciter() && $resource->external_source === null)
        @can('sync quran')
            {{-- No source yet: tie this reciter to a QF or mp3quran recording so it can be fetched. --}}
            <x-admin.quran.panel :title="__('quran.manage.link_source')" icon="link">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('quran.manage.link_source_help') }}</p>
                <form method="POST" action="{{ route('admin.quran.resources.link-source', $resource->id) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,14rem)_minmax(0,12rem)_auto_auto] sm:items-end">
                    @csrf
                    <x-admin.quran.field name="external_source" type="select" :blank="false" :label="__('quran.manage.external_source')"
                        :options="collect(\App\Domain\Quran\Enums\ExternalSource::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                    <x-admin.quran.field name="external_ref_id" :label="__('quran.manage.external_ref_id')" dir="ltr" inputmode="numeric" required maxlength="50" />
                    <x-admin.quran.button icon="link" class="h-[42px] justify-center">{{ __('quran.manage.link_source') }}</x-admin.quran.button>
                    @if ($kind === \App\Domain\Quran\Enums\ResourceKind::CHAPTER_RECITER)
                        <x-admin.quran.button variant="secondary" icon="radio-tower" class="h-[42px] justify-center" :href="route('admin.quran.resources.mp3quran')">{{ __('quran.manage.mp3quran_catalogue') }}</x-admin.quran.button>
                    @endif
                </form>
            </x-admin.quran.panel>
        @endcan
    @endif

    @if ($coverage['by_chapter'] !== [] || $rowsRoute)
        @php
            // One row per surah: how much of it this resource covers. Per-chapter kinds
            // (chapter info, surah audio) are present-or-not; verse kinds count against
            // the surah's own verse total.
            $rows = $chapters->map(function ($chapter) use ($coverage, $perChapter) {
                $n = $coverage['by_chapter'][$chapter->id] ?? 0;
                $of = $perChapter ? 1 : max(1, (int) $chapter->verses_count);

                return ['chapter' => $chapter, 'n' => $n, 'of' => $of, 'state' => $n <= 0 ? 'none' : ($n >= $of ? 'full' : 'partial')];
            });
            $covered = $rows->where('n', '>', 0)->count();
        @endphp

        <x-admin.quran.panel :title="__('quran.manage.coverage')" icon="list-checks"
            x-data="{ q: '', show: 'all', visible: {{ $rows->count() }},
                      match(el) { const ok = (this.show === 'all' || (this.show === 'have') === (el.dataset.state !== 'none')) && (!this.q.trim() || el.dataset.search.includes(this.q.trim().toLowerCase())); return ok; },
                      recount() { this.$nextTick(() => this.visible = [...this.$refs.list.children].filter((el) => this.match(el)).length); } }">

            <div class="mb-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,18rem)] lg:items-center">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('quran.manage.coverage_summary', ['n' => $covered, 'total' => $rows->count()]) }}</p>
                    <div class="mt-2 h-1.5 max-w-xs overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] rtl:bg-gradient-to-l" style="width: {{ $rows->count() ? round($covered / $rows->count() * 100) : 0 }}%"></div>
                    </div>
                </div>

                <div class="inline-flex w-fit rounded-xl border border-gray-200 p-1 dark:border-gray-700" role="group" aria-label="{{ __('quran.manage.filter') }}">
                    @foreach (['all' => __('quran.manage.coverage_all'), 'have' => __('quran.manage.coverage_available'), 'none' => __('quran.manage.missing')] as $value => $label)
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

            <ul x-ref="list" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($rows as $row)
                    @php
                        $chapter = $row['chapter'];
                        $chapterName = $chapter->displayName();
                        $contentUrl = $rowsRoute ? route($rowsRoute, ['resource' => $resource->id, 'chapter' => $chapter->id]) : null;
                    @endphp
                    <li x-show="match($el)" data-state="{{ $row['state'] }}"
                        data-search="{{ mb_strtolower($chapter->id.' '.$chapter->name_arabic.' '.$chapter->name_simple.' '.$chapterName) }}"
                        class="group flex items-center gap-3 rounded-xl border border-gray-200 p-2.5 transition-colors hover:border-accent/60 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/40">

                        <span @class([
                            'grid h-11 w-11 shrink-0 place-items-center rounded-xl text-sm font-bold tabular-nums',
                            'bg-gradient-to-br from-[#0056b3] to-[#218838] text-white' => $row['state'] !== 'none',
                            'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' => $row['state'] === 'none',
                        ])>{{ $chapter->id }}</span>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.quran.chapters.show', $chapter->id) }}" title="{{ $chapterName }}"
                               class="block truncate font-semibold text-gray-900 transition-colors hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-white">{{ $chapterName }}</a>
                            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                @if ($chapterName !== $chapter->name_simple)<span dir="ltr" class="truncate">{{ $chapter->name_simple }}</span><span aria-hidden="true">·</span>@endif
                                <span class="whitespace-nowrap tabular-nums">{{ $chapter->verses_count }} {{ __('quran.manage.verses') }}</span>
                            </div>
                        </div>

                        {{-- Status says it in words and an icon, not colour alone. --}}
                        <span @class([
                            'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold tabular-nums',
                            'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-300' => $row['state'] === 'full',
                            'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => $row['state'] === 'partial',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $row['state'] === 'none',
                        ])>
                            <i data-lucide="{{ ['full' => 'check', 'partial' => 'circle-dashed', 'none' => 'minus'][$row['state']] }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            @if ($row['state'] === 'none')
                                {{ __('quran.manage.missing') }}
                            @elseif ($perChapter)
                                {{ __('quran.manage.coverage_available') }}
                            @else
                                <span dir="ltr">{{ $row['n'] }} / {{ $row['of'] }}</span>
                            @endif
                        </span>

                        @if ($chapterFiles->has($chapter->id))
                            <x-admin.quran.track-button :id="$chapter->id" :label="$chapterName" />
                        @endif

                        @if ($contentUrl)
                            <a href="{{ $contentUrl }}" aria-label="{{ __('quran.manage.manage_content') }} — {{ $chapterName }}" title="{{ __('quran.manage.manage_content') }}"
                               class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:text-gray-400 dark:hover:bg-gray-800">
                                <i data-lucide="list" class="h-[1.125rem] w-[1.125rem]" aria-hidden="true"></i>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p x-show="visible === 0" x-cloak class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('quran.manage.empty') }}</p>
        </x-admin.quran.panel>

        @if ($chapterFiles->isNotEmpty())
            <x-admin.quran.track-player :tracks="$rows->filter(fn ($row) => $chapterFiles->has($row['chapter']->id))->map(fn ($row) => [
                'id' => $row['chapter']->id,
                'title' => $row['chapter']->id.'. '.$row['chapter']->displayName(),
                'url' => \App\Domain\Quran\Services\AudioService::absolutize($chapterFiles[$row['chapter']->id]->url),
            ])->all()" />
        @endif
    @endif

    @if ($resource->info)
        <x-admin.quran.panel :title="__('quran.manage.info')" icon="file-text">
            <div class="prose max-w-none rich-text">{!! app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($resource->info) !!}</div>
        </x-admin.quran.panel>
    @endif
@endsection
