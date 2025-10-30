<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول - آخر الرسل</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-1000px 0' },
                            '100%': { backgroundPosition: '1000px 0' }
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                        'shimmer': 'shimmer 2s infinite'
                    }
                }
            }
        }
    </script>

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #64CCC5;
            box-shadow: 0 0 0 4px rgba(100, 204, 197, 0.1);
        }

        .gradient-text {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Floating particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        @keyframes particleFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.1; }
            100% { transform: translate(var(--tx), var(--ty)) scale(0.3); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 relative overflow-hidden">

    <!-- Animated Background Particles -->
    @for ($i = 0; $i < 30; $i++)
        @php
            $size = rand(20, 100);
            $left = rand(0, 100);
            $top = rand(0, 100);
            $duration = rand(15, 30);
            $delay = rand(0, 10);
            $tx = rand(-100, 100);
            $ty = rand(-200, -400);
        @endphp
        <div class="particle"
             style="width: {{ $size }}px;
                    height: {{ $size }}px;
                    left: {{ $left }}%;
                    top: {{ $top }}%;
                    --tx: {{ $tx }}px;
                    --ty: {{ $ty }}px;
                    animation: particleFloat {{ $duration }}s ease-in-out {{ $delay }}s infinite;">
        </div>
    @endfor

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md animate-fade-in-up">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl glass mb-4 animate-float">
                <i data-lucide="book-open" class="w-10 h-10 text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">آخر الرسل</h1>
            <p class="text-white/80 text-lg">لوحة التحكم الإدارية</p>
        </div>

        <!-- Login Form Card -->
        <div class="glass rounded-3xl p-8 shadow-2xl">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-2">مرحباً بك</h2>
                <p class="text-white/80">سجل دخولك للوصول إلى لوحة التحكم</p>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-xl backdrop-blur-sm animate-fade-in-up">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-300 flex-shrink-0"></i>
                        <div class="flex-1">
                            @foreach($errors->all() as $error)
                                <p class="text-sm text-red-100">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.web.login.submit') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <!-- Email Field -->
                <div class="mb-6">
                    <label for="email" class="block text-white font-semibold mb-2">
                        <i data-lucide="mail" class="w-4 h-4 inline-block ml-2"></i>
                        البريد الإلكتروني
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="input-glass w-full px-4 py-3 rounded-xl text-gray-800 placeholder-gray-500 focus:outline-none transition-all duration-300"
                        placeholder="admin@example.com"
                    >
                </div>

                <!-- Password Field -->
                <div class="mb-6" x-data="{ showPassword: false }">
                    <label for="password" class="block text-white font-semibold mb-2">
                        <i data-lucide="lock" class="w-4 h-4 inline-block ml-2"></i>
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            class="input-glass w-full px-4 py-3 rounded-xl text-gray-800 placeholder-gray-500 focus:outline-none transition-all duration-300"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800 transition-colors"
                        >
                            <i :data-lucide="showPassword ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 text-blue-600 bg-white/90 border-white/50 rounded focus:ring-2 focus:ring-blue-500 ml-2"
                        >
                        <span class="text-sm text-white/90">تذكرني</span>
                    </label>

                    <a href="#" class="text-sm text-white/90 hover:text-white transition-colors font-semibold">
                        نسيت كلمة المرور؟
                    </a>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-white/95 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden group"
                >
                    <span x-show="!loading" class="flex items-center justify-center">
                        <i data-lucide="log-in" class="w-5 h-5 ml-2"></i>
                        تسجيل الدخول
                    </span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        جاري التحميل...
                    </span>

                    <!-- Shimmer effect on hover -->
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                    </div>
                </button>
            </form>

            <!-- Additional Info -->
            <div class="mt-6 pt-6 border-t border-white/20">
                <p class="text-center text-sm text-white/70">
                    يتطلب الوصول إلى لوحة التحكم صلاحيات إدارية
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white/70 text-sm">
            <p>© {{ date('Y') }} آخر الرسل - جميع الحقوق محفوظة</p>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Re-initialize icons after Alpine mutations
        document.addEventListener('alpine:initialized', () => {
            lucide.createIcons();
        });

        // Watch for password visibility toggle
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                setTimeout(() => lucide.createIcons(), 100);
            });
        });
    </script>
</body>
</html>
