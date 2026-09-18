@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$resource->displayName()" icon="disc-3" :subtitle="__('quran.manage.chapter_audio_help', ['count' => $files->count()])"
        :crumbs="[$resource->kind->label() => route('admin.quran.resources.index', $resource->kind->routeSegment()), $resource->name => route('admin.quran.resources.show', $resource->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @can('edit quran')
        <x-admin.quran.panel :title="__('quran.manage.fill_template')" icon="wand-2">
            <form method="POST" action="{{ route('admin.quran.resources.chapter-audio.fill', $resource->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-72">
                    <x-admin.quran.field name="url_template" :label="__('quran.manage.url_template')" dir="ltr" required
                        placeholder="https://server8.mp3quran.net/afs/{surah:03}.mp3" :help="__('quran.manage.url_template_chapter_help')" />
                </div>
                <x-admin.quran.button variant="secondary" icon="wand-2">{{ __('quran.manage.fill') }}</x-admin.quran.button>
            </form>
        </x-admin.quran.panel>
    @endcan

    <x-admin.quran.table>
        <x-slot:head>
            <th>{{ __('quran.manage.chapter') }}</th>
            <th class="w-full">{{ __('quran.manage.url') }}</th>
            <th>{{ __('quran.manage.play') }}</th>
            <th>{{ __('quran.manage.timestamps') }}</th>
            <th class="text-end">{{ __('quran.manage.actions') }}</th>
        </x-slot:head>
        @foreach ($chapters as $chapter)
            @php $file = $files->get($chapter->id); @endphp
            <tr>
                <td class="whitespace-nowrap"><span class="font-bold">{{ $chapter->id }}.</span> <span class="font-quran" dir="rtl">{{ $chapter->name_arabic }}</span></td>
                <td>
                    <details>
                        <summary class="cursor-pointer font-mono text-xs break-all" dir="ltr">{{ $file?->url ?? __('quran.manage.missing') }}</summary>
                        @can('edit quran')
                            <form method="POST" enctype="multipart/form-data" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 items-end"
                                  action="{{ $file ? route('admin.quran.resources.chapter-audio.update', [$resource->id, $file->id]) : route('admin.quran.resources.chapter-audio.store', $resource->id) }}">
                                @csrf
                                @if ($file) @method('PUT') @else <input type="hidden" name="chapter_id" value="{{ $chapter->id }}"> @endif
                                <div class="md:col-span-2"><x-admin.quran.field name="url" type="url" :label="__('quran.manage.url')" :value="$file?->url" dir="ltr" /></div>
                                <x-admin.quran.field name="file" type="file" :label="__('quran.manage.upload_mp3')" accept="audio/mpeg" />
                                <x-admin.quran.field name="duration_ms" type="number" :label="__('quran.manage.duration_ms')" :value="$file?->duration_ms" min="0" />
                                <div><x-admin.quran.button variant="secondary" icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button></div>
                            </form>
                        @endcan
                    </details>
                </td>
                <td>@if ($file)<audio controls preload="none" src="{{ \App\Domain\Quran\Services\AudioService::absolutize($file->url) }}" class="h-8 w-48"></audio>@endif</td>
                <td>@if ($file)<x-admin.quran.badge :on="$file->has_timestamps" />@endif</td>
                <td>
                    @if ($file)
                        <div class="flex justify-end gap-2">
                            <x-admin.quran.button variant="secondary" icon="timer" :href="route('admin.quran.resources.chapter-audio.timestamps.edit', [$resource->id, $file->id])">{{ __('quran.manage.timestamps') }}</x-admin.quran.button>
                            @can('edit quran')
                                <form method="POST" action="{{ route('admin.quran.resources.chapter-audio.destroy', [$resource->id, $file->id]) }}"
                                      x-data @submit="if (!window.confirm(@js(__('quran.manage.confirm_delete')))) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <x-admin.quran.button variant="danger" icon="trash-2">{{ __('quran.manage.delete') }}</x-admin.quran.button>
                                </form>
                            @endcan
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
