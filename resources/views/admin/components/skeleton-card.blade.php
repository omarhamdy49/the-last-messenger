{{-- Card Skeleton Loading Component --}}
{{-- Usage: @include('admin.components.skeleton-card') --}}

<div class="rounded-xl overflow-hidden shadow-sm animate-pulse" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
    <!-- Image Placeholder -->
    <div class="relative aspect-video overflow-hidden" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'">
        <div class="absolute inset-0 animate-shimmer"
             style="background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.2) 50%, transparent 100%); background-size: 200% 100%;">
        </div>
        <!-- Placeholder icon -->
        <div class="absolute inset-0 flex items-center justify-center" :class="darkMode ? 'text-gray-600' : 'text-gray-300'">
            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
            </svg>
        </div>
    </div>

    <!-- Content Placeholder -->
    <div class="p-5 space-y-3">
        <!-- Title -->
        <div class="space-y-2">
            <div class="h-5 rounded animate-shimmer w-3/4"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
            <div class="h-5 rounded animate-shimmer w-1/2"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <div class="h-3 rounded animate-shimmer w-full"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
            <div class="h-3 rounded animate-shimmer w-5/6"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
            <div class="h-3 rounded animate-shimmer w-4/6"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
        </div>

        <!-- Footer Metadata -->
        <div class="flex items-center justify-between pt-3 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="flex items-center gap-2">
                <!-- Avatar -->
                <div class="w-8 h-8 rounded-full animate-shimmer"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                     style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                </div>
                <!-- Name & Date -->
                <div class="space-y-1">
                    <div class="h-3 w-20 rounded animate-shimmer"
                         :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                         style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="h-8 w-20 rounded-lg animate-shimmer"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                 style="background-image: linear-gradient(90deg, currentColor 0px, rgba(255,255,255,0.2) 40px, currentColor 80px); background-size: 1000px 100%;">
            </div>
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
            opacity: 0.8;
        }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
