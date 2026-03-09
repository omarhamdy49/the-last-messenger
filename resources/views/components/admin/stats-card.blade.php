{{--
/**
 * Stats Card Component
 *
 * @props
 * - icon: string (required) - Lucide icon name
 * - title: string (required) - Card label
 * - value: string|number (required) - Statistic value
 * - change: string (optional) - e.g. '+12%'
 * - changeType: 'up'|'down'
 * - gradient: boolean - Use gradient background
 * - compact: boolean - Compact horizontal layout (smaller, no animation delay)
 * - delay: string (optional) - Animation delay class
 * - href: string (optional) - Make card clickable
 */
--}}

@props([
    'icon'       => 'activity',
    'title'      => 'Statistic',
    'value'      => '0',
    'change'     => null,
    'changeType' => 'up',
    'gradient'   => false,
    'compact'    => false,
    'delay'      => '',
    'href'       => null
])

@php
    $isClickable     = !empty($href);
    $changeColorClass = $changeType === 'up' ? 'text-green-500' : 'text-red-500';

    if ($compact) {
        $baseClasses = 'flex items-center gap-3 rounded-xl p-3 shadow-md transition-all duration-300 group';
    } else {
        $baseClasses = 'block rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group';
    }
@endphp

<div class="{{ $compact ? '' : 'animate-fadeInUp ' . $delay }}"
     x-data="{
         countValue: 0,
         targetValue: {{ is_numeric($value) ? $value : 0 }},
         animateCounter() {
             if (this.targetValue === 0) return;
             const duration = {{ $compact ? 800 : 1500 }};
             const steps = 40;
             const stepValue = this.targetValue / steps;
             const stepDuration = duration / steps;
             let currentStep = 0;
             const interval = setInterval(() => {
                 currentStep++;
                 this.countValue = Math.min(Math.round(stepValue * currentStep), this.targetValue);
                 if (currentStep >= steps) { clearInterval(interval); this.countValue = this.targetValue; }
             }, stepDuration);
         }
     }"
     x-init="setTimeout(() => animateCounter(), {{ $compact ? 50 : ($delay ? '300' : '100') }})">

    @if($isClickable)
        <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses]) }}
    @else
        <div {{ $attributes->merge(['class' => $baseClasses]) }}
    @endif
           :class="darkMode ?
               '{{ $gradient ? 'bg-gradient-to-br from-brand to-secondary text-white' : 'bg-gray-900 border border-gray-800' }}' :
               '{{ $gradient ? 'bg-gradient-to-br from-brand to-secondary text-white' : 'bg-white border border-gray-200' }}'">

        @if($compact)
            {{-- COMPACT: horizontal layout --}}
            <div class="p-2 rounded-lg flex-shrink-0 {{ $gradient ? 'bg-white/20' : 'bg-gradient-to-br from-brand to-secondary' }}">
                <i data-lucide="{{ $icon }}" class="w-4 h-4 text-white"></i>
            </div>
            <div class="min-w-0 flex-1">
                @if(is_numeric($value))
                    <p class="text-xl font-bold leading-none {{ $gradient ? 'text-white' : 'gradient-text' }}"
                       x-text="countValue.toLocaleString()">0</p>
                @else
                    <p class="text-xl font-bold leading-none {{ $gradient ? 'text-white' : 'gradient-text' }}">{{ $value }}</p>
                @endif
                <p class="text-xs font-medium mt-0.5 truncate {{ $gradient ? 'text-white/80' : '' }}"
                   :class="!{{ $gradient ? 'true' : 'false' }} && (darkMode ? 'text-gray-400' : 'text-gray-500')">
                    {{ $title }}
                </p>
            </div>
            @if($change)
                <span class="flex-shrink-0 text-xs font-bold px-1.5 py-0.5 rounded-full
                             {{ $gradient ? 'bg-white/20 text-white' : $changeColorClass }}">
                    {{ $change }}
                </span>
            @endif

        @else
            {{-- DEFAULT: full card layout --}}
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl {{ $gradient ? 'bg-white/20' : 'bg-gradient-to-br from-brand to-secondary' }}
                            group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="{{ $icon }}" class="w-6 h-6 text-white"></i>
                </div>

                @if($change)
                    <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                {{ $gradient ? 'bg-white/20 text-white' : $changeColorClass . ' bg-current/10' }}">
                        <i data-lucide="{{ $changeType === 'up' ? 'trending-up' : 'trending-down' }}" class="w-3 h-3"></i>
                        <span>{{ $change }}</span>
                    </div>
                @endif
            </div>

            <div class="mb-2">
                @if(is_numeric($value))
                    <h3 class="text-3xl font-bold {{ $gradient ? 'text-white' : 'gradient-text' }}"
                        x-text="countValue.toLocaleString()">0</h3>
                @else
                    <h3 class="text-3xl font-bold {{ $gradient ? 'text-white' : 'gradient-text' }}">{{ $value }}</h3>
                @endif
            </div>

            <p class="text-sm font-medium {{ $gradient ? 'text-white/80' : '' }}"
               :class="!{{ $gradient ? 'true' : 'false' }} && (darkMode ? 'text-gray-300' : 'text-gray-600')">
                {{ $title }}
            </p>

            @if($isClickable)
                <div class="mt-4 pt-4 border-t {{ $gradient ? 'border-white/20' : '' }}"
                     :class="!{{ $gradient ? 'true' : 'false' }} && (darkMode ? 'border-gray-800' : 'border-gray-200')">
                    <div class="flex items-center gap-2 text-sm font-semibold group-hover:gap-3 transition-all
                                {{ $gradient ? 'text-white' : 'text-brand' }}">
                        <span>{{ __('admin.view_details') }}</span>
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                    </div>
                </div>
            @endif
        @endif

    @if($isClickable)
        </a>
    @else
        </div>
    @endif
</div>
