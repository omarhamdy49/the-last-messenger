@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.sections')" icon="layout-list" :subtitle="__('quran.manage.sections_subtitle')">
        @can('sync quran')
            <form method="POST" action="{{ route('admin.quran.sections.reimport') }}">
                @csrf
                <x-admin.quran.button variant="secondary" icon="refresh-cw">{{ __('quran.manage.reimport_structure') }}</x-admin.quran.button>
            </form>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <nav class="mb-4 flex flex-wrap gap-2">
        @foreach (\App\Domain\Quran\Enums\SectionType::cases() as $case)
            <a href="{{ route('admin.quran.sections.index', $case->value) }}"
               class="rounded-xl border px-4 py-2 text-sm font-semibold {{ $case === $type ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white border-transparent' : 'border-gray-300' }}">
                {{ $case->label() }} <span class="opacity-75">({{ $counts[$case->value] ?? 0 }}/{{ $case->max() }})</span>
            </a>
        @endforeach
    </nav>

    <x-admin.quran.table :empty="$sections->isEmpty()" :empty-text="__('quran.manage.not_imported')">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('quran.manage.verse_range') }}</th>
            <th>{{ __('quran.manage.verses') }}</th>
            <th>{{ __('quran.manage.chapters') }}</th>
        </x-slot:head>
        @foreach ($sections as $section)
            <tr>
                <td class="font-bold"><a class="hover:text-accent" href="{{ route('admin.quran.sections.show', [$type->value, $section->number]) }}">{{ $section->number }}</a></td>
                <td dir="ltr">{{ $section->first_verse_id }}–{{ $section->last_verse_id }}</td>
                <td>{{ $section->verses_count }}</td>
                <td dir="ltr" class="text-xs">
                    @foreach ($section->verse_mapping as $chapter => $range)
                        <span class="me-2 inline-block">{{ $chapter }}:{{ $range }}</span>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
