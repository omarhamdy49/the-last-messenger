@extends('layouts.public')

@section('title', __('quran.nav.page').' '.$pageNumber.' — '.__('quran.public.page_title'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrowUrl="route('public.quran')"
            :eyebrowLabel="__('quran.public.page_title')"
            eyebrowIcon="book-open"
            :title="__('quran.nav.page').' '.$pageNumber"
            :stats="[['icon' => 'list', 'label' => __('quran.public.verse_unit'), 'value' => count($verses)]]"
        />

        {{-- Prev / next page --}}
        <div class="mb-6">
            <x-public.editorial.pager
                :label="__('quran.public.surah_navigation')"
                :prev="$hasPrevPage ? ['url' => route('public.quran.page', $pageNumber - 1), 'title' => __('quran.nav.page').' '.($pageNumber - 1)] : null"
                :next="$hasNextPage ? ['url' => route('public.quran.page', $pageNumber + 1), 'title' => __('quran.nav.page').' '.($pageNumber + 1)] : null"
                :prevLabel="__('quran.public.prev_page')"
                :nextLabel="__('quran.public.next_page')"
            />
        </div>

        @if(count($verses))
            @include('public.quran._reader', ['verses' => $verses])
        @else
            <x-public.editorial.empty-state icon="book-x" :title="__('quran.empty.no_verses')" />
        @endif
    </div>
</div>
@endsection
