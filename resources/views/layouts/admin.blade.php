<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
      x-data="{
          darkMode: localStorage.getItem('darkMode') !== 'false',
          sidebarOpen: window.innerWidth >= 1024,
          currentLang: '{{ app()->getLocale() }}',

          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('darkMode', this.darkMode);
              this.$nextTick(() => lucide.createIcons());
          },

          toggleSidebar() {
              this.sidebarOpen = !this.sidebarOpen;
          },

          changeLang(lang) {
              this.currentLang = lang;
              window.location.href = `{{ url('/') }}/admin?lang=${lang}`;
          }
      }"
      :class="darkMode ? 'dark' : ''"
      class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'The Last Messenger') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS 4 -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js Plugins (Must load BEFORE Alpine.js) -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#0056b3',
                            50: '#e6f0ff',
                            100: '#cce0ff',
                            200: '#99c2ff',
                            300: '#66a3ff',
                            400: '#3385ff',
                            500: '#0056b3',
                            600: '#004799',
                            700: '#003880',
                            800: '#002966',
                            900: '#001a4d'
                        },
                        secondary: {
                            DEFAULT: '#218838',
                            50: '#e8f5e9',
                            100: '#c8e6c9',
                            200: '#a5d6a7',
                            300: '#81c784',
                            400: '#66bb6a',
                            500: '#218838',
                            600: '#1e7e34',
                            700: '#1b5e20',
                            800: '#184e1c',
                            900: '#153e18'
                        },
                        accent: '#64CCC5'
                    },
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif']
                    },
                    backgroundImage: {
                        'gradient-primary': 'linear-gradient(135deg, #0056b3 0%, #218838 100%)',
                        'gradient-soft': 'linear-gradient(135deg, rgba(0, 86, 179, 0.95) 0%, rgba(33, 136, 56, 0.9) 100%)'
                    },
                    animation: {
                        'fadeInUp': 'fadeInUp 0.6s ease-out',
                        'fadeIn': 'fadeIn 0.8s ease-out',
                        'slideInRight': 'slideInRight 0.5s ease-out',
                        'shimmer': 'shimmer 2s infinite'
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideInRight: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' }
                        },
                        shimmer: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }

        * {
            font-family: 'Cairo', sans-serif;
        }

        /* Glassmorphism utility classes */
        .glass {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .glass-dark {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-light {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(100, 204, 197, 0.5);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 204, 197, 0.8);
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Shadow utilities */
        .shadow-soft {
            box-shadow: 0 4px 6px -1px rgba(0, 86, 179, 0.1), 0 2px 4px -1px rgba(0, 86, 179, 0.06);
        }

        .shadow-glow {
            box-shadow: 0 0 20px rgba(100, 204, 197, 0.3);
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>

    @stack('styles')
</head>
<body :class="darkMode ? 'bg-gray-950 text-white' : 'bg-gray-50 text-gray-900'"
      class="min-h-screen font-cairo">

    <!-- Admin Layout Container -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar Component -->
        <x-admin.sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header Component -->
            <x-admin.header />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto custom-scrollbar"
                  :class="darkMode ? 'bg-gray-950' : 'bg-gray-50'">

                <!-- Content Wrapper with Padding -->
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fadeInUp">

                    <!-- Page Title Section (Optional - Can be yielded) -->
                    @hasSection('page-header')
                        <div class="mb-8">
                            @yield('page-header')
                        </div>
                    @endif

                    <!-- Main Content Slot -->
                    @yield('content')

                </div>

            </main>

            <!-- Footer (Optional) -->
            <footer class="glass py-4 px-6 border-t"
                    :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        &copy; {{ date('Y') }} {{ __('admin.copyright') }}
                    </p>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="#" class="hover:text-accent transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.documentation') }}
                        </a>
                        <span :class="darkMode ? 'text-gray-600' : 'text-gray-300'">|</span>
                        <a href="#" class="hover:text-accent transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.support') }}
                        </a>
                        <span :class="darkMode ? 'text-gray-600' : 'text-gray-300'">|</span>
                        <a href="#" class="hover:text-accent transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.api_docs') }}
                        </a>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen && window.innerWidth < 1024"
         x-cloak
         @click="sidebarOpen = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden">
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });

        // Handle window resize for sidebar
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                Alpine.store('sidebarOpen', true);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
