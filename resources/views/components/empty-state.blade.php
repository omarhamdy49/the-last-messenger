{{-- Empty State Component --}}
@props(['icon', 'title', 'description', 'actionText' => null, 'actionUrl' => null, 'action' => null])

@php
    // Handle both old format (actionText/actionUrl) and new format (action array)
    if (is_array($action)) {
        $actionLabel = $action['label'] ?? $actionText;
        $actionIcon = $action['icon'] ?? 'arrow-right';
        $actionOnclick = $action['onclick'] ?? null;
        $actionHref = $action['url'] ?? $actionUrl ?? '#';
    } else {
        $actionLabel = $actionText;
        $actionIcon = 'arrow-right';
        $actionOnclick = null;
        $actionHref = $actionUrl ?? '#';
    }
@endphp

<div {{ $attributes->merge(['class' => 'text-center py-16']) }}>
    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] opacity-20 flex items-center justify-center mx-auto mb-6">
        <i class="w-10 h-10" data-lucide="{{ $icon }}"></i>
    </div>
    <h3 class="text-2xl font-bold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $title }}</h3>
    <p class="text-base mb-6 max-w-md mx-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $description }}</p>

    @if($actionLabel)
        @if($actionOnclick)
            <button @click="{{ $actionOnclick }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold rounded-full shadow-lg hover:scale-105 transition-transform">
                <span>{{ $actionLabel }}</span>
                <i class="w-5 h-5" data-lucide="{{ $actionIcon }}"></i>
            </button>
        @else
            <a href="{{ $actionHref }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold rounded-full shadow-lg hover:scale-105 transition-transform">
                <span>{{ $actionLabel }}</span>
                <i class="w-5 h-5" data-lucide="{{ $actionIcon }}"></i>
            </a>
        @endif
    @endif
</div>
