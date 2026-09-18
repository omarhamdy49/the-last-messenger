@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.chapters')" icon="book-open" :subtitle="__('quran.manage.chapters_subtitle')" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <x-admin.stats-card icon="book-open" :title="__('quran.manage.total')" :value="$stats['total']" :gradient="true" />
        <x-admin.stats-card icon="sun" :title="__('quran.manage.makkah')" :value="$stats['makkah']" />
        <x-admin.stats-card icon="moon" :title="__('quran.manage.madinah')" :value="$stats['madinah']" />
        <x-admin.stats-card icon="text" :title="__('quran.manage.verses')" :value="$stats['verses']" />
        <x-admin.stats-card icon="eye" :title="__('quran.manage.published')" :value="$stats['published']" />
    </div>

    <x-admin.quran.panel>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <x-admin.quran.field name="search" :label="__('quran.manage.search')" :value="request('search')" />
            </div>
            <x-admin.quran.field name="revelation_place" type="select" :label="__('quran.manage.revelation_place')" :value="request('revelation_place')"
                :options="['makkah' => __('quran.manage.makkah'), 'madinah' => __('quran.manage.madinah')]" />
            <div class="flex items-end gap-3">
                <x-admin.quran.field name="published" type="select" :label="__('quran.manage.status')" :value="request('published')"
                    :options="['1' => __('quran.manage.published'), '0' => __('quran.manage.hidden')]" />
                <x-admin.quran.button icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
            </div>
        </form>
    </x-admin.quran.panel>

    <x-admin.quran.table :empty="$chapters->isEmpty()">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('quran.manage.name') }}</th>
            <th>{{ __('quran.manage.revelation_place') }}</th>
            <th>{{ __('quran.manage.verses') }}</th>
            <th>{{ __('quran.manage.pages') }}</th>
            <th>{{ __('quran.manage.status') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($chapters as $chapter)
            <tr>
                <td class="font-bold">{{ $chapter->id }}</td>
                <td>
                    <a href="{{ route('admin.quran.chapters.show', $chapter->id) }}" class="font-semibold hover:text-accent">
                        <span class="font-quran text-lg" dir="rtl">{{ $chapter->name_arabic }}</span>
                    </a>
                    <div class="text-xs text-gray-500">{{ $chapter->displayName() }} · {{ $chapter->name_simple }}</div>
                </td>
                <td>{{ __('quran.manage.'.$chapter->revelation_place->value) }} <span class="text-xs text-gray-500">({{ $chapter->revelation_order }})</span></td>
                <td>{{ $chapter->verses_count }}</td>
                <td dir="ltr">{{ $chapter->first_page }}–{{ $chapter->last_page }}</td>
                <td><x-admin.quran.badge :on="$chapter->is_published" :on-label="__('quran.manage.published')" :off-label="__('quran.manage.hidden')" /></td>
                <td>
                    <div class="flex justify-end gap-2">
                        <x-admin.quran.button variant="secondary" icon="list" :href="route('admin.quran.verses.index', ['chapter' => $chapter->id])">{{ __('quran.manage.verses') }}</x-admin.quran.button>
                        @can('edit quran')
                            <x-admin.quran.button variant="secondary" icon="edit-2" :href="route('admin.quran.chapters.edit', $chapter->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $chapters->links() }}</x-slot:footer>
    </x-admin.quran.table>
@endsection
