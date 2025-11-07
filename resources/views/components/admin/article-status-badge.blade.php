{{--
/**
 * Article Status Badge Component
 *
 * Displays status badge with color-coded styling and pulse animation for scheduled items.
 * Supports Published, Draft, Scheduled, and Archived statuses.
 *
 * @props
 * - status: string (required) - Article status: 'published', 'draft', 'scheduled', 'archived'
 * - locale: string (optional) - Locale code for translation status
 * - isReady: boolean (optional) - Whether translation is complete
 * - size: string (optional) - 'sm', 'md', 'lg'
 *
 * @example
 * <x-admin.article-status-badge status="published" />
 * <x-admin.article-status-badge status="draft" locale="ar" :isReady="false" />
 */
--}}

@props([
    'status' => 'draft',
    'locale' => null,
    'isReady' => true,
    'size' => 'md'
])

@php
    // Status configuration
    $statusConfig = [
        'published' => [
            'label' => 'Published',
            'icon' => 'check-circle',
            'gradient' => 'from-[#218838] to-[#1e7e34]',
            'bgClass' => 'bg-gradient-to-r from-[#218838] to-[#1e7e34]',
            'textClass' => 'text-white',
            'pulse' => false
        ],
        'draft' => [
            'label' => 'Draft',
            'icon' => 'file-text',
            'gradient' => 'from-gray-500 to-gray-600',
            'bgClass' => 'bg-gradient-to-r from-gray-500 to-gray-600',
            'textClass' => 'text-white',
            'pulse' => false
        ],
        'scheduled' => [
            'label' => 'Scheduled',
            'icon' => 'clock',
            'gradient' => 'from-[#0056b3] to-[#004799]',
            'bgClass' => 'bg-gradient-to-r from-[#0056b3] to-[#004799]',
            'textClass' => 'text-white',
            'pulse' => true
        ],
        'archived' => [
            'label' => 'Archived',
            'icon' => 'archive',
            'gradient' => 'from-orange-500 to-orange-600',
            'bgClass' => 'bg-gradient-to-r from-orange-500 to-orange-600',
            'textClass' => 'text-white',
            'pulse' => false
        ],
        'ready' => [
            'label' => 'Ready',
            'icon' => 'check-circle',
            'gradient' => 'from-green-500 to-green-600',
            'bgClass' => 'bg-gradient-to-r from-green-500 to-green-600',
            'textClass' => 'text-white',
            'pulse' => false
        ],
        'pending' => [
            'label' => 'Pending',
            'icon' => 'clock',
            'gradient' => 'from-yellow-500 to-yellow-600',
            'bgClass' => 'bg-gradient-to-r from-yellow-500 to-yellow-600',
            'textClass' => 'text-white',
            'pulse' => false
        ]
    ];

    // Determine which status to display
    if ($locale) {
        $displayStatus = $isReady ? 'ready' : 'pending';
    } else {
        $displayStatus = $status;
    }

    $config = $statusConfig[$displayStatus] ?? $statusConfig['draft'];

    // Size classes
    $sizeClasses = [
        'sm' => [
            'container' => 'px-2 py-0.5 text-xs',
            'icon' => 'w-3 h-3'
        ],
        'md' => [
            'container' => 'px-3 py-1 text-sm',
            'icon' => 'w-4 h-4'
        ],
        'lg' => [
            'container' => 'px-4 py-1.5 text-base',
            'icon' => 'w-5 h-5'
        ]
    ];

    $sizes = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full font-semibold shadow-sm ' .
               $sizes['container'] . ' ' .
               $config['bgClass'] . ' ' .
               $config['textClass']
]) }}>

    @if($config['pulse'])
        <!-- Pulse animation for scheduled status -->
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
        </span>
    @else
        <i data-lucide="{{ $config['icon'] }}" class="{{ $sizes['icon'] }}"></i>
    @endif

    <span>{{ $config['label'] }}</span>

    @if($locale)
        <span class="opacity-75">({{ strtoupper($locale) }})</span>
    @endif
</span>

@push('scripts')
<script>
    // Reinitialize Lucide icons after component loads
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
