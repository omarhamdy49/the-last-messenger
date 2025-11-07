@extends('layouts.admin')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center">
    <div class="text-center max-w-2xl mx-auto px-6" x-data="{ darkMode: $store.admin.darkMode }">

        <!-- Icon with Animation -->
        <div class="mb-8 inline-block animate-float">
            <div class="w-32 h-32 mx-auto rounded-full flex items-center justify-center"
                 :class="darkMode ? 'bg-gradient-to-br from-brand/20 to-secondary/20' : 'bg-gradient-to-br from-brand/10 to-secondary/10'">
                <i data-lucide="construction"
                   class="w-16 h-16 text-gradient"></i>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-4xl md:text-5xl font-bold mb-4 gradient-text">
            {{ $module ?? 'Module' }} Coming Soon
        </h1>

        <!-- Description -->
        <p class="text-lg mb-8"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            We're working hard to bring you this feature. Stay tuned for updates!
        </p>

        <!-- Progress Indicator -->
        <div class="max-w-md mx-auto mb-8">
            <div class="h-2 rounded-full overflow-hidden"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                <div class="h-full bg-gradient-to-r from-brand to-secondary rounded-full animate-shimmer"
                     style="width: 45%"></div>
            </div>
            <p class="text-sm mt-2"
               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                Development Progress: 45%
            </p>
        </div>

        <!-- Features Preview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="p-6 rounded-xl glass"
                 :class="darkMode ? 'glass-dark' : 'bg-white/50'">
                <i data-lucide="check-circle" class="w-8 h-8 mb-3 text-brand mx-auto"></i>
                <h3 class="font-semibold mb-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Feature-Rich
                </h3>
                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Packed with powerful tools
                </p>
            </div>

            <div class="p-6 rounded-xl glass"
                 :class="darkMode ? 'glass-dark' : 'bg-white/50'">
                <i data-lucide="zap" class="w-8 h-8 mb-3 text-secondary mx-auto"></i>
                <h3 class="font-semibold mb-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Fast & Efficient
                </h3>
                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Optimized performance
                </p>
            </div>

            <div class="p-6 rounded-xl glass"
                 :class="darkMode ? 'glass-dark' : 'bg-white/50'">
                <i data-lucide="shield-check" class="w-8 h-8 mb-3 text-accent mx-auto"></i>
                <h3 class="font-semibold mb-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Secure
                </h3>
                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Enterprise-grade security
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-brand to-secondary hover:scale-105 transition-transform">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                Back to Dashboard
            </a>

            <button onclick="alert('Updates will be sent to your email!')"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold transition-colors"
                    :class="darkMode ?
                        'bg-gray-800 text-white hover:bg-gray-700' :
                        'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                <i data-lucide="bell" class="w-5 h-5"></i>
                Notify Me
            </button>
        </div>

        <!-- Estimated Launch -->
        <div class="mt-12 p-4 rounded-xl inline-block"
             :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-100'">
            <p class="text-sm"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <i data-lucide="calendar" class="w-4 h-4 inline me-2"></i>
                <strong>Estimated Launch:</strong> Coming in next update
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Reinitialize Lucide icons after Alpine renders
    document.addEventListener('alpine:initialized', () => {
        lucide.createIcons();
    });
</script>
@endpush
@endsection
