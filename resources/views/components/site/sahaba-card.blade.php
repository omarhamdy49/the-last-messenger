{{--
    Sahaba card component — used in the home carousel and future sahaba listing pages.

    Props:
        $sahabi  — Sahabi model instance with `translations` relation loaded
        $color   — Tailwind gradient pair e.g. 'from-[#6f42c1] to-[#5a32a3]'
        $badge   — short honorific text e.g. 'الخليفة الأول'
        $badgeIcon  — lucide icon name e.g. 'star'
        $badgeColor — gradient for badge circle e.g. 'from-yellow-400 to-yellow-600'
        $accentColor — Tailwind text colour for the "read biography" link e.g. 'text-[#6f42c1]'
--}}
@php
    $locale   = app()->getLocale();
    $name     = $sahabi->getTranslation('name', $locale) ?: $sahabi->getTranslation('name', 'ar');
    $bio      = $sahabi->getTranslation('biography', $locale) ?: $sahabi->getTranslation('biography', 'ar');
    $laqab    = $sahabi->getTranslation('laqab', $locale) ?: $sahabi->getTranslation('laqab', 'ar');
    $kunya    = $sahabi->getTranslation('kunya', $locale) ?: $sahabi->getTranslation('kunya', 'ar');
    $honor    = $badge ?? ($laqab ?: $kunya ?: '');
    $cardColor = $color ?? 'from-[#6f42c1] to-[#5a32a3]';
    $icon     = $badgeIcon ?? 'star';
    $ringColor = $badgeColor ?? 'from-yellow-400 to-yellow-600';
    $accent   = $accentColor ?? 'text-[#6f42c1]';
    $avatarIcon = $sahabi->gender === 'female' ? 'circle-user-round' : 'user-round';
    $href     = Route::has('public.sahaba.show')
        ? route('public.sahaba.show', $sahabi->slug)
        : '#';
    $isRtl    = in_array($locale, ['ar', 'ur'], true);
@endphp

<div class="flex-shrink-0 w-72 sahaba-card">
    <a href="{{ $href }}"
       class="group block bg-gradient-to-br from-[#F8F9FA] to-white dark:from-[#16213e] dark:to-[#1a1a2e] rounded-2xl shadow-xl hover:shadow-2xl overflow-hidden hover-scale transition-all duration-300 border border-transparent hover:border-[#6f42c1]/50">

        {{-- Card image / avatar area --}}
        <div class="relative h-64 overflow-hidden bg-gradient-to-br {{ $cardColor }} flex items-center justify-center">
            {{-- Islamic avatar --}}
            <div class="relative">
                <div class="w-32 h-32 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm border-4 border-white/30 group-hover:scale-110 transition-transform duration-500">
                    <i class="w-20 h-20 text-white" data-lucide="{{ $avatarIcon }}"></i>
                </div>
                {{-- Badge circle --}}
                <div class="absolute -bottom-2 {{ $isRtl ? '-right-2' : '-right-2' }} w-12 h-12 bg-gradient-to-br {{ $ringColor }} rounded-full flex items-center justify-center shadow-lg">
                    <i class="w-7 h-7 text-white" data-lucide="{{ $icon }}"></i>
                </div>
            </div>

            {{-- Decorative circles --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-4 end-4 w-16 h-16 border-2 border-white rounded-full"></div>
                <div class="absolute bottom-4 start-4 w-20 h-20 border-2 border-white rounded-full"></div>
                <div class="absolute top-1/2 start-1/4 w-12 h-12 border-2 border-white rounded-full"></div>
            </div>

            {{-- Title overlay --}}
            <div class="absolute bottom-0 start-0 end-0 p-6 text-white bg-gradient-to-t from-black/60 to-transparent">
                @if($honor)
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">{{ $honor }}</span>
                    </div>
                @endif
                <h3 class="text-2xl font-bold mb-1">{{ $name }}</h3>
                <p class="text-sm opacity-90">{{ $sahabi->gender === 'female' ? __('site.radia_allah_anha') : __('site.radi_allah_anhu') }}</p>
            </div>
        </div>

        {{-- Card body --}}
        <div class="p-5">
            @if($bio)
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">{{ $bio }}</p>
            @else
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">&nbsp;</p>
            @endif

            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2 {{ $accent }} font-semibold">
                    <span>{{ __('site.read_biography') }}</span>
                    <i class="w-4 h-4 transform group-hover:translate-x-1 transition"
                       data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                </div>
                {{-- Reading time, as in the prototype (derived from biography length) --}}
                <div class="flex items-center gap-1 text-gray-500">
                    <i class="w-4 h-4" data-lucide="book-open"></i>
                    <span>{{ max(5, (int) ceil(mb_strlen($sahabi->getTranslation('biography') ?? $bio ?? '') / 600) * 2) }} {{ __('site.minutes') }}</span>
                </div>
            </div>
        </div>
    </a>
</div>
