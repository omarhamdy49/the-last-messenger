@extends('layouts.public')

@section('title', __('quran.nav.juz').' '.$juz.' — '.__('quran.public.page_title'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrowUrl="route('public.quran')"
            :eyebrowLabel="__('quran.public.page_title')"
            eyebrowIcon="book-open"
            :title="__('quran.nav.juz').' '.$juz"
            :stats="[['icon' => 'list', 'label' => __('quran.public.verse_unit'), 'value' => $pagination['total']]]"
        />

        {{-- Prev / next juz --}}
        <div class="mb-6">
            <x-public.editorial.pager
                :label="__('quran.public.surah_navigation')"
                :prev="$hasPrevJuz ? ['url' => route('public.quran.juz', $juz - 1), 'title' => __('quran.nav.juz').' '.($juz - 1)] : null"
                :next="$hasNextJuz ? ['url' => route('public.quran.juz', $juz + 1), 'title' => __('quran.nav.juz').' '.($juz + 1)] : null"
                :prevLabel="__('quran.public.previous')"
                :nextLabel="__('quran.public.next')"
            />
        </div>

        @if(count($verses))
            @include('public.quran._reader', ['verses' => $verses])

            @if($pagination['total_pages'] > 1)
            <nav class="sx-pager justify-center mb-12" aria-label="pagination">
                <div class="sx-pages">
                    @if($pagination['has_prev'])
                    <a href="{{ route('public.quran.juz', $juz) }}?page={{ $currentPage - 1 }}&per_page={{ $perPage }}"
                       rel="prev" class="sx-page px-4">{{ __('quran.public.previous') }}</a>
                    @endif
                    <span class="sx-page tabular-nums px-4" aria-current="page">{{ $pagination['current_page'] }} / {{ $pagination['total_pages'] }}</span>
                    @if($pagination['has_next'])
                    <a href="{{ route('public.quran.juz', $juz) }}?page={{ $currentPage + 1 }}&per_page={{ $perPage }}"
                       rel="next" class="sx-page px-4">{{ __('quran.public.next') }}</a>
                    @endif
                </div>
            </nav>
            @endif
        @else
            <x-public.editorial.empty-state icon="book-x" :title="__('quran.empty.no_verses')" />
        @endif
    </div>
</div>
@endsection
