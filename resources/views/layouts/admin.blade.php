<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') - خاتَمُ المُرْسَلِين</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- x-cloak CSS for Alpine.js -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            primary: '#0056b3',
                            secondary: '#218838',
                            accent: '#64CCC5',
                        }
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' }
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-1000px 0' },
                            '100%': { backgroundPosition: '1000px 0' }
                        },
                        pulseGlow: {
                            '0%, 100%': { boxShadow: '0 0 20px rgba(0, 86, 179, 0.3)' },
                            '50%': { boxShadow: '0 0 30px rgba(33, 136, 56, 0.5)' }
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'shimmer': 'shimmer 2s infinite',
                        'pulse-glow': 'pulseGlow 2s ease-in-out infinite'
                    }
                }
            }
        }
    </script>

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        /* ===== GLASS MORPHISM ===== */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Premium Glass for Light Mode */
        .glass-light {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 86, 179, 0.1);
            box-shadow: 0 8px 32px rgba(0, 86, 179, 0.05);
        }

        .glass-light-soft {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(33, 136, 56, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .glass-light-strong {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 204, 197, 0.15);
            box-shadow: 0 10px 40px rgba(0, 86, 179, 0.08);
        }

        /* ===== GRADIENTS ===== */
        .gradient-primary {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-light {
            background: linear-gradient(135deg, rgba(0, 86, 179, 0.15) 0%, rgba(33, 136, 56, 0.15) 100%);
        }

        /* ===== SIDEBAR ===== */
        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-item:hover {
            transform: translateX(-5px);
        }

        /* Light Mode Sidebar Item */
        .light .sidebar-item:hover {
            background: linear-gradient(135deg, rgba(0, 86, 179, 0.08) 0%, rgba(33, 136, 56, 0.08) 100%);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.05);
        }

        .sidebar-item.active {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
        }

        /* ===== CARDS ===== */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Light Mode Card Hover */
        .light .card-hover:hover {
            box-shadow: 0 15px 35px rgba(0, 86, 179, 0.12);
        }

        .card-light {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 86, 179, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .card-light:hover {
            border-color: rgba(0, 86, 179, 0.2);
            box-shadow: 0 8px 32px rgba(0, 86, 179, 0.1);
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        /* Light Mode Scrollbar */
        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 86, 179, 0.05);
            border-radius: 10px;
        }

        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            border-radius: 10px;
            opacity: 0.6;
        }

        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            opacity: 0.8;
            background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
        }

        /* Dark Mode Scrollbar */
        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
        }

        /* ===== RTL SPECIFIC ===== */
        [dir="rtl"] .sidebar-item:hover {
            transform: translateX(5px);
        }

        /* ===== PARTICLES BACKGROUND ===== */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            animation: float 20s infinite;
        }

        /* Light Mode Particles */
        body:not(.dark) .particle {
            background: radial-gradient(circle, rgba(0, 86, 179, 0.08) 0%, transparent 70%);
        }

        /* Dark Mode Particles */
        .dark .particle {
            background: radial-gradient(circle, rgba(0, 86, 179, 0.1) 0%, transparent 70%);
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0; }
            10% { opacity: 0.3; }
            50% { transform: translate(50px, -100vh) scale(0.5); opacity: 0; }
        }

        /* ===== SMOOTH TRANSITIONS ===== */
        * {
            transition-property: background-color, border-color, color, box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        * {
            transition-duration: 200ms;
        }

        /* ===== FOCUS STATES ===== */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        .dark input:focus,
        .dark select:focus,
        .dark textarea:focus {
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: true, darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="darkMode ? 'dark bg-gray-900' : 'light'">
    <style>
        body.light {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(240, 245, 255, 0.6) 100%);
            position: relative;
        }

        body.light::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
    </style>

    <!-- Floating Particles Background -->
    <div class="particles">
        @for ($i = 0; $i < 15; $i++)
            <div class="particle" style="width: {{ rand(50, 150) }}px; height: {{ rand(50, 150) }}px; left: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.5 }}s;"></div>
        @endfor
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('admin.partials.header')

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-6 relative z-10">
                <!-- Breadcrumb -->
                @if(isset($breadcrumbs))
                    <nav class="mb-6" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 space-x-reverse">
                            @foreach($breadcrumbs as $index => $breadcrumb)
                                <li class="flex items-center">
                                    @if($index > 0)
                                        <i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i>
                                    @endif
                                    @if($loop->last)
                                        <span class="text-gray-600 dark:text-gray-300 font-semibold">{{ $breadcrumb['title'] }}</span>
                                    @else
                                        <a href="{{ $breadcrumb['url'] }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors">
                                            {{ $breadcrumb['title'] }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <!-- Page Content -->
                <div class="animate-fade-in-up">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-r-4 border-green-500 text-green-800 dark:text-green-300 rounded-lg shadow-sm animate-fade-in-up">
                            <div class="flex items-center">
                                <i data-lucide="check-circle" class="w-5 h-5 ml-3"></i>
                                <span class="font-semibold">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-r-4 border-red-500 text-red-800 dark:text-red-300 rounded-lg shadow-sm animate-fade-in-up">
                            <div class="flex items-center">
                                <i data-lucide="alert-circle" class="w-5 h-5 ml-3"></i>
                                <span class="font-semibold">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            @include('admin.partials.footer')
        </div>
    </div>

    <!-- Toast Notification Component -->
    @include('admin.components.toast')

    <!-- Confirmation Dialog Component -->
    @include('admin.components.confirm-dialog')

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Dark mode toggle
        function toggleDarkMode() {
            const darkMode = localStorage.getItem('darkMode') !== 'true';
            localStorage.setItem('darkMode', darkMode);
            window.location.reload();
        }

        // Add CSRF token to all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        if (token) {
            window.axios = {
                defaults: {
                    headers: {
                        common: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }
                }
            };
        }
    </script>

    @stack('scripts')
</body>
</html>
