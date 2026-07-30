@extends('layouts.public')

@php
    $rtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $texts = collect($hadith['texts'] ?? []);
    $arabic = $texts->firstWhere('locale', 'ar');
    $translation = $texts->first(fn ($t) => $t['locale'] !== 'ar');
    $reference = $hadith['reference'] ?? [];
    $gradings = collect($hadith['gradings'] ?? []);
    $gradeKey = $hadith['display_grade'] ?? 'unknown';

    // Colour carries meaning here, so it is paired with the text label rather
    // than replacing it — a reader who cannot distinguish the hues still reads
    // the verdict.
    $gradeStyles = [
        'sahih' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'hasan' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        'daif' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'mawdu' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        'unknown' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    ];
@endphp

@section('title', ($reference['canonical'] ?? __('hadith.hadith')).' — '.__('site.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($translation['body'] ?? $arabic['body'] ?? ''), 155))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-4xl">

        <x-site.breadcrumb :items="[
            ['label' => __('hadith.hadiths'), 'url' => \Illuminate\Support\Facades\Route::has('public.hadith') ? route('public.hadith') : null],
            ['label' => $hadith['collection_name'] ?? $hadith['collection']],
            ['label' => $reference['canonical'] ?? ''],
        ]" />

        <article class="mt-6 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#16213e] shadow-sm overflow-hidden">

            {{-- Header: collection, book, chapter, and the derived grade --}}
            <header class="px-5 sm:px-8 py-6 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="text-2xl sm:text-3xl font-bold break-words">
                            {{ $reference['canonical'] ?? __('hadith.hadith') }}
                        </h1>
                        <p class="mt-2 text-sm text-white/85 break-words">
                            @if (! empty($hadith['book_title']))
                                {{ __('hadith.book') }}: {{ $hadith['book_title'] }}
                            @endif
                            @if (! empty($hadith['chapter_number']))
                                <span class="mx-2 opacity-60">•</span>
                                {{ __('hadith.chapter') }} {{ $hadith['chapter_number'] }}
                            @endif
                        </p>
                    </div>

                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold {{ $gradeStyles[$gradeKey] ?? $gradeStyles['unknown'] }}">
                        {{ __('hadith.grade') }}: {{ __('hadith.grade_'.$gradeKey) }}
                    </span>
                </div>
            </header>

            <div class="px-5 sm:px-8 py-8 space-y-8">

                {{-- Arabic: always present, always Amiri, always RTL regardless
                     of the interface language. --}}
                @if ($arabic)
                    <section aria-label="{{ __('hadith.arabic_text') }}">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                            {{ __('hadith.arabic_text') }}
                        </h2>
                        <p dir="rtl" lang="ar"
                           class="font-['Amiri'] text-2xl sm:text-3xl leading-[2.4] text-gray-900 dark:text-gray-100 break-words">
                            {{ $arabic['body'] }}
                        </p>
                    </section>
                @endif

                {{-- Translation: absent for ~7% of records, so this states the
                     absence rather than rendering an empty block. --}}
                <section aria-label="{{ __('hadith.translation') }}"
                         class="pt-6 border-t border-gray-200 dark:border-gray-800">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                        {{ __('hadith.translation') }}
                    </h2>

                    @php
                        // The source's English body already opens with the
                        // narrator line, so rendering the extracted field too
                        // prints it twice. The data keeps both — the body stays
                        // faithful to the source — and the view drops the
                        // redundant one.
                        $narrator = $translation['narrated_by'] ?? null;
                        $showNarrator = $narrator !== null
                            && ! str_starts_with(ltrim($translation['body'] ?? ''), $narrator);
                    @endphp

                    @if ($translation)
                        @if ($showNarrator)
                            {{-- dir must match the TEXT, not the page: on an RTL
                                 page an undirected Latin line renders its
                                 trailing colon at the front. --}}
                            <p dir="{{ $translation['locale'] === 'ar' ? 'rtl' : 'ltr' }}"
                               lang="{{ $translation['locale'] }}"
                               class="text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] mb-2 break-words text-start">
                                {{ $narrator }}
                            </p>
                        @endif
                        <p dir="{{ $translation['locale'] === 'ar' ? 'rtl' : 'ltr' }}"
                           lang="{{ $translation['locale'] }}"
                           class="text-base leading-8 text-gray-700 dark:text-gray-300 whitespace-pre-line break-words text-start">
                            {{ $translation['body'] }}
                        </p>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('hadith.no_translation') }}</p>
                    @endif
                </section>

                {{-- The three scholarly reference lines, exactly as the source
                     presents them. --}}
                <section aria-label="{{ __('hadith.reference') }}"
                         class="pt-6 border-t border-gray-200 dark:border-gray-800">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                        {{ __('hadith.reference') }}
                    </h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex flex-wrap gap-x-2">
                            <dt class="text-gray-600 dark:text-gray-300">{{ __('hadith.reference_canonical_label') }}:</dt>
                            <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $reference['canonical'] ?? '—' }}</dd>
                        </div>
                        @if (! empty($reference['in_book']))
                            <div class="flex flex-wrap gap-x-2">
                                <dt class="text-gray-600 dark:text-gray-300">{{ __('hadith.reference_in_book_label') }}:</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ $reference['in_book'] }}</dd>
                            </div>
                        @endif
                        @if (! empty($reference['translation']))
                            <div class="flex flex-wrap gap-x-2">
                                <dt class="text-gray-600 dark:text-gray-300">{{ __('hadith.reference_translation_label') }}:</dt>
                                <dd class="text-gray-900 dark:text-gray-100">
                                    {{ $reference['translation'] }}
                                    @if (! empty($reference['translation_deprecated']))
                                        <span class="ms-2 text-xs text-amber-700 dark:text-amber-300">
                                            ({{ __('hadith.reference_deprecated_note') }})
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Gradings: one row per claim. raw_grade is shown verbatim so a
                     reader can see what the source actually said, not only how we
                     classified it. --}}
                <section aria-label="{{ __('hadith.grade') }}"
                         class="pt-6 border-t border-gray-200 dark:border-gray-800">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">
                        {{ __('hadith.grade') }}
                    </h2>

                    @forelse ($gradings as $grading)
                        <div class="flex flex-wrap items-center gap-2 py-2 border-b last:border-0 border-gray-100 dark:border-gray-800">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $gradeStyles[$grading['grade']] ?? $gradeStyles['unknown'] }}">
                                {{ __('hadith.grade_'.$grading['grade']) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('hadith.grade_scope_'.$grading['scope']) }}
                            </span>
                            @if (! empty($grading['part_label']))
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $grading['part_label'] }})
                                </span>
                            @endif
                            @if (! empty($grading['scholar']))
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    — {{ __('hadith.graded_by') }}: {{ $grading['scholar'] }}
                                </span>
                            @endif
                            <span class="w-full text-xs text-gray-500 dark:text-gray-400 break-words"
                                  title="{{ __('hadith.raw_grade') }}">
                                {{ $grading['raw_grade'] }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('hadith.no_gradings') }}</p>
                    @endforelse
                </section>
            </div>
        </article>
    </div>
@endsection
