@props(['icon' => 'inbox', 'title' => null, 'message' => null, 'showReset' => false])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-16 px-4']) }}>
    <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#0f3460] flex items-center justify-center text-gray-400 mb-4">
        <i data-lucide="{{ $icon }}" class="w-8 h-8"></i>
    </div>
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">{{ $title ?? __('site.no_results') }}</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mb-5">{{ $message ?? __('site.try_adjusting_filters') }}</p>
    @if ($showReset)
        <button type="button" {{ $attributes->only('@click') }}
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-primary border border-primary/30 hover:bg-primary/5 transition-colors">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            {{ __('site.reset_filters') }}
        </button>
    @endif
    {{ $slot }}
</div>
