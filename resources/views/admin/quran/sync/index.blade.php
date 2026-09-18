@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.sync')" icon="refresh-cw" :subtitle="__('quran.manage.sync_subtitle')" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @can('sync quran')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-admin.quran.panel :title="__('quran.manage.incremental_sync')" icon="refresh-cw">
                <p class="mb-4 text-sm text-gray-500">{{ __('quran.manage.incremental_sync_help') }}</p>
                <form method="POST" action="{{ route('admin.quran.sync.incremental') }}">@csrf
                    <x-admin.quran.button icon="refresh-cw">{{ __('quran.manage.run_sync') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>

            <x-admin.quran.panel :title="__('quran.manage.bootstrap_sync')" icon="database">
                <p class="mb-4 text-sm text-gray-500">{{ __('quran.manage.bootstrap_sync_help') }}</p>
                <form method="POST" action="{{ route('admin.quran.sync.bootstrap') }}" class="space-y-3"
                      x-data @submit="if (!window.confirm(@js(__('quran.manage.confirm_bootstrap')))) $event.preventDefault()">
                    @csrf
                    <x-admin.quran.field name="resources" :label="__('quran.manage.resources_filter')" dir="ltr" placeholder="translations:20;tafsirs:169" :help="__('quran.manage.resources_filter_help')" />
                    <x-admin.quran.button variant="secondary" icon="database">{{ __('quran.manage.run_bootstrap') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>

            <x-admin.quran.panel :title="__('quran.manage.assets')" icon="download">
                <form method="POST" action="{{ route('admin.quran.sync.assets') }}" class="space-y-2">
                    @csrf
                    <x-admin.quran.field name="images" type="checkbox" :label="__('quran.manage.verse_images')" :value="false" />
                    <fieldset class="flex flex-wrap gap-3">
                        <legend class="mb-1 text-sm font-medium">{{ __('quran.manage.fonts') }}</legend>
                        @foreach ($fontSets as $set)
                            <label class="inline-flex items-center gap-2 text-sm" dir="ltr"><input type="checkbox" name="fonts[]" value="{{ $set }}" class="rounded"> {{ $set }}</label>
                        @endforeach
                    </fieldset>
                    <x-admin.quran.button variant="secondary" icon="download">{{ __('quran.manage.download') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>
        </div>
    @endcan

    <x-admin.quran.panel :title="__('quran.manage.current_filter')" icon="filter">
        <code class="block break-all text-xs" dir="ltr">{{ $filter !== '' ? $filter : __('quran.manage.nothing_enabled') }}</code>
    </x-admin.quran.panel>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.sync_states') }}</h2>
    <x-admin.quran.table :empty="$states->isEmpty()">
        <x-slot:head>
            <th class="w-full">{{ __('quran.manage.resources_filter') }}</th>
            <th>{{ __('quran.manage.sequence') }}</th>
            <th>{{ __('quran.manage.status') }}</th>
            <th>{{ __('quran.manage.last_run') }}</th>
        </x-slot:head>
        @foreach ($states as $state)
            <tr>
                <td dir="ltr" class="font-mono text-xs break-all">{{ $state->resources_filter }}
                    @if ($state->last_error)<div class="mt-1 text-red-600">{{ \Illuminate\Support\Str::limit($state->last_error, 300) }}</div>@endif</td>
                <td>{{ $state->last_sequence }}</td>
                <td>{{ $state->status }}</td>
                <td class="whitespace-nowrap">{{ $state->last_run_at?->diffForHumans() ?? '—' }}</td>
            </tr>
        @endforeach
    </x-admin.quran.table>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.recent_runs') }}</h2>
    <x-admin.quran.table :empty="$runs->isEmpty()">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('quran.manage.mode') }}</th>
            <th>{{ __('quran.manage.status') }}</th>
            <th>{{ __('quran.manage.started') }}</th>
            <th>{{ __('quran.manage.mutations') }}</th>
            <th>{{ __('quran.manage.upserted') }}</th>
            <th>{{ __('quran.manage.deleted_rows') }}</th>
            <th>{{ __('quran.manage.skipped_locked') }}</th>
        </x-slot:head>
        @foreach ($runs as $run)
            <tr>
                <td><a class="font-semibold hover:text-accent" href="{{ route('admin.quran.sync.runs.show', $run->id) }}">{{ $run->id }}</a></td>
                <td>{{ $run->mode->value }}</td>
                <td>{{ $run->status }}</td>
                <td class="whitespace-nowrap">{{ $run->started_at?->diffForHumans() }}</td>
                <td>{{ $run->mutations_applied }}</td>
                <td>{{ $run->rows_upserted }}</td>
                <td>{{ $run->rows_deleted }}</td>
                <td>{{ $run->rows_skipped_locked }}</td>
            </tr>
        @endforeach
    </x-admin.quran.table>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.freshness') }}</h2>
    <x-admin.quran.table :empty="$resources->isEmpty()" :empty-text="__('quran.manage.nothing_enabled')">
        <x-slot:head>
            <th>{{ __('quran.manage.resource') }}</th>
            <th>{{ __('quran.manage.kind') }}</th>
            <th>{{ __('quran.manage.records') }}</th>
            <th>{{ __('quran.manage.last_synced') }}</th>
        </x-slot:head>
        @foreach ($resources as $resource)
            <tr>
                <td><a class="hover:text-accent" href="{{ route('admin.quran.resources.show', $resource->id) }}">{{ $resource->displayName() }}</a> <span class="text-xs text-gray-500">QF #{{ $resource->qf_id }}</span></td>
                <td>{{ $resource->kind->label() }}</td>
                <td>{{ number_format($resource->records_count) }}</td>
                <td>{{ $resource->last_synced_at?->diffForHumans() ?? __('quran.manage.never') }}</td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
