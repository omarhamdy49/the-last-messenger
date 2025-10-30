{{-- Form Skeleton Loading Component --}}
{{-- Usage: @include('admin.components.skeleton-form', ['fields' => 4]) --}}

@php
    $fields = $fields ?? 4;
@endphp

<div class="rounded-xl shadow-sm p-6 space-y-6" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
    @for ($i = 0; $i < $fields; $i++)
        <div class="space-y-2 animate-pulse" style="animation-delay: {{ $i * 100 }}ms;">
            <!-- Label -->
            <div class="h-4 w-32 rounded animate-shimmer"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>

            <!-- Input Field -->
            @if ($i % 3 === 0)
                {{-- Textarea --}}
                <div class="h-24 rounded-lg animate-shimmer"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                     style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                </div>
            @elseif ($i % 3 === 1)
                {{-- Select/Dropdown --}}
                <div class="h-10 rounded-lg animate-shimmer flex items-center justify-between px-3"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                     style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                    <div class="h-3 w-24 rounded" :class="darkMode ? 'bg-gray-600' : 'bg-gray-300'"></div>
                    <svg class="w-4 h-4" :class="darkMode ? 'text-gray-600' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </div>
            @else
                {{-- Regular Input --}}
                <div class="h-10 rounded-lg animate-shimmer"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                     style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                </div>
            @endif
        </div>
    @endfor

    <!-- Buttons Row -->
    <div class="flex items-center gap-3 pt-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
        <!-- Submit Button -->
        <div class="h-10 w-28 rounded-lg animate-shimmer"
             style="background: linear-gradient(135deg, #0056b3 0%, #218838 100%); opacity: 0.6;">
        </div>

        <!-- Cancel Button -->
        <div class="h-10 w-24 rounded-lg animate-shimmer"
             :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
             style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
        </div>
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

    /* Pulse animation */
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
