@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $collectionTitle = $collection->title($locale);
    $grade = $hadith->grade?->value ?? ($hadith->grade ?: null);
    $gradeStyles = [
        'sahih' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'hasan' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'daif'  => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'mawdu' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    ];
    // Three readings of the same matn: linked HTML for the page, and plain
    // text wherever markup would be wrong (meta description, JSON-LD, and the
    // buffer the copy button reads).
    $matnHtml = \App\Domain\Hadith\Support\QuranCitation::toHtml($hadith->text_ar);
    $matnPlain = \App\Domain\Hadith\Support\QuranCitation::strip($hadith->text_ar);
    $excerpt = \Illuminate\Support\Str::of($matnPlain)->limit(155);
@endphp

@section('title', $hadith->displayReference($locale).' — '.$collectionTitle)
@section('meta_description', $excerpt)

@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CreativeWork',
    'name' => $hadith->canonical_reference,
    'text' => $matnPlain,
    'inLanguage' => 'ar',
    'isPartOf' => ['@type' => 'Book', 'name' => $collectionTitle],
    'url' => url()->current(),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
{{-- JSON_UNESCAPED_SLASHES removed deliberately: inside a <script> block it strips the
     one default escape that stops a `</script>` inside the payload from terminating the
     block early. `text` here is hadith text imported from sunnah.com — third-party
     content in an unescaped context. JSON_HEX_* is what public/genealogy/index.blade.php
     already uses for the same reason. --}}
</script>
@endpush

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    <section class="bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-white/70" aria-label="breadcrumb">
                <a href="{{ route('public.hadith') }}" class="hover:text-white transition-colors">{{ __('hadith.collections') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                <a href="{{ route('public.hadith.collection', $collection->slug) }}" class="hover:text-white transition-colors">{{ $collectionTitle }}</a>
                @if ($hadith->book)
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                    <a href="{{ route('public.hadith.book', [$collection->slug, $hadith->book->book_number]) }}" class="hover:text-white transition-colors truncate max-w-[14rem]">
                        {{ $hadith->book->title_ar ?: $hadith->book->title_en }}
                    </a>
                @endif
            </nav>
        </div>
    </section>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12" x-data="{ copied: false }">

        <article class="rounded-3xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] shadow-sm overflow-hidden">

            <div class="px-6 sm:px-8 py-5 border-b border-gray-100 dark:border-white/5 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
                    <i data-lucide="hash" class="w-3.5 h-3.5"></i>{{ __('hadith.hadith_number_label', ['number' => $locale === 'ar' ? arabic_digits($hadith->number) : $hadith->number]) }}
                </span>

                @if ($grade)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $gradeStyles[$grade] ?? '' }}">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>{{ __('hadith.grade_'.$grade) }}
                    </span>
                @else
                    {{-- The importer stores NULL rather than guessing when the source
                         grading is ambiguous; say so instead of implying authenticity. --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400"
                          title="{{ __('hadith.no_per_hadith_grading_at_source') }}">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>{{ __('hadith.grade_unknown') }}
                    </span>
                @endif

                {{-- Raw source grade is English evidence text; only worth showing
                     when the mapper refused to classify (grade is NULL). --}}
                @if (! $grade && $hadith->raw_grade)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                        {{ __('hadith.raw_grade') }}: {{ $hadith->raw_grade }}
                    </span>
                @endif
            </div>

            @if ($hadith->chapter)
                <div class="px-6 sm:px-8 pt-6">
                    <p class="text-sm font-bold text-[#0056b3] dark:text-[#64CCC5] flex items-center gap-2">
                        <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                        {{ $hadith->chapter->title_ar ?: $hadith->chapter->title_en }}
                    </p>
                </div>
            @endif

            {{-- Arabic matn --}}
            <div class="px-6 sm:px-8 py-7">
                <p dir="rtl" class="font-arabic text-[21px] sm:text-[25px] leading-[2.3] text-gray-900 dark:text-gray-100">
                    {!! $matnHtml !!}
                </p>
            </div>

            {{-- Translation --}}
            @if ($hadith->text_en)
                <div class="px-6 sm:px-8 pb-7">
                    <details class="rounded-2xl bg-gray-50 dark:bg-white/[.03] overflow-hidden" {{ $locale === 'ar' ? '' : 'open' }}>
                        <summary class="cursor-pointer list-none px-5 py-3.5 flex items-center justify-between gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center gap-2"><i data-lucide="languages" class="w-4 h-4"></i>{{ __('hadith.translation') }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                        </summary>
                        <p dir="ltr" class="px-5 pb-5 text-[15px] leading-relaxed text-gray-600 dark:text-gray-400 text-left">
                            {{ $hadith->text_en }}
                        </p>
                    </details>
                </div>
            @endif

            {{-- References + actions --}}
            <div class="px-6 sm:px-8 py-5 border-t border-gray-100 dark:border-white/5 bg-gray-50/60 dark:bg-white/[.02]">
                <dl class="grid sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <dt class="text-gray-400 dark:text-gray-500 mb-0.5">{{ __('hadith.reference_canonical_label') }}</dt>
                        <dd class="font-bold text-gray-900 dark:text-white">{{ $hadith->displayReference($locale) }}</dd>
                    </div>
                    @if ($hadith->in_book_reference)
                        <div>
                            <dt class="text-gray-400 dark:text-gray-500 mb-0.5">{{ __('hadith.reference_in_book_label') }}</dt>
                            <dd class="font-bold text-gray-900 dark:text-white">{{ $hadith->displayInBookReference($locale) }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.matn.innerText.trim() + '\n\n' + @js($hadith->displayReference($locale))).then(() => { copied = true; setTimeout(() => copied = false, 1800) })"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:border-[#64CCC5] transition-colors">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span x-text="copied ? @js(__('hadith.copied')) : @js(__('hadith.copy'))"></span>
                    </button>
                    <span x-ref="matn" class="hidden">{{ $matnPlain }}</span>
                </div>
            </div>
        </article>

        {{-- Prev / next within the collection --}}
        <nav class="mt-6 grid sm:grid-cols-2 gap-3">
            @if ($neighbours['prev'])
                <a href="{{ route('public.hadith.show', $collection->slug.':'.$neighbours['prev']->reference_key) }}"
                   class="group flex items-center gap-3 rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] p-4 hover:border-[#64CCC5]/50 transition-all">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5 text-gray-400 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors"></i>
                    <span class="min-w-0">
                        <span class="block text-[11px] text-gray-400 dark:text-gray-500">{{ __('hadith.previous') }}</span>
                        <span class="block text-sm font-bold text-gray-900 dark:text-white tabular-nums">{{ __('hadith.hadith') }} {{ $neighbours['prev']->number }}</span>
                    </span>
                </a>
            @else
                <span class="hidden sm:block"></span>
            @endif

            @if ($neighbours['next'])
                <a href="{{ route('public.hadith.show', $collection->slug.':'.$neighbours['next']->reference_key) }}"
                   class="group flex items-center justify-end gap-3 rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] p-4 hover:border-[#64CCC5]/50 transition-all text-end">
                    <span class="min-w-0">
                        <span class="block text-[11px] text-gray-400 dark:text-gray-500">{{ __('hadith.next') }}</span>
                        <span class="block text-sm font-bold text-gray-900 dark:text-white tabular-nums">{{ __('hadith.hadith') }} {{ $neighbours['next']->number }}</span>
                    </span>
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5 text-gray-400 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors"></i>
                </a>
            @endif
        </nav>
    </main>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endpush
