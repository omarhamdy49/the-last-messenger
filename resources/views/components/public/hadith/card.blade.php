{{--
    Hadith → editorial card.

    The matn is the card's title: a hadith carries no headline of its own, its
    text is what identifies it. Full text stays in the markup (clamped by CSS,
    with the title attribute as the tooltip), never truncated server-side.

    @props hadith, showCollection (name the collection in the meta row), variant tile|row, eager
--}}
@props(['hadith', 'showCollection' => false, 'variant' => 'row', 'eager' => false])

@php
    $locale = app()->getLocale();
    $text = $hadith->text($locale) ?: $hadith->text_ar;
    // NULL grade is deliberate: SunnahGradeMapper refuses to guess when the
    // source is ambiguous, so say "غير محدد" rather than implying sahih.
    $grade = $hadith->grade?->value ?? ($hadith->grade ?: null);
    $chapter = $hadith->chapter ? ($hadith->chapter->title_ar ?: $hadith->chapter->title_en) : null;
@endphp

<x-public.editorial.card
    :url="route('public.hadith.show', $hadith->collection?->slug ? $hadith->collection->slug.':'.$hadith->reference_key : '#')"
    :title="$text"
    icon="scroll-text"
    :badge="$grade ? __('hadith.grade_'.$grade) : __('hadith.grade_unknown')"
    :big="$locale === 'ar' ? arabic_digits($hadith->number) : $hadith->number"
    :meta="[
        ['icon' => 'library', 'text' => $showCollection ? $hadith->collection?->title($locale) : null],
        ['icon' => 'book-marked', 'text' => $chapter],
        ['icon' => 'bookmark', 'text' => $hadith->displayReference($locale)],
        ['icon' => 'circle-help', 'text' => $grade ? null : __('hadith.per_hadith_grade_not_provided')],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes->class('hadith-card') }} />

@once
@push('head')
<style>
    /* Module-unique: the matn is revealed text, so the card title is set in
       Amiri at card scale (.sx-scripture is display scale). Tokens only. */
    .hadith-card .sx-card-title {
        font-family: "Amiri", serif;
        font-size: clamp(1.05rem, 0.98rem + 0.4vw, 1.25rem);
        font-weight: 400;
        line-height: 2.05;
        color: var(--sx-ink);
        -webkit-line-clamp: 3;
    }
</style>
@endpush
@endonce
