@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $collectionTitle = $collection->title($locale);
    $grade = $hadith->grade?->value ?? ($hadith->grade ?: null);
    $chapterTitle = $hadith->chapter ? ($hadith->chapter->title_ar ?: $hadith->chapter->title_en) : null;
    $bookTitle = $hadith->book ? ($hadith->book->title_ar ?: $hadith->book->title_en) : null;
    // The matn carries Sunnah.com's [quran ...] markers. toHtml() escapes the
    // text and turns each marker into a link to that ayah in our reader;
    // strip() is the plain reading for everywhere markup would be wrong.
    $matnHtml = \App\Domain\Hadith\Support\QuranCitation::toHtml($hadith->text_ar);
    $matnPlain = \App\Domain\Hadith\Support\QuranCitation::strip($hadith->text_ar);
    $excerpt = \Illuminate\Support\Str::of($matnPlain)->limit(155);

    $references = [
        ['key' => __('hadith.reference_canonical_label'), 'value' => $hadith->displayReference($locale)],
    ];
    if ($hadith->in_book_reference) {
        $references[] = ['key' => __('hadith.reference_in_book_label'), 'value' => $hadith->displayInBookReference($locale)];
    }

    $neighbourLabel = fn ($neighbour) => $neighbour === null ? null : [
        'url' => route('public.hadith.show', $collection->slug.':'.$neighbour->reference_key),
        'title' => __('hadith.hadith').' '.$neighbour->number,
    ];
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
<div class="sx min-h-[100dvh] pb-20">

    <x-public.editorial.hero variant="reading"
        :title="$hadith->displayReference($locale)" :lead="$chapterTitle">
        <x-slot:top>
            <nav class="flex flex-wrap items-center gap-2 pb-4" aria-label="breadcrumb">
                <a href="{{ route('public.hadith') }}" class="sx-glass-chip">
                    <i data-lucide="library-big" aria-hidden="true"></i>{{ __('hadith.collections') }}
                </a>
                <a href="{{ route('public.hadith.collection', $collection->slug) }}" class="sx-glass-chip sx-clamp-1 max-w-[16rem]" title="{{ $collectionTitle }}">
                    <i data-lucide="library" aria-hidden="true"></i>{{ $collectionTitle }}
                </a>
                @if ($bookTitle)
                    <a href="{{ route('public.hadith.book', [$collection->slug, $hadith->book->book_number]) }}"
                       class="sx-glass-chip sx-clamp-1 max-w-[16rem]" title="{{ $bookTitle }}">
                        <i data-lucide="book" aria-hidden="true"></i>{{ $bookTitle }}
                    </a>
                @endif
            </nav>
        </x-slot:top>

        <x-slot:chips>
            <span class="sx-glass-chip">
                <i data-lucide="hash" aria-hidden="true"></i>{{ __('hadith.hadith_number_label', ['number' => $locale === 'ar' ? arabic_digits($hadith->number) : $hadith->number]) }}
            </span>
            @if ($grade)
                <span class="sx-glass-chip"><i data-lucide="shield-check" aria-hidden="true"></i>{{ __('hadith.grade_'.$grade) }}</span>
            @else
                {{-- The importer stores NULL rather than guessing when the source
                     grading is ambiguous; say so instead of implying authenticity. --}}
                <span class="sx-glass-chip" title="{{ __('hadith.no_per_hadith_grading_at_source') }}">
                    <i data-lucide="circle-help" aria-hidden="true"></i>{{ __('hadith.grade_unknown') }}
                </span>
            @endif
            {{-- Raw source grade is English evidence text; only worth showing
                 when the mapper refused to classify (grade is NULL). --}}
            @if (! $grade && $hadith->raw_grade)
                <span class="sx-glass-chip sx-clamp-1 max-w-[20rem]" title="{{ $hadith->raw_grade }}">
                    {{ __('hadith.raw_grade') }}: {{ $hadith->raw_grade }}
                </span>
            @endif
        </x-slot:chips>
    </x-public.editorial.hero>

    <div class="sx-wrap grid gap-8 py-10 sm:py-14" x-data="{ copied: false }">

        <article class="sx-surface sx-article grid min-w-0 gap-8">
            {{-- Arabic matn --}}
            <p dir="rtl" class="sx-scripture">{!! $matnHtml !!}</p>

            {{-- Translation --}}
            @if ($hadith->text_en)
                <details class="sx-disclosure" {{ $locale === 'ar' ? '' : 'open' }}>
                    <summary>
                        <span class="inline-flex items-center gap-2"><i data-lucide="languages" aria-hidden="true"></i>{{ __('hadith.translation') }}</span>
                        <i data-lucide="chevron-down" class="sx-disclosure-chevron" aria-hidden="true"></i>
                    </summary>
                    <p dir="ltr" class="sx-prose px-2 pb-4 pt-1 text-start !text-base">{{ $hadith->text_en }}</p>
                </details>
            @endif

            {{-- References --}}
            <dl class="sx-kv">
                @foreach ($references as $reference)
                    <div><dt>{{ $reference['key'] }}</dt><dd>{{ $reference['value'] }}</dd></div>
                @endforeach
            </dl>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="sx-btn"
                        @click="navigator.clipboard.writeText($refs.matn.innerText.trim() + '\n\n' + @js($hadith->displayReference($locale))).then(() => { copied = true; setTimeout(() => copied = false, 1800) })">
                    <i data-lucide="copy" aria-hidden="true"></i>
                    <span x-text="copied ? @js(__('hadith.copied')) : @js(__('hadith.copy'))">{{ __('hadith.copy') }}</span>
                </button>
                <span x-ref="matn" class="hidden">{{ $matnPlain }}</span>
            </div>
        </article>

        {{-- Prev / next within the collection --}}
        <x-public.editorial.pager
            :prev="$neighbourLabel($neighbours['prev'])" :next="$neighbourLabel($neighbours['next'])"
            :prev-label="__('hadith.previous')" :next-label="__('hadith.next')" :label="__('hadith.hadiths')" />
    </div>
</div>
@endsection
