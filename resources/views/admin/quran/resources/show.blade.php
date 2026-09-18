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

    @if ($coverage['by_chapter'] !== [] || $rowsRoute)
        <x-admin.quran.panel :title="__('quran.manage.coverage')" icon="grid-3x3">
            <p class="mb-3 text-xs text-gray-500">{{ $perChapter ? __('quran.manage.coverage_chapter_help') : __('quran.manage.coverage_verse_help') }}</p>
            <div class="grid grid-cols-6 sm:grid-cols-12 md:grid-cols-19 gap-1" dir="ltr" style="grid-template-columns: repeat(auto-fill, minmax(2.75rem, 1fr));">
                @for ($chapter = 1; $chapter <= 114; $chapter++)
                    @php $n = $coverage['by_chapter'][$chapter] ?? 0; @endphp
                    <a @if ($rowsRoute) href="{{ route($rowsRoute, ['resource' => $resource->id, 'chapter' => $chapter]) }}" @endif
                       title="{{ $chapter }}: {{ $n }}"
                       class="rounded-md py-1 text-center text-xs {{ $n > 0 ? 'bg-green-100 text-green-900' : 'bg-gray-100 text-gray-400' }}">{{ $chapter }}</a>
                @endfor
            </div>
        </x-admin.quran.panel>
    @endif

    @if ($resource->info)
        <x-admin.quran.panel :title="__('quran.manage.info')" icon="file-text">
            <div class="prose max-w-none rich-text">{!! app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($resource->info) !!}</div>
        </x-admin.quran.panel>
    @endif
@endsection
