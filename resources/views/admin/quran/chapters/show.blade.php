@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$chapter->id.'. '.$chapter->name_arabic" icon="book-open" :subtitle="$chapter->name_simple.' · '.$chapter->name_complex"
        :crumbs="[__('quran.manage.chapters') => route('admin.quran.chapters.index')]">
        <x-admin.quran.button variant="secondary" icon="list" :href="route('admin.quran.verses.index', ['chapter' => $chapter->id])">{{ __('quran.manage.verses') }}</x-admin.quran.button>
        @can('edit quran')
            <x-admin.quran.button icon="edit-2" :href="route('admin.quran.chapters.edit', $chapter->id)">{{ __('quran.manage.edit') }}</x-admin.quran.button>
        @endcan
    </x-admin.quran.page-header>
@endsection

@section('content')
    <x-admin.quran.flash />

    <x-admin.quran.panel :title="__('quran.manage.details')" icon="info">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><dt class="text-gray-500">{{ __('quran.manage.revelation_place') }}</dt><dd class="font-semibold">{{ __('quran.manage.'.$chapter->revelation_place->value) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.revelation_order') }}</dt><dd class="font-semibold">{{ $chapter->revelation_order }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.verses') }}</dt><dd class="font-semibold">{{ $chapter->verses_count }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.pages') }}</dt><dd class="font-semibold" dir="ltr">{{ $chapter->first_page }}–{{ $chapter->last_page }}</dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.bismillah') }}</dt><dd><x-admin.quran.badge :on="$chapter->bismillah_pre" /></dd></div>
            <div><dt class="text-gray-500">{{ __('quran.manage.status') }}</dt><dd><x-admin.quran.badge :on="$chapter->is_published" :on-label="__('quran.manage.published')" :off-label="__('quran.manage.hidden')" /></dd></div>
            <div><dt class="text-gray-500">{{ __('locations.singular') }}</dt><dd class="font-semibold">{{ $chapter->location?->name() ?? '—' }}</dd></div>
            @if ($chapter->cover_image)
                <div><dt class="text-gray-500">{{ __('quran.manage.cover') }}</dt><dd><img src="{{ Storage::disk('public')->url($chapter->cover_image) }}" alt="" class="h-16 rounded-lg"></dd></div>
            @endif
        </dl>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
            @foreach (config('translatable.locales', ['ar', 'en', 'fr', 'tr', 'ur']) as $locale)
                <div class="rounded-xl border border-gray-200 p-3">
                    <div class="text-xs uppercase text-gray-500">{{ $locale }}</div>
                    <div class="font-semibold">{{ $chapter->getTranslation('name', $locale) ?? '—' }}</div>
                </div>
            @endforeach
        </div>
    </x-admin.quran.panel>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.chapter_infos') }}</h2>
    <x-admin.quran.table :empty="$chapter->infos->isEmpty()">
        <x-slot:head>
            <th>{{ __('quran.manage.resource') }}</th>
            <th>{{ __('quran.manage.language') }}</th>
            <th>{{ __('quran.manage.summary') }}</th>
            <th>{{ __('quran.manage.locked') }}</th>
        </x-slot:head>
        @foreach ($chapter->infos as $info)
            <tr>
                <td><a class="hover:text-accent" href="{{ route('admin.quran.resources.chapter-infos.index', ['resource' => $info->resource_id, 'chapter' => $chapter->id]) }}">{{ $info->resource?->name }}</a></td>
                <td dir="ltr">{{ $info->language_iso }}</td>
                <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $info->short_text), 140) }}</td>
                <td><x-admin.quran.badge :on="$info->is_locked" /></td>
            </tr>
        @endforeach
    </x-admin.quran.table>

    <h2 class="text-lg font-bold mb-3">{{ __('quran.manage.chapter_audio') }}</h2>
    <x-admin.quran.table :empty="$chapter->audioFiles->isEmpty()">
        <x-slot:head>
            <th>{{ __('quran.manage.reciter') }}</th>
            <th>{{ __('quran.manage.url') }}</th>
            <th>{{ __('quran.manage.timestamps') }}</th>
            <th>{{ __('quran.manage.local_file') }}</th>
        </x-slot:head>
        @foreach ($chapter->audioFiles as $file)
            <tr>
                <td><a class="hover:text-accent" href="{{ route('admin.quran.resources.chapter-audio.index', $file->resource_id) }}">{{ $file->resource?->name }}</a></td>
                <td dir="ltr" class="font-mono text-xs break-all">{{ $file->url }}</td>
                <td><x-admin.quran.badge :on="$file->has_timestamps" /></td>
                <td><x-admin.quran.badge :on="$file->is_local" /></td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
