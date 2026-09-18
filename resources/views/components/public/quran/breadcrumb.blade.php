{{--
    Breadcrumb Navigation Component

    Props:
    - $surah: array with surah data (nameAr)
--}}

@props(['surah'])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<nav class="mb-8 flex text-sm text-[color:var(--sx-muted)]" aria-label="{{ __('site.breadcrumb') }}">
    <ol class="inline-flex flex-wrap items-center gap-2">
        <li><a href="{{ route('public.home') }}" class="transition-colors hover:text-[color:var(--sx-accent)]">{{ __('site.home') }}</a></li>
        <li class="flex items-center gap-2">
            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
            <a href="{{ route('public.quran') }}" class="transition-colors hover:text-[color:var(--sx-accent)]">{{ __('site.quran') }}</a>
        </li>
        <li class="flex items-center gap-2">
            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
            <span class="font-bold text-[color:var(--sx-ink)]">{{ $surah['nameAr'] }}</span>
        </li>
    </ol>
</nav>
