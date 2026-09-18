@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.languages')" icon="languages" :subtitle="__('quran.manage.languages_subtitle')">
        @can('edit quran')
            <x-admin.quran.button icon="plus" :href="route('admin.quran.languages.create')">{{ __('quran.manage.add_language') }}</x-admin.quran.button>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel>
        <form method="GET" class="flex items-end gap-3">
            <div class="flex-1"><x-admin.quran.field name="search" :label="__('quran.manage.search')" :value="request('search')" /></div>
            <x-admin.quran.button icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
        </form>
    </x-admin.quran.panel>

    <x-admin.quran.table :empty="$languages->isEmpty()">
        <x-slot:head>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.iso_code') }}</th>
            <th>{{ __('quran.manage.direction') }}</th>
            <th>{{ __('quran.manage.translations') }}</th>
            <th>{{ __('quran.manage.source') }}</th>
            <th>{{ __('quran.manage.active') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($languages as $language)
            <tr>
                <td class="font-semibold">{{ $language->name }} <span class="text-xs text-gray-500">{{ $language->native_name }}</span></td>
                <td dir="ltr" class="font-mono">{{ $language->iso_code }}</td>
                <td dir="ltr">{{ $language->direction }}</td>
                <td>{{ $language->translations_count }}</td>
                <td>{{ $language->id >= 10000 ? __('quran.manage.source_manual') : __('quran.manage.source_qf') }}</td>
                <td><x-admin.quran.badge :on="$language->is_active" /></td>
                <td>
                    @can('edit quran')
                        <div class="flex justify-end gap-2">
                            <form method="POST" action="{{ route('admin.quran.languages.toggle', $language->id) }}">@csrf
                                <x-admin.quran.button variant="secondary" :icon="$language->is_active ? 'toggle-right' : 'toggle-left'">{{ $language->is_active ? __('quran.manage.deactivate') : __('quran.manage.activate') }}</x-admin.quran.button>
                            </form>
                            <x-admin.quran.button variant="secondary" icon="edit-2" :href="route('admin.quran.languages.edit', $language->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
                            @if ($language->id >= 10000)
                                <form method="POST" action="{{ route('admin.quran.languages.destroy', $language->id) }}"
                                      x-data @submit="if (!window.confirm(@js(__('quran.manage.confirm_delete')))) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <x-admin.quran.button variant="danger" icon="trash-2">{{ __('quran.manage.delete') }}</x-admin.quran.button>
                                </form>
                            @endif
                        </div>
                    @endcan
                </td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $languages->links() }}</x-slot:footer>
    </x-admin.quran.table>
@endsection
