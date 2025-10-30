{{--
    Module Card Component

    Props:
    - $icon (string): Lucide icon name
    - $title (string): Module name
    - $description (string): Module description
    - $count (int): Number of items
    - $countLabel (string): Label for count
    - $url (string): Link URL
    - $gradient (string, optional): Custom gradient classes

    Usage:
    <x-cards.module-card
        icon="book-open"
        title="السيرة النبوية"
        description="اكتشف حياة النبي محمد ﷺ"
        :count="150"
        countLabel="مقال"
        url="/seerah"
    />
--}}

@props([
    'icon',
    'title',
    'description',
    'count',
    'countLabel',
    'url',
    'gradient' => 'from-[#0056b3] to-[#218838]'
])

<a href="{{ $url }}"
   {{ $attributes->merge(['class' => 'group block p-6 rounded-2xl shadow-xl hover-lift transition-all duration-300']) }}
   :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

    {{-- Icon --}}
    <div class="flex items-start justify-between mb-4">
        <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $gradient }} flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-lg">
            <i class="w-7 h-7 text-white" data-lucide="{{ $icon }}"></i>
        </div>

        {{-- Count Badge --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gradient-to-r {{ $gradient }} text-white text-sm font-bold shadow-md">
            <span>{{ $count }}</span>
            <span class="text-xs font-normal opacity-90">{{ $countLabel }}</span>
        </div>
    </div>

    {{-- Title --}}
    <h3 class="text-xl font-bold mb-2 group-hover:text-[#64CCC5] transition-colors"
        :class="darkMode ? 'text-white' : 'text-gray-900'">
        {{ $title }}
    </h3>

    {{-- Description --}}
    <p class="text-sm mb-4 leading-relaxed"
       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        {{ $description }}
    </p>

    {{-- CTA --}}
    <div class="flex items-center gap-2 text-sm font-semibold text-[#0056b3] group-hover:gap-3 transition-all">
        <span>{{ __('site.explore') }}</span>
        <i class="w-4 h-4" data-lucide="arrow-right"></i>
    </div>

    {{-- Hover Effect Background --}}
    <div class="absolute inset-0 bg-gradient-to-r {{ $gradient }} opacity-0 group-hover:opacity-5 rounded-2xl transition-opacity duration-300 pointer-events-none"></div>
</a>
