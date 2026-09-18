@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$type->label().' '.$section->number" icon="layout-list"
        :crumbs="[__('quran.manage.sections') => route('admin.quran.sections.index', $type->value)]" />
@endsection

@section('content')
    <x-admin.quran.panel>
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-gray-500">{{ __('quran.manage.first_verse') }}</dt><dd class="font-semibold">{{ $section->first_verse_id }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.last_verse') }}</dt><dd class="font-semibold">{{ $section->last_verse_id }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.verses') }}</dt><dd class="font-semibold">{{ $section->verses_count }}</dd></div>
            @if ($section->surah_ruku_number)
                <div><dt class="text-gray-500">{{ __('quran.manage.surah_ruku_number') }}</dt><dd class="font-semibold">{{ $section->surah_ruku_number }}</dd></div>
            @endif
        </dl>
    </x-admin.quran.panel>

    <x-admin.quran.table>
        <x-slot:head>
            <th>{{ __('quran.manage.chapter') }}</th>
            <th>{{ __('quran.manage.verse_range') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($section->verse_mapping as $chapter => $range)
            <tr>
                <td>{{ $chapter }}</td>
                <td dir="ltr">{{ $range }}</td>
                <td class="text-end">
                    <x-admin.quran.button variant="secondary" icon="list" :href="route('admin.quran.verses.index', ['chapter' => $chapter])">{{ __('quran.manage.verses') }}</x-admin.quran.button>
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
