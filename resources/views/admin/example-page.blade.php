@extends('layouts.admin')

@php
    // Example breadcrumbs data
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Content', 'url' => route('admin.articles.index')],
        ['label' => 'Example Page', 'url' => '#']
    ];

    $pageTitle = 'Example Page';
@endphp

@section('page-header')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">Example Page</h1>
            <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                This is an example page demonstrating the Netflix-grade RTL admin layout.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded-xl font-semibold transition-all hover:scale-105"
                    :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800' : 'bg-white hover:bg-gray-50 border border-gray-200'">
                <i data-lucide="settings" class="w-4 h-4 inline me-2"></i>
                Settings
            </button>
            <button class="px-4 py-2 rounded-xl font-semibold bg-gradient-primary text-white shadow-glow hover:shadow-xl transition-all hover:scale-105">
                <i data-lucide="plus" class="w-4 h-4 inline me-2"></i>
                Create New
            </button>
        </div>
    </div>
@endsection

@section('content')

    <!-- Feature Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

        <!-- Card 1: RTL Support -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="languages" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                RTL-First Design
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Full RTL support for Arabic and Urdu with logical properties (ms-*, me-*) for proper text direction.
            </p>
        </div>

        <!-- Card 2: Dark Mode -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'"
             style="animation-delay: 0.1s;">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="moon" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Dark/Light Mode
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Toggle between dark and light themes with persistent state using localStorage.
            </p>
        </div>

        <!-- Card 3: Glassmorphism -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'"
             style="animation-delay: 0.2s;">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="sparkles" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Glassmorphism UI
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Modern glass effect with backdrop blur for a premium, Netflix-inspired aesthetic.
            </p>
        </div>

        <!-- Card 4: Responsive -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'"
             style="animation-delay: 0.3s;">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="smartphone" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Fully Responsive
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Mobile-first responsive design with hamburger menu and smooth transitions.
            </p>
        </div>

        <!-- Card 5: Alpine.js -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'"
             style="animation-delay: 0.4s;">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="zap" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Alpine.js Powered
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Lightweight interactivity with Alpine.js for state management and animations.
            </p>
        </div>

        <!-- Card 6: Blue-Green Theme -->
        <div class="glass rounded-2xl p-6 transition-all hover:-translate-y-1 hover:shadow-xl animate-fadeInUp"
             :class="darkMode ? 'glass-dark' : 'glass-light'"
             style="animation-delay: 0.5s;">
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center mb-4 shadow-glow">
                <i data-lucide="palette" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Blue-Green Gradient
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Consistent blue (#0056b3) to green (#218838) gradient with teal accent (#64CCC5).
            </p>
        </div>

    </div>

    <!-- Code Examples -->
    <div class="glass rounded-2xl overflow-hidden animate-fadeInUp"
         :class="darkMode ? 'glass-dark' : 'glass-light'"
         style="animation-delay: 0.6s;">

        <div class="px-6 py-4 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Key Features Implemented
            </h2>
        </div>

        <div class="p-6">
            <div class="space-y-4">

                <!-- Feature 1 -->
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            RTL-Optimized Sidebar
                        </h4>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Sidebar automatically positions on the right side for Arabic/Urdu and left side for LTR languages.
                            Includes smooth slide-in animations and responsive collapse functionality.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Glassmorphism Header
                        </h4>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Sticky header with backdrop blur, breadcrumbs, search functionality, language switcher,
                            notifications, and user profile dropdown with glass effect.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Multi-Language Support
                        </h4>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Built-in language switcher with support for Arabic, English, Turkish, French, and Urdu.
                            Automatically switches between RTL and LTR layouts.
                        </p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Smooth Animations
                        </h4>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            fadeInUp, slideInRight, and shimmer animations with staggered delays for professional loading effects.
                            All animations use hardware-accelerated CSS transforms.
                        </p>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-5 h-5 text-green-500"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Navigation Menu
                        </h4>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Comprehensive sidebar navigation with expandable submenus, active state highlighting with teal accent,
                            and icons for all menu items using Lucide icons.
                        </p>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    // Example page specific JavaScript
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Example page loaded successfully!');
        console.log('Dark mode:', Alpine.store('darkMode') ? 'enabled' : 'disabled');
        console.log('Current language:', document.documentElement.lang);
        console.log('Text direction:', document.documentElement.dir);
    });
</script>
@endpush
