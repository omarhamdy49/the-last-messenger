@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$kind->label()" icon="library" :subtitle="__('quran.manage.kind_help.'.$kind->value)">
        @can('sync quran')
            @if ($kind->qfCataloguePath())
                <x-admin.quran.button variant="secondary" icon="cloud-download" :href="route('admin.quran.resources.catalogue', $kind->routeSegment())">{{ __('quran.manage.qf_catalogue') }}</x-admin.quran.button>
            @endif
        @endcan
        @can('edit quran')
            <x-admin.quran.button icon="plus" :href="route('admin.quran.resources.create', $kind->routeSegment())">{{ __('quran.manage.add_manual') }}</x-admin.quran.button>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2"><x-admin.quran.field name="search" :label="__('quran.manage.search')" :value="request('search')" /></div>
            <x-admin.quran.field name="language" type="select" :label="__('quran.manage.language')" :value="request('language')" :options="$languages" />
            <x-admin.quran.field name="status" type="select" :label="__('quran.manage.status')" :value="request('status')"
                :options="['active' => __('quran.manage.active'), 'inactive' => __('quran.manage.inactive'), 'sync' => __('quran.manage.sync_enabled')]" />
            <div class="flex items-end gap-3">
                <x-admin.quran.field name="source" type="select" :label="__('quran.manage.source')" :value="request('source')"
                    :options="['qf' => __('quran.manage.source_qf'), 'manual' => __('quran.manage.source_manual')]" />
                <x-admin.quran.button icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
            </div>
        </form>
    </x-admin.quran.panel>

    <x-admin.quran.table :empty="$resources->isEmpty()" :empty-text="__('quran.manage.no_resources')">
        <x-slot:head>
            <th>{{ __('quran.manage.order') }}</th>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.language') }}</th>
            @if ($kind->isReciter())<th>{{ __('quran.manage.style') }}</th>@endif
            <th>{{ __('quran.manage.records') }}</th>
            <th>{{ __('quran.manage.last_synced') }}</th>
            <th>{{ __('quran.manage.active') }}</th>
            <th>{{ __('quran.manage.default') }}</th>
            <th>{{ __('quran.manage.sync_enabled') }}</th>
        </x-slot:head>
        @foreach ($resources as $resource)
            <tr>
                <td>{{ $resource->display_order }}</td>
                <td>
                    <a class="font-semibold hover:text-accent" href="{{ route('admin.quran.resources.show', $resource->id) }}">{{ $resource->displayName() }}</a>
                    <div class="text-xs text-gray-500">{{ $resource->author_name }}
                        <span class="ms-1 rounded bg-gray-100 px-1 text-gray-600">{{ $resource->isFromQf() ? 'QF #'.$resource->qf_id : __('quran.manage.source_manual') }}</span>
                    </div>
                </td>
                <td dir="ltr">{{ $resource->language_iso ?? '—' }}</td>
                @if ($kind->isReciter())<td>{{ $resource->style?->label() ?? '—' }} <span class="text-xs text-gray-500">{{ $resource->qirat }}</span></td>@endif
                <td>{{ number_format($resource->records_count) }}</td>
                <td>{{ $resource->last_synced_at?->diffForHumans() ?? '—' }}</td>
                @foreach (['is_active', 'is_default', 'sync_enabled'] as $field)
                    <td>
                        @can('edit quran')
                            <form method="POST" action="{{ route('admin.quran.resources.toggle', [$resource->id, $field]) }}">@csrf
                                <button type="submit" class="rounded-lg" title="{{ __('quran.manage.toggle') }}"
                                    @disabled($field === 'sync_enabled' && $resource->qf_id === null)><x-admin.quran.badge :on="$resource->{$field}" /></button>
                            </form>
                        @else
                            <x-admin.quran.badge :on="$resource->{$field}" />
                        @endcan
                    </td>
                @endforeach
            </tr>
        @endforeach
        <x-slot:footer>{{ $resources->links() }}</x-slot:footer>
    </x-admin.quran.table>
@endsection
