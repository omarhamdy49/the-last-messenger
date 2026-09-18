@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.verses')" icon="text" :subtitle="__('quran.manage.verses_subtitle')" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel>
        <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-4 items-end">
            <div class="col-span-2">
                <x-admin.quran.field name="q" :label="__('quran.manage.search_verse')" :value="request('q')" :placeholder="__('quran.manage.search_verse_placeholder')" />
            </div>
            <x-admin.quran.field name="chapter" type="select" :label="__('quran.manage.chapter')" :value="request('chapter')"
                :options="$chapters->mapWithKeys(fn ($c) => [$c->id => $c->id.'. '.$c->name_arabic])->all()" />
            <x-admin.quran.field name="juz" type="number" :label="__('quran.manage.juz')" :value="request('juz')" min="1" max="30" />
            <x-admin.quran.field name="mushaf_page" type="number" :label="__('quran.manage.page')" :value="request('mushaf_page')" min="1" max="604" />
            <div class="flex items-end gap-3">
                <x-admin.quran.field name="locked" type="select" :label="__('quran.manage.locked')" :value="request('locked')"
                    :options="['1' => __('quran.manage.yes'), '0' => __('quran.manage.no')]" />
                <x-admin.quran.button icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
            </div>
        </form>
    </x-admin.quran.panel>

    <form method="POST" action="{{ route('admin.quran.verses.bulk-lock') }}" x-data="{ ids: [] }">
        @csrf
        @can('edit quran')
            <div class="mb-3 flex flex-wrap items-center gap-3" x-show="ids.length > 0" x-cloak>
                <span class="text-sm"><span x-text="ids.length"></span> {{ __('quran.manage.selected') }}</span>
                <x-admin.quran.button variant="secondary" icon="lock" name="locked" value="1">{{ __('quran.manage.lock') }}</x-admin.quran.button>
                <x-admin.quran.button variant="secondary" icon="unlock" name="locked" value="0">{{ __('quran.manage.unlock') }}</x-admin.quran.button>
            </div>
        @endcan

        <x-admin.quran.table :empty="$verses->isEmpty()">
            <x-slot:head>
                <th><span class="sr-only">{{ __('quran.manage.select') }}</span></th>
                <th>{{ __('quran.manage.key') }}</th>
                <th>{{ __('quran.manage.text') }}</th>
                <th>{{ __('quran.manage.juz') }}</th>
                <th>{{ __('quran.manage.page') }}</th>
                <th>{{ __('quran.manage.locked') }}</th>
                <th class="text-end">{{ __('quran.manage.actions') }}</th>
            </x-slot:head>
            @foreach ($verses as $verse)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $verse->id }}" x-model="ids" class="w-4 h-4 rounded text-accent" aria-label="{{ $verse->verse_key }}"></td>
                    <td dir="ltr" class="font-mono font-semibold whitespace-nowrap">{{ $verse->verse_key }}</td>
                    <td><p class="font-quran text-xl leading-loose" dir="rtl" lang="ar">{{ $verse->text_uthmani }}</p></td>
                    <td>{{ $verse->juz_number }}</td>
                    <td>{{ $verse->page_number }}</td>
                    <td><x-admin.quran.badge :on="$verse->is_locked" /></td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <x-admin.quran.button variant="secondary" icon="eye" :href="route('admin.quran.verses.show', $verse->id)">{{ __('quran.manage.view') }}</x-admin.quran.button>
                            @can('edit quran')
                                <x-admin.quran.button variant="secondary" icon="edit-2" :href="route('admin.quran.verses.edit', $verse->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
            <x-slot:footer>{{ $verses->links() }}</x-slot:footer>
        </x-admin.quran.table>
    </form>
@endsection
