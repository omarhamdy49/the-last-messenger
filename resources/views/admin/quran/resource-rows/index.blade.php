@extends('layouts.admin')

@php
    $isInfo = $rowsSegment === 'chapter-infos';
    $isTranslation = $rowsSegment === 'translations';
    $rtl = in_array($resource->language_iso, ['ar', 'ur', 'fa', 'he'], true) ? 'rtl' : 'auto';
    $chapterOptions = $chapters->mapWithKeys(fn ($c) => [$c->id => $c->id.'. '.$c->name_arabic])->all();
@endphp

@section('page-header')
    <x-admin.quran.page-header :title="$resource->displayName()" icon="file-text" :subtitle="__('quran.manage.rows_help')"
        :crumbs="[$resource->kind->label() => route('admin.quran.resources.index', $resource->kind->routeSegment()), $resource->name => route('admin.quran.resources.show', $resource->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-64"><x-admin.quran.field name="chapter" type="select" :label="__('quran.manage.chapter')" :value="$chapter" :options="$chapterOptions" /></div>
            <x-admin.quran.button variant="secondary" icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
        </form>
    </x-admin.quran.panel>

    @can('edit quran')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-admin.quran.panel class="lg:col-span-2" :title="__('quran.manage.add_row')" icon="plus-circle">
                <form method="POST" action="{{ route('admin.quran.resources.'.$rowsSegment.'.store', $resource->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if ($isInfo)
                            <x-admin.quran.field name="chapter_id" type="select" :label="__('quran.manage.chapter')" :value="$chapter" :options="$chapterOptions" />
                            <x-admin.quran.field name="source" :label="__('quran.manage.source')" maxlength="255" dir="auto" />
                        @else
                            <x-admin.quran.field name="verse_key" :label="__('quran.manage.key')" placeholder="2:255" dir="ltr" maxlength="10" required />
                        @endif
                        <x-admin.quran.field name="is_locked" type="checkbox" :label="__('quran.manage.locked')" :value="true" />
                    </div>
                    @if ($isInfo)
                        <x-admin.quran.field name="short_text" type="textarea" rows="2" :label="__('quran.manage.summary')" :dir="$rtl" maxlength="2000" />
                    @endif
                    <x-admin.quran.field name="text" type="textarea" rows="5" :label="__('quran.manage.text')" :dir="$rtl" required
                        :help="$isTranslation ? __('quran.manage.translation_text_help') : __('quran.manage.rich_text_help')" />
                    <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>

            <x-admin.quran.panel :title="__('quran.manage.import_file')" icon="upload">
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quran.resources.'.$rowsSegment.'.import', $resource->id) }}" class="space-y-4">
                    @csrf
                    <x-admin.quran.field name="file" type="file" :label="__('quran.manage.json_or_csv')" accept=".json,.csv,application/json,text/csv" required />
                    <p class="text-xs text-gray-500" dir="ltr">{{ $isInfo ? '[{"chapter_id": 1, "text": "…", "short_text": "…"}]' : '[{"verse_key": "1:1", "text": "…"}]' }}</p>
                    <x-admin.quran.button variant="secondary" icon="upload">{{ __('quran.manage.import') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>
        </div>
    @endcan

    <x-admin.quran.table :empty="$items->isEmpty()">
        <x-slot:head>
            <th>{{ $isInfo ? __('quran.manage.chapter') : __('quran.manage.key') }}</th>
            <th class="w-full">{{ __('quran.manage.text') }}</th>
            <th>{{ __('quran.manage.locked') }}</th>
            <th>{{ __('quran.manage.updated') }}</th>
        </x-slot:head>
        @foreach ($items as $item)
            <tr>
                <td dir="ltr" class="whitespace-nowrap font-mono font-semibold">{{ $isInfo ? $item->chapter_id.'. '.$item->chapter?->name_simple : $item->verse_key }}</td>
                <td>
                    <details>
                        <summary class="cursor-pointer" dir="{{ $rtl }}">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($isInfo ? ($item->short_text ?: $item->text) : $item->text)), 220) }}</summary>
                        @can('edit quran')
                            <form method="POST" action="{{ route('admin.quran.resources.'.$rowsSegment.'.update', [$resource->id, $item->id]) }}" class="mt-3 space-y-3">
                                @csrf
                                @method('PUT')
                                @if ($isInfo)
                                    <x-admin.quran.field name="short_text" type="textarea" rows="2" :label="__('quran.manage.summary')" :value="$item->short_text" :dir="$rtl" />
                                    <x-admin.quran.field name="source" :label="__('quran.manage.source')" :value="$item->source" dir="auto" />
                                @endif
                                <x-admin.quran.field name="text" type="textarea" rows="6" :label="__('quran.manage.text')" :value="$item->text" :dir="$rtl" required />
                                @if ($isTranslation && ! empty($item->foot_notes))
                                    <div class="text-xs text-gray-600">
                                        @foreach ($item->foot_notes as $noteId => $note)
                                            <input type="hidden" name="foot_notes[{{ $loop->index }}][id]" value="{{ $noteId }}">
                                            <x-admin.quran.field :name="'foot_notes['.$loop->index.'][text]'" :label="__('quran.manage.footnote').' '.$noteId" :value="$note" :dir="$rtl" />
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-admin.quran.field name="is_locked" type="checkbox" :label="__('quran.manage.locked')" :value="$item->is_locked" />
                                    <x-admin.quran.button variant="secondary" icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.quran.resources.'.$rowsSegment.'.destroy', [$resource->id, $item->id]) }}" class="mt-2"
                                  x-data @submit="if (!window.confirm(@js(__('quran.manage.confirm_delete')))) $event.preventDefault()">
                                @csrf @method('DELETE')
                                <x-admin.quran.button variant="danger" icon="trash-2">{{ __('quran.manage.delete') }}</x-admin.quran.button>
                            </form>
                        @endcan
                    </details>
                </td>
                <td><x-admin.quran.badge :on="$item->is_locked" /></td>
                <td class="whitespace-nowrap text-xs">{{ $item->updated_at?->diffForHumans() }}</td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $items->links() }}</x-slot:footer>
    </x-admin.quran.table>
@endsection
