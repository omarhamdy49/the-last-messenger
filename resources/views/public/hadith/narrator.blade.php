@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $narrator = $profile->narrator;
    $name = $narrator->getTranslation('name', $locale, true);
    $fullName = $narrator->getTranslation('full_name', $locale, true) ?: null;
    $biography = $narrator->getTranslation('biography', $locale, true) ?: null;
    $gradeStyles = [
        'sahih' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'hasan' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        'daif' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'mawdu' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        'unknown' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    ];
@endphp

@section('title', $name.' — '.__('site.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($biography ?? $fullName ?? $name), 155))

@section('content')
    <div class="container mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <x-site.breadcrumb :items="[
            ['label' => __('hadith.hadiths'), 'url' => route('public.hadith')],
            ['label' => __('hadith.narrators')],
            ['label' => $name],
        ]" />

        <header class="mt-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] px-6 py-8 text-white shadow-sm sm:px-10">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div>
                    <p class="text-sm font-semibold text-white/75">{{ __('hadith.narrator_profile') }}</p>
                    <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $name }}</h1>
                    @if ($fullName && $fullName !== $name)
                        <p class="mt-3 max-w-3xl text-white/85">{{ $fullName }}</p>
                    @endif
                </div>

                @if ($narrator->sahaba_id)
                    <a href="{{ route('public.sahaba.show', ['slug' => $narrator->sahaba_id]) }}"
                       class="inline-flex min-h-11 items-center rounded-full bg-white px-5 py-2 text-sm font-bold text-[#0056b3] transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#218838]">
                        {{ __('hadith.view_companion_profile') }}
                    </a>
                @endif
            </div>
        </header>

        @php
            $facts = array_filter([
                __('sahaba.kunya') => $narrator->getTranslation('kunya', $locale, true) ?: null,
                __('hadith.birth_year') => $narrator->birth_year_hijri !== null ? $narrator->birth_year_hijri.' '.__('sahaba.year_hijri') : null,
                __('hadith.death_year') => $narrator->death_year_hijri !== null ? $narrator->death_year_hijri.' '.__('sahaba.year_hijri') : null,
                __('hadith.generation') => $narrator->generation,
                __('hadith.reliability') => $narrator->reliability,
            ]);
        @endphp

        @if ($facts || $biography)
            <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-[#16213e] sm:p-8">
                @if ($facts)
                    <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($facts as $label => $value)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if ($biography)
                    <div class="{{ $facts ? 'mt-7 border-t border-gray-200 pt-7 dark:border-gray-800' : '' }}">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('sahaba.section_story') }}</h2>
                        <p class="mt-3 whitespace-pre-line text-base leading-8 text-gray-700 dark:text-gray-300">{{ $biography }}</p>
                    </div>
                @endif
            </section>
        @endif

        <section class="mt-8" aria-labelledby="narrated-hadiths-heading">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 id="narrated-hadiths-heading" class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('sahaba.hadith_narrated') }}</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hadith.hadiths_count', ['count' => $profile->hadiths->total()]) }}</span>
            </div>

            <div class="space-y-4">
                @foreach ($profile->hadiths as $hadith)
                    @php
                        $text = $hadith->textFor($locale);
                        $grade = $hadith->display_grade?->value;
                    @endphp
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-[#16213e] sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <a href="{{ route('public.hadith.show', ['collection' => $hadith->collection->code, 'number' => $hadith->hadith_number]) }}"
                               class="font-bold text-[#0056b3] hover:underline focus:outline-none focus:ring-2 focus:ring-[#0056b3] focus:ring-offset-2 dark:text-[#64CCC5] dark:focus:ring-offset-[#16213e]">
                                {{ $hadith->collection->getTranslation('name', $locale, true) }} · {{ __('hadith.hadith') }} {{ $hadith->hadith_number }}
                            </a>
                            @if ($grade)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $gradeStyles[$grade] ?? $gradeStyles['unknown'] }}">{{ __('hadith.grade_'.$grade) }}</span>
                            @endif
                        </div>
                        @if ($text)
                            <p dir="{{ $text->locale === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ $text->locale }}"
                               class="mt-4 line-clamp-3 text-start leading-7 text-gray-700 dark:text-gray-300">{{ $text->body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($profile->hadiths->hasPages())
                <div class="mt-8">{{ $profile->hadiths->withQueryString()->links() }}</div>
            @endif
        </section>
    </div>
@endsection
