@extends('layouts.admin')

@php $chapterOptions = $chapters->mapWithKeys(fn ($c) => [$c->id => $c->id.'. '.$c->name_arabic])->all(); @endphp

@section('page-header')
    <x-admin.quran.page-header :title="$resource->displayName()" icon="audio-lines" :subtitle="__('quran.manage.ayah_audio_help')"
        :crumbs="[$resource->kind->label() => route('admin.quran.resources.index', $resource->kind->routeSegment()), $resource->name => route('admin.quran.resources.show', $resource->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @can('edit quran')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-admin.quran.panel :title="__('quran.manage.add_audio_file')" icon="plus-circle">
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quran.resources.audio-files.store', $resource->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-admin.quran.field name="verse_key" :label="__('quran.manage.key')" placeholder="1:1" dir="ltr" required />
                        <x-admin.quran.field name="duration_ms" type="number" :label="__('quran.manage.duration_ms')" min="0" />
                    </div>
                    <x-admin.quran.field name="url" type="url" :label="__('quran.manage.url')" dir="ltr" :help="__('quran.manage.url_or_upload')" />
                    <x-admin.quran.field name="file" type="file" :label="__('quran.manage.upload_mp3')" accept="audio/mpeg" />
                    <x-admin.quran.field name="segments" type="textarea" rows="2" :label="__('quran.manage.segments')" dir="ltr" placeholder="[[1,0,480],[2,480,1100]]" />
                    <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>

            <x-admin.quran.panel :title="__('quran.manage.fill_template')" icon="wand-2">
                <form method="POST" action="{{ route('admin.quran.resources.audio-files.fill', $resource->id) }}" class="space-y-4">
                    @csrf
                    <x-admin.quran.field name="url_template" :label="__('quran.manage.url_template')" dir="ltr" required
                        placeholder="https://everyayah.com/data/Alafasy_128kbps/{surah:03}{ayah:03}.mp3" :help="__('quran.manage.url_template_ayah_help')" />
                    <x-admin.quran.field name="chapter" type="select" :label="__('quran.manage.chapter')" :options="$chapterOptions" :help="__('quran.manage.fill_all_chapters_help')" />
                    <x-admin.quran.button variant="secondary" icon="wand-2">{{ __('quran.manage.fill') }}</x-admin.quran.button>
                </form>
            </x-admin.quran.panel>
        </div>
    @endcan

    <x-admin.quran.panel>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-64"><x-admin.quran.field name="chapter" type="select" :label="__('quran.manage.chapter')" :value="$chapter" :options="$chapterOptions" /></div>
            <x-admin.quran.button variant="secondary" icon="filter">{{ __('quran.manage.filter') }}</x-admin.quran.button>
        </form>
    </x-admin.quran.panel>

    <x-admin.quran.table :empty="$files->isEmpty()">
        <x-slot:head>
            <th>{{ __('quran.manage.key') }}</th>
            <th class="w-full">{{ __('quran.manage.url') }}</th>
            <th>{{ __('quran.manage.play') }}</th>
            <th>{{ __('quran.manage.local_file') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($files as $file)
            <tr>
                <td dir="ltr" class="font-mono font-semibold">{{ $file->verse_key }}</td>
                <td>
                    <details>
                        <summary class="cursor-pointer font-mono text-xs break-all" dir="ltr">{{ $file->url }}</summary>
                        @can('edit quran')
                            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.quran.resources.audio-files.update', [$resource->id, $file->id]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                @csrf @method('PUT')
                                <div class="md:col-span-2"><x-admin.quran.field name="url" type="url" :label="__('quran.manage.url')" :value="$file->url" dir="ltr" /></div>
                                <x-admin.quran.field name="file" type="file" :label="__('quran.manage.upload_mp3')" accept="audio/mpeg" />
                                <x-admin.quran.field name="duration_ms" type="number" :label="__('quran.manage.duration_ms')" :value="$file->duration_ms" min="0" />
                                <div><x-admin.quran.button variant="secondary" icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button></div>
                            </form>
                        @endcan
                    </details>
                </td>
                <td><audio controls preload="none" src="{{ \App\Domain\Quran\Services\AudioService::absolutize($file->url) }}" class="h-8 w-48"></audio></td>
                <td><x-admin.quran.badge :on="$file->is_local" /></td>
                <td class="text-end">
                    @can('edit quran')
                        <form method="POST" action="{{ route('admin.quran.resources.audio-files.destroy', [$resource->id, $file->id]) }}"
                              x-data @submit="if (!window.confirm(@js(__('quran.manage.confirm_delete')))) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <x-admin.quran.button variant="danger" icon="trash-2">{{ __('quran.manage.delete') }}</x-admin.quran.button>
                        </form>
                    @endcan
                </td>
            </tr>
        @endforeach
        <x-slot:footer>{{ $files->links() }}</x-slot:footer>
    </x-admin.quran.table>
@endsection
