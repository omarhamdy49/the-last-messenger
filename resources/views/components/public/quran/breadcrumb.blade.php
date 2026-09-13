{{--
    Breadcrumb Navigation Component

    Props:
    - $surah: array with surah data (nameAr)
--}}

@props(['surah'])

<nav class="flex mb-8 text-sm animate-fadeIn" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
     aria-label="{{ __('site.breadcrumb') }}">
    <ol class="inline-flex items-center gap-2 flex-wrap">
        <li><a href="{{ route('public.home') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.home') }}</a></li>
        <li class="flex items-center gap-2">
            <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <a href="{{ route('public.quran') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.quran') }}</a>
        </li>
        <li class="flex items-center gap-2">
            <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="font-medium" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                {{ $surah['nameAr'] }}
            </span>
        </li>
    </ol>
</nav>
