{{-- Stats Card Component --}}
@props(['icon', 'value', 'label', 'color' => 'blue'])
@php
$colors = [
    'blue' => 'from-blue-500 to-blue-600',
    'green' => 'from-green-500 to-green-600',
    'teal' => 'from-teal-500 to-teal-600',
    'purple' => 'from-purple-500 to-purple-600'
];
$gradient = $colors[$color] ?? $colors['blue'];
@endphp
<div {{ $attributes->merge(['class' => 'p-6 rounded-2xl shadow-xl hover-lift transition-all']) }}
     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $label }}</p>
            <p class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $value }}</p>
        </div>
        <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $gradient }} flex items-center justify-center shadow-lg">
            <i class="w-7 h-7 text-white" data-lucide="{{ $icon }}"></i>
        </div>
    </div>
</div>
