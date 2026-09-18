@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.timestamps_for', ['chapter' => $chapter?->name_arabic ?? $file->chapter_id])" icon="timer"
        :subtitle="$resource->displayName()"
        :crumbs="[$resource->name => route('admin.quran.resources.chapter-audio.index', $resource->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel>
        <audio id="chapter-audio" controls preload="metadata" src="{{ \App\Domain\Quran\Services\AudioService::absolutize($file->url) }}" class="w-full"></audio>
    </x-admin.quran.panel>

    @can('edit quran')
        <x-admin.quran.panel :title="__('quran.manage.edit_timestamps')" icon="code">
            <p class="mb-3 text-sm text-gray-500">{{ __('quran.manage.timestamps_help', ['count' => $chapter?->verses_count ?? '?']) }}</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quran.resources.chapter-audio.timestamps.update', [$resource->id, $file->id]) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-admin.quran.field name="timestamps" type="textarea" rows="16" :label="__('quran.manage.timestamps_json')" :value="$json" dir="ltr" class="font-mono text-xs" />
                <x-admin.quran.field name="timestamps_file" type="file" :label="__('quran.manage.or_upload_json')" accept=".json,application/json" />
                <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
            </form>
        </x-admin.quran.panel>
    @endcan

    <x-admin.quran.table :empty="$timings === []" :empty-text="__('quran.manage.no_timestamps')">
        <x-slot:head>
            <th>{{ __('quran.manage.key') }}</th>
            <th>{{ __('quran.manage.from') }}</th>
            <th>{{ __('quran.manage.to') }}</th>
            <th>{{ __('quran.manage.segments') }}</th>
            <th></th>
        </x-slot:head>
        @foreach ($timings as $timing)
            <tr>
                <td dir="ltr" class="font-mono font-semibold">{{ $timing['verse_key'] }}</td>
                <td dir="ltr">{{ gmdate('i:s', intdiv($timing['from'], 1000)) }}.{{ sprintf('%03d', $timing['from'] % 1000) }}</td>
                <td dir="ltr">{{ gmdate('i:s', intdiv($timing['to'], 1000)) }}.{{ sprintf('%03d', $timing['to'] % 1000) }}</td>
                <td>{{ count($timing['segments'] ?? []) }}</td>
                <td>
                    <button type="button" class="rounded-lg border border-gray-300 px-2 py-1 text-xs"
                            onclick="const a = document.getElementById('chapter-audio'); a.currentTime = {{ $timing['from'] / 1000 }}; a.play();">
                        {{ __('quran.manage.play') }}
                    </button>
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
