<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ get_text_direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.login_title') }} - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#0056b3',
                        secondary: '#218838',
                        accent: '#64CCC5',
                    },
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    },
                    animation: {
                        'fadeInUp': 'fadeInUp 0.6s ease-out',
                        'fadeIn': 'fadeIn 0.8s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
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

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .animated-mesh-bg {
            background:
                radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(100, 204, 197, 0.1) 0%, transparent 50%);
            background-size: 200% 200%;
            animation: meshMove 20s ease-in-out infinite;
        }

        @keyframes meshMove {
            0%, 100% { background-position: 0% 0%; }
            50% { background-position: 100% 100%; }
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(100, 204, 197, 0.2);
        }

        .btn-primary {
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        /* Floating particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(100, 204, 197, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: particleFloat 15s linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-100vh) translateX(50px) scale(1);
                opacity: 0;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0056b3, #218838);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0069d9, #28a745);
        }

        /* Hide scrollbar for main container by default */
        .main-container::-webkit-scrollbar {
            width: 6px;
        }

        .main-container::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen overflow-hidden" x-data="{ showPassword: false }">

    <!-- Animated Background -->
    <div class="fixed inset-0 animated-mesh-bg overflow-hidden"></div>

    <!-- Floating Particles Container -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        @for ($i = 0; $i < 10; $i++)
        <div class="particle" style="
            width: {{ rand(20, 60) }}px;
            height: {{ rand(20, 60) }}px;
            left: {{ rand(0, 100) }}%;
            animation-delay: {{ $i * 1.5 }}s;
            animation-duration: {{ rand(10, 20) }}s;
        "></div>
        @endfor
    </div>

    <!-- Main Container -->
    <div class="main-container relative min-h-screen max-h-screen overflow-y-auto flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Logo & Welcome Section -->
            <div class="text-center mb-8 animate-fadeInUp">
                <!-- Logo -->
                <div class="inline-block mb-6">
                    <div class="w-24 h-24 mx-auto rounded-2xl gradient-bg shadow-2xl flex items-center justify-center animate-float">
                        <i data-lucide="book-open" class="w-12 h-12 text-white"></i>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-4xl font-bold mb-2">
                    <span class="text-white">{{ __('auth.welcome_back') }}</span>
                </h1>
                <p class="text-gray-400 text-lg">
                    {{ __('auth.login_subtitle') }}
                </p>
            </div>

            <!-- Login Card -->
            <div class="glass rounded-3xl shadow-2xl p-8 animate-fadeInUp" style="animation-delay: 0.2s;">

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-green-300 text-sm">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-300 text-sm">
                        <div class="flex items-start gap-2">
                            <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5"></i>
                            <div>
                                <p class="font-semibold mb-1">{{ __('auth.errors_occurred') }}</p>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-2 text-gray-200">
                            {{ __('auth.email') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="{{ __('auth.email_placeholder') }}"
                                class="input-field w-full {{ app()->getLocale() === 'ar' ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-accent"
                            >
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold mb-2 text-gray-200">
                            {{ __('auth.password') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="{{ __('auth.password_placeholder') }}"
                                class="input-field w-full {{ app()->getLocale() === 'ar' ? 'pr-10 pl-12' : 'pl-10 pr-12' }} py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-accent"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'left-0 pl-3' : 'right-0 pr-3' }} flex items-center text-gray-400 hover:text-accent transition-colors"
                            >
                                <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                class="w-5 h-5 rounded border-white/20 bg-white/10 text-accent focus:ring-accent focus:ring-offset-0 cursor-pointer"
                            >
                            <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }} text-sm text-gray-300">
                                {{ __('auth.remember_me') }}
                            </span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-accent hover:text-accent/80 transition-colors">
                                {{ __('auth.forgot_password') }}
                            </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary w-full py-3.5 rounded-xl font-bold text-white gradient-bg shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-5 h-5"></i>
                            {{ __('auth.login_button') }}
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                @if (Route::has('register'))
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/10"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-transparent text-gray-400">{{ __('auth.or') }}</span>
                    </div>
                </div>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="text-gray-400 text-sm">
                        {{ __('auth.no_account') }}
                        <a href="{{ route('register') }}" class="text-accent hover:text-accent/80 font-semibold transition-colors">
                            {{ __('auth.register_now') }}
                        </a>
                    </p>
                </div>
                @endif
            </div>

            <!-- Footer Links -->
            <div class="mt-8 text-center text-sm text-gray-500 animate-fadeIn" style="animation-delay: 0.4s;">
                <p>
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('auth.all_rights_reserved') }}
                </p>
                <div class="mt-2 space-x-4 rtl:space-x-reverse">
                    <a href="#" class="hover:text-accent transition-colors">{{ __('auth.privacy_policy') }}</a>
                    <span class="text-gray-700">•</span>
                    <a href="#" class="hover:text-accent transition-colors">{{ __('auth.terms_of_service') }}</a>
                    <span class="text-gray-700">•</span>
                    <a href="#" class="hover:text-accent transition-colors">{{ __('auth.support') }}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Reinitialize icons when Alpine changes the DOM
            document.addEventListener('alpine:initialized', () => {
                lucide.createIcons();
            });

            // Watch for password visibility toggle
            const passwordToggle = document.querySelector('[x-on\\:click]');
            if (passwordToggle) {
                passwordToggle.addEventListener('click', () => {
                    setTimeout(() => lucide.createIcons(), 10);
                });
            }
        });
    </script>
</body>
</html>
