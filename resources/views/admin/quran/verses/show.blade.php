@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.verse').' '.$verse->verse_key" icon="text"
        :crumbs="[__('quran.manage.chapters') => route('admin.quran.chapters.index'), $verse->chapter->name_arabic => route('admin.quran.chapters.show', $verse->chapter_id), __('quran.manage.verses') => route('admin.quran.verses.index', ['chapter' => $verse->chapter_id])]">
        <x-admin.quran.button variant="secondary" icon="spell-check" :href="route('admin.quran.verses.words.index', $verse->id)">{{ __('quran.manage.words') }}</x-admin.quran.button>
        @can('edit quran')
            <form method="POST" action="{{ route('admin.quran.verses.lock', $verse->id) }}">
                @csrf
                @method('PATCH')
                <x-admin.quran.button variant="secondary" :icon="$verse->is_locked ? 'unlock' : 'lock'">{{ $verse->is_locked ? __('quran.manage.unlock') : __('quran.manage.lock') }}</x-admin.quran.button>
            </form>
            <x-admin.quran.button icon="edit-2" :href="route('admin.quran.verses.edit', $verse->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <div x-data="{ tab: 'scripts' }">
        <div class="mb-4 flex flex-wrap gap-2" role="tablist">
            @foreach (['scripts' => __('quran.manage.scripts'), 'words' => __('quran.manage.words'), 'translations' => __('quran.manage.translations'), 'tafsirs' => __('quran.manage.tafsirs'), 'audio' => __('quran.manage.audio')] as $key => $label)
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                        class="rounded-xl px-4 py-2 text-sm font-semibold border"
                        :class="tab === '{{ $key }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white border-transparent' : 'border-gray-300'">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'scripts'">
            <x-admin.quran.panel>
                <dl class="grid grid-cols-2 md:grid-cols-6 gap-4 text-sm mb-6">
                    <div><dt class="text-gray-500">{{ __('quran.manage.juz') }}</dt><dd class="font-semibold">{{ $verse->juz_number }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('quran.manage.hizb') }}</dt><dd class="font-semibold">{{ $verse->hizb_number }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('quran.manage.page') }}</dt><dd class="font-semibold">{{ $verse->page_number }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('quran.manage.ruku') }}</dt><dd class="font-semibold">{{ $verse->ruku_number }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('quran.manage.sajdah') }}</dt><dd class="font-semibold">{{ $verse->sajdah_type ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('quran.manage.locked') }}</dt><dd><x-admin.quran.badge :on="$verse->is_locked" /></dd></div>
                </dl>
                <div class="space-y-4">
                    @foreach (\App\Domain\Quran\Models\Verse::TEXT_COLUMNS as $column)
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-1 text-xs font-mono text-gray-500" dir="ltr">{{ $column }}</div>
                            @if ($column === 'text_uthmani_tajweed')
                                <p class="font-quran text-2xl leading-loose" dir="rtl" lang="ar">{!! \App\Domain\Quran\Models\Verse::sanitizeTajweed($verse->{$column}) !!}</p>
                            @else
                                <p class="font-quran text-2xl leading-loose" dir="rtl" lang="ar">{{ $verse->{$column} ?? '—' }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-admin.quran.panel>
        </div>

        <div x-show="tab === 'words'" x-cloak>
            <x-admin.quran.table :empty="$verse->words->isEmpty()">
                <x-slot:head>
                    <th>#</th><th>{{ __('quran.manage.text') }}</th><th>{{ __('quran.manage.char_type') }}</th><th>{{ __('quran.manage.glosses') }}</th><th>{{ __('quran.manage.line') }}</th>
                </x-slot:head>
                @foreach ($verse->words as $word)
                    <tr>
                        <td>{{ $word->position }}</td>
                        <td class="font-quran text-xl" dir="rtl" lang="ar">{{ $word->text_uthmani }}</td>
                        <td dir="ltr">{{ $word->char_type->value }}</td>
                        <td>@foreach ($word->glosses as $gloss)<div><span class="text-xs text-gray-500">{{ $gloss->resource?->name }}:</span> {{ $gloss->text }}</div>@endforeach</td>
                        <td>{{ $word->page_number }}:{{ $word->line_number }}</td>
                    </tr>
                @endforeach
            </x-admin.quran.table>
        </div>

        <div x-show="tab === 'translations'" x-cloak>
            <x-admin.quran.table :empty="$verse->translations->isEmpty()">
                <x-slot:head><th>{{ __('quran.manage.resource') }}</th><th>{{ __('quran.manage.text') }}</th><th>{{ __('quran.manage.locked') }}</th></x-slot:head>
                @foreach ($verse->translations as $row)
                    <tr>
                        <td class="whitespace-nowrap"><a class="hover:text-accent" href="{{ route('admin.quran.resources.translations.index', ['resource' => $row->resource_id, 'chapter' => $verse->chapter_id]) }}">{{ $row->resource?->name }}</a> <span class="text-xs text-gray-500">{{ $row->resource?->language_iso }}</span></td>
                        <td>{{ strip_tags((string) $row->text) }}</td>
                        <td><x-admin.quran.badge :on="$row->is_locked" /></td>
                    </tr>
                @endforeach
            </x-admin.quran.table>
        </div>

        <div x-show="tab === 'tafsirs'" x-cloak>
            <x-admin.quran.table :empty="$verse->tafsirs->isEmpty()">
                <x-slot:head><th>{{ __('quran.manage.resource') }}</th><th>{{ __('quran.manage.text') }}</th><th>{{ __('quran.manage.locked') }}</th></x-slot:head>
                @foreach ($verse->tafsirs as $row)
                    <tr>
                        <td class="whitespace-nowrap"><a class="hover:text-accent" href="{{ route('admin.quran.resources.tafsirs.index', ['resource' => $row->resource_id, 'chapter' => $verse->chapter_id]) }}">{{ $row->resource?->name }}</a></td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $row->text), 400) }}</td>
                        <td><x-admin.quran.badge :on="$row->is_locked" /></td>
                    </tr>
                @endforeach
            </x-admin.quran.table>
        </div>

        <div x-show="tab === 'audio'" x-cloak>
            <x-admin.quran.table :empty="$verse->audioFiles->isEmpty()">
                <x-slot:head><th>{{ __('quran.manage.reciter') }}</th><th>{{ __('quran.manage.url') }}</th><th>{{ __('quran.manage.play') }}</th></x-slot:head>
                @foreach ($verse->audioFiles as $file)
                    <tr>
                        <td>{{ $file->resource?->name }}</td>
                        <td dir="ltr" class="font-mono text-xs break-all">{{ $file->url }}</td>
                        <td><audio controls preload="none" src="{{ \App\Domain\Quran\Services\AudioService::absolutize($file->url) }}" class="h-8"></audio></td>
                    </tr>
                @endforeach
            </x-admin.quran.table>
        </div>
    </div>
@endsection
