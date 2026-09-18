@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.words_of', ['key' => $verse->verse_key])" icon="spell-check"
        :crumbs="[__('quran.manage.verses') => route('admin.quran.verses.index', ['chapter' => $verse->chapter_id]), $verse->verse_key => route('admin.quran.verses.show', $verse->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @if ($glossResources->isEmpty())
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ __('quran.manage.no_gloss_resources') }}</div>
    @endif

    <x-admin.quran.table :empty="$verse->words->isEmpty()">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('quran.manage.text') }}</th>
            <th>{{ __('quran.manage.char_type') }}</th>
            <th>{{ __('quran.manage.glosses') }}</th>
        </x-slot:head>
        @foreach ($verse->words as $word)
            @php $byResource = $word->glosses->keyBy('resource_id'); @endphp
            <tr>
                <td>{{ $word->position }}</td>
                <td class="font-quran text-2xl whitespace-nowrap" dir="rtl" lang="ar">{{ $word->text_uthmani }}
                    @if ($word->audio_url)
                        <audio controls preload="none" src="{{ \App\Domain\Quran\Services\AudioService::absolutize($word->audio_url) }}" class="mt-1 h-8 w-40"></audio>
                    @endif
                </td>
                <td dir="ltr">{{ $word->char_type->value }}</td>
                <td class="w-full">
                    @if ($glossResources->isNotEmpty() && $word->char_type->value === 'word')
                        <form method="POST" action="{{ route('admin.quran.words.update', $word->id) }}" class="grid grid-cols-1 md:grid-cols-2 gap-2 items-end">
                            @csrf
                            @method('PUT')
                            @foreach ($glossResources as $resource)
                                <x-admin.quran.field :name="'glosses['.$resource->id.']'" :label="$resource->name.' ('.$resource->language_iso.')'"
                                    :value="$byResource->get($resource->id)?->text" dir="auto" maxlength="500" />
                            @endforeach
                            @can('edit quran')
                                <div><x-admin.quran.button variant="secondary" icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button></div>
                            @endcan
                        </form>
                    @else
                        @foreach ($word->glosses as $gloss)<div>{{ $gloss->text }}</div>@endforeach
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.quran.table>
@endsection
