@props(['hadith', 'showCollection' => false])

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $text = $hadith->text($locale) ?: $hadith->text_ar;
    $grade = $hadith->grade?->value ?? ($hadith->grade ?: null);
    // NULL grade is deliberate: SunnahGradeMapper refuses to guess when the
    // source is ambiguous, so render "غير مصنّف" rather than implying sahih.
    $gradeStyles = [
        'sahih' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'hasan' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'daif'  => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'mawdu' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    ];
@endphp

<article class="group relative rounded-2xl border border-gray-100 dark:border-white/5 bg-white dark:bg-white/[.03] p-5 sm:p-6 hover:border-[#64CCC5]/50 hover:shadow-lg transition-all">
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="hash" class="w-3 h-3"></i>{{ $locale === 'ar' ? arabic_digits($hadith->number) : $hadith->number }}
        </span>

        @if ($showCollection && $hadith->collection)
            <a href="{{ route('public.hadith.collection', $hadith->collection->slug) }}"
               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 hover:text-[#0056b3] dark:hover:text-[#64CCC5] transition-colors">
                <i data-lucide="library" class="w-3 h-3"></i>{{ $hadith->collection->title($locale) }}
            </a>
        @endif

        @if ($grade)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold {{ $gradeStyles[$grade] ?? '' }}">
                <i data-lucide="shield-check" class="w-3 h-3"></i>{{ __('hadith.grade_'.$grade) }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400"
                  title="{{ __('hadith.no_per_hadith_grading_at_source') }}">
                <i data-lucide="help-circle" class="w-3 h-3"></i>{{ __('hadith.grade_unknown') }}
            </span>
        @endif
    </div>

    @if ($hadith->chapter)
        <p class="mb-3 text-xs font-semibold text-[#0056b3] dark:text-[#64CCC5]">
            {{ $hadith->chapter->title_ar ?: $hadith->chapter->title_en }}
        </p>
    @endif

    <a href="{{ route('public.hadith.show', $hadith->collection?->slug ? $hadith->collection->slug.':'.$hadith->reference_key : '#') }}" class="block">
        <p dir="rtl" class="font-arabic text-[17px] sm:text-[19px] leading-[2.1] text-gray-900 dark:text-gray-100 line-clamp-4 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">
            {{ $text }}
        </p>
    </a>

    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5 flex flex-wrap items-center justify-between gap-3">
        <span class="text-[11px] text-gray-400 dark:text-gray-500 font-medium">
            {{ $hadith->displayReference($locale) }}
        </span>
        <a href="{{ route('public.hadith.show', $hadith->collection?->slug ? $hadith->collection->slug.':'.$hadith->reference_key : '#') }}"
           class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0056b3] dark:text-[#64CCC5] hover:gap-2.5 transition-all">
            {{ __('hadith.read_hadith') }}
            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
        </a>
    </div>
</article>
