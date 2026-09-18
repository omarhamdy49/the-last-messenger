{{-- Bordered content panel with an optional heading. --}}
@props(['title' => null, 'icon' => null])
<section {{ $attributes->merge(['class' => 'mb-6 rounded-2xl border p-6']) }}
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    @if ($title)
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            @if ($icon)<i data-lucide="{{ $icon }}" class="w-5 h-5 text-accent"></i>@endif
            {{ $title }}
        </h2>
    @endif
    {{ $slot }}
</section>
