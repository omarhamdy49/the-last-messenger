<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
      x-data="{
          darkMode: window.resolveDarkMode(),
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
      @theme-changed.window="darkMode = $event.detail.dark"
      class="antialiased">
<head>
    @include('partials.theme-preference')
    <script>window.applyDarkMode();</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.adminRoutes = {
            categories: {{ Js::from(route('admin.categories.index')) }},
            tagSearch: {{ Js::from(route('admin.tags.search')) }},
        };
    </script>

    <title>{{ __('site.site_name') }} — {{ __('admin.admin_panel') }}</title>

    {{-- Same mark as the public site: the Seal of the Prophet ﷺ. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Compiled Assets -->
    <script>window.enabledContentLocales = {{ \Illuminate\Support\Js::from(enabled_content_locales()) }};</script>
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])

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

                <!-- Content Wrapper with Padding (extra bottom space when the player bar is visible) -->
                {{-- backwards, not forwards: a transform kept after the animation makes this wrapper the
                     containing block of every position:fixed modal inside it, pushing them off-screen. --}}
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fadeInUp [animation-fill-mode:backwards]"
                     :style="$store.audioPlayer && $store.audioPlayer.track ? 'padding-bottom: 6rem' : ''">

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

    {{-- Global Toast Notifications --}}
    <x-admin.toast-container />

    {{-- Flash messages surface as toasts --}}
    @if(session('success') || session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
            window.showToast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
            window.showToast(@json(session('error')), 'error');
            @endif
        });
    </script>
    @endif

    {{-- Global Confirm Modal --}}
    <x-admin.confirm-modal />

    {{-- Global audio player (persists across admin pages) --}}
    <x-audio-player />

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Alpine re-renders (data-tables, language tabs, modals) insert fresh
            // <i data-lucide> nodes after the initial pass — re-render them, one
            // coalesced pass per frame. createIcons swaps <i> for <svg>, which the
            // selector doesn't match, so this can't loop.
            let iconRenderQueued = false;
            const scheduleIcons = () => {
                if (iconRenderQueued) return;
                iconRenderQueued = true;
                requestAnimationFrame(() => {
                    iconRenderQueued = false;
                    lucide.createIcons();
                });
            };

            const containsUnrenderedIcon = (node) => node.nodeType === 1
                && (node.matches?.('i[data-lucide]') || node.querySelector?.('i[data-lucide]'));

            // Debounce the observer callback to reduce processing frequency during rapid DOM updates
            let iconObserverTimer = null;
            let pendingMutations = [];

            const processPendingIconMutations = () => {
                const mutations = pendingMutations;
                pendingMutations = [];

                for (const mutation of mutations) {
                    for (const added of mutation.addedNodes) {
                        if (containsUnrenderedIcon(added)) {
                            scheduleIcons();
                            return;
                        }
                    }
                }
            };

            const debouncedIconCheck = (mutations) => {
                pendingMutations.push(...mutations);
                clearTimeout(iconObserverTimer);
                iconObserverTimer = setTimeout(processPendingIconMutations, 150);
            };

            /* Set up observers for icon containers. Supports scoped observation via
               data-icon-container attribute for better performance, with fallback to
               document.body for admin panel. */
            const iconObservers = [];

            const cleanupIconObservers = () => {
                // Disconnect all active observers
                iconObservers.forEach(obs => obs.disconnect());
                iconObservers.length = 0;

                // Clear any pending timers and mutations
                clearTimeout(iconObserverTimer);
                iconObserverTimer = null;
                pendingMutations.length = 0;
            };

            const setupIconObservers = () => {
                // Clean up any existing observers first
                cleanupIconObservers();

                // Look for scoped containers with data-icon-container attribute
                const containers = document.querySelectorAll('[data-icon-container]');

                if (containers.length > 0) {
                    // Observe each scoped container
                    containers.forEach(container => {
                        const observer = new MutationObserver(debouncedIconCheck);
                        observer.observe(container, { childList: true, subtree: true });
                        iconObservers.push(observer);
                    });
                } else {
                    // Fallback to document.body observation for admin panel
                    const observer = new MutationObserver(debouncedIconCheck);
                    observer.observe(document.body, { childList: true, subtree: true });
                    iconObservers.push(observer);
                }
            };

            // Initialize observers
            setupIconObservers();

            // Clean up observers when the page is being unloaded to prevent memory leaks
            // during navigation or tab closure
            window.addEventListener('pagehide', cleanupIconObservers);

            // Export for potential cleanup or re-initialization
            window.iconObservers = iconObservers;
            window.setupIconObservers = setupIconObservers;
            window.cleanupIconObservers = cleanupIconObservers;
        });

        // Handle window resize for sidebar.
        // `window.Alpine` is guarded: this script is inline and runs during
        // parsing, while the Alpine bundle is deferred — a resize fired in that
        // window threw "Alpine is not defined" on load.
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && window.Alpine) {
                window.Alpine.store('sidebarOpen', true);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
