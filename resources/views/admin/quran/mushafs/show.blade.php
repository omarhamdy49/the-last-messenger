@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$mushaf->name" icon="book" :subtitle="$mushaf->description"
        :crumbs="[__('quran.manage.mushafs') => route('admin.quran.mushafs.index')]">
        @can('sync quran')
            <form method="POST" action="{{ route('admin.quran.mushafs.fonts', $mushaf->id) }}">@csrf
                <x-admin.quran.button variant="secondary" icon="download">{{ __('quran.manage.download_fonts') }}</x-admin.quran.button>
            </form>
            <form method="POST" action="{{ route('admin.quran.mushafs.resync', $mushaf->id) }}">@csrf
                <x-admin.quran.button variant="secondary" icon="refresh-cw">{{ __('quran.manage.resync') }}</x-admin.quran.button>
            </form>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    @can('edit quran')
        <x-admin.quran.panel :title="__('quran.manage.settings')" icon="settings">
            <form method="POST" action="{{ route('admin.quran.mushafs.update', $mushaf->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                @method('PUT')
                <div class="md:col-span-2"><x-admin.quran.field name="name" :label="__('quran.manage.name')" :value="$mushaf->name" required maxlength="120" /></div>
                <x-admin.quran.field name="is_active" type="checkbox" :label="__('quran.manage.active')" :value="$mushaf->is_active" />
                <x-admin.quran.field name="is_default" type="checkbox" :label="__('quran.manage.default')" :value="$mushaf->is_default" />
                <div><x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button></div>
            </form>
        </x-admin.quran.panel>
    @endcan

    <x-admin.quran.panel :title="__('quran.manage.page_viewer')" icon="file-text">
        <form method="GET" class="mb-4 flex items-end gap-3">
            <x-admin.quran.field name="page" type="number" :label="__('quran.manage.page')" :value="$pageNumber" min="1" :max="$mushaf->pages_count" />
            <x-admin.quran.button variant="secondary" icon="arrow-right">{{ __('quran.manage.go') }}</x-admin.quran.button>
            @if ($pageNumber > 1)<x-admin.quran.button variant="secondary" :href="route('admin.quran.mushafs.show', [$mushaf->id, 'page' => $pageNumber - 1])">{{ __('quran.manage.previous') }}</x-admin.quran.button>@endif
            @if ($pageNumber < $mushaf->pages_count)<x-admin.quran.button variant="secondary" :href="route('admin.quran.mushafs.show', [$mushaf->id, 'page' => $pageNumber + 1])">{{ __('quran.manage.next') }}</x-admin.quran.button>@endif
        </form>

        @if ($page)
            <p class="mb-3 text-xs text-gray-500" dir="ltr">{{ __('quran.manage.verse_range') }}: {{ $page->first_verse_id }}–{{ $page->last_verse_id }} · {{ $page->verses_count }}</p>
        @endif

        @if ($lines === [])
            <p class="text-sm text-gray-500">{{ __('quran.manage.page_not_synced') }}</p>
        @else
            <div class="mx-auto max-w-2xl rounded-xl border border-gray-200 p-6" dir="rtl" lang="ar">
                @foreach ($lines as $lineNumber => $words)
                    <div class="flex justify-between gap-2 py-1 font-quran text-2xl leading-loose">
                        <span class="text-xs text-gray-400 self-center" dir="ltr">{{ $lineNumber }}</span>
                        <span class="flex-1 text-center">
                            @foreach ($words as $word)
                                <span title="{{ $word['verse_key'] }}:{{ $word['position'] }}">{{ $word['text_qpc_hafs'] ?? $word['text'] }}</span>
                            @endforeach
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.quran.panel>
@endsection
