{{-- Table Skeleton Loading Component --}}
{{-- Usage: @include('admin.components.skeleton-table', ['rows' => 5]) --}}

@php
    $rows = $rows ?? 5;
@endphp

<div class="overflow-hidden rounded-xl shadow-sm" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
    <!-- Table Header Skeleton -->
    <div class="border-b" :class="darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
        <div class="grid grid-cols-4 gap-4 p-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="h-4 rounded animate-shimmer"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                     style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                </div>
            @endfor
        </div>
    </div>

    <!-- Table Body Skeleton -->
    <div class="divide-y" :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'">
        @for ($row = 0; $row < $rows; $row++)
            <div class="grid grid-cols-4 gap-4 p-4 animate-pulse" style="animation-delay: {{ $row * 100 }}ms;">
                @for ($col = 0; $col < 4; $col++)
                    @php
                        $widthClass = ['w-3/4', 'w-2/3', 'w-full', 'w-1/2'][rand(0, 3)];
                    @endphp
                    <div class="space-y-2">
                        <div class="{{ $widthClass }} h-4 rounded animate-shimmer"
                             :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                             style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                        </div>
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</div>

<style>
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    .animate-shimmer {
        animation: shimmer 2s infinite linear;
    }

    /* Dark mode shimmer effect */
    .dark .animate-shimmer {
        background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.1) 40px, currentColor 80px) !important;
    }

    /* Pulse animation for rows */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
