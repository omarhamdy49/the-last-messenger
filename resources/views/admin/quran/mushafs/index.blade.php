@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.mushafs')" icon="book" :subtitle="__('quran.manage.mushafs_subtitle')" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.table :empty="$mushafs->isEmpty()">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.pages') }}</th>
            <th>{{ __('quran.manage.font') }}</th>
            <th>{{ __('quran.manage.layout_words') }}</th>
            <th>{{ __('quran.manage.last_synced') }}</th>
            <th>{{ __('quran.manage.active') }}</th>
            <th>{{ __('quran.manage.default') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($mushafs as $mushaf)
            <tr>
                <td>{{ $mushaf->id }}</td>
                <td><a class="font-semibold hover:text-accent" href="{{ route('admin.quran.mushafs.show', $mushaf->id) }}">{{ $mushaf->name }}</a>
                    <div class="text-xs text-gray-500">{{ $mushaf->qirat_name }}</div></td>
                <td>{{ $mushaf->pages_count }} × {{ $mushaf->lines_per_page }}</td>
                <td dir="ltr">{{ $mushaf->font_version ?? '—' }}
                    <div class="text-xs text-gray-500">{{ $mushaf->fonts_downloaded_at?->diffForHumans() ?? __('quran.manage.not_downloaded') }}</div></td>
                <td>{{ number_format($mushaf->records_count) }}</td>
                <td>{{ $mushaf->last_synced_at?->diffForHumans() ?? '—' }}</td>
                <td><x-admin.quran.badge :on="$mushaf->is_active" /></td>
                <td><x-admin.quran.badge :on="$mushaf->is_default" /></td>
                <td>
                    <div class="flex justify-end gap-2">
                        <x-admin.quran.button variant="secondary" icon="eye" :href="route('admin.quran.mushafs.show', $mushaf->id)">{{ __('quran.manage.view') }}</x-admin.quran.button>
                        @can('sync quran')
                            <form method="POST" action="{{ route('admin.quran.mushafs.resync', $mushaf->id) }}">@csrf
                                <x-admin.quran.button variant="secondary" icon="refresh-cw">{{ __('quran.manage.resync') }}</x-admin.quran.button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
