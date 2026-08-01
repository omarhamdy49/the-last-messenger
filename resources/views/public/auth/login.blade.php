@extends('layouts.guest')

@section('title', __('auth_web.login_title') . ' — ' . __('site.site_name'))

@section('content')
<div class="w-full max-w-md">
    <div class="rounded-3xl bg-white/95 dark:bg-[#16213e]/95 backdrop-blur-xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10 p-8 sm:p-10 animate-fade-in-up motion-reduce:animate-none"
         x-data="{ showPassword: false, loading: false }">

        {{-- Brand + heading --}}
        <div class="text-center mb-8">
            <x-site.brand-icon size="w-16 h-16" :eager="true" class="mx-auto mb-4" />
            <h1 class="text-2xl font-bold font-amiri">{{ __('auth_web.login_heading') }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1.5">{{ __('auth_web.login_subheading') }}</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300"
                 role="alert">
                <ul class="space-y-1 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('public.login.submit') }}"
              @submit="loading = true" class="space-y-5 auth-stagger">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold mb-2">{{ __('auth_web.email') }}</label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </span>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           value="{{ old('email') }}"
                           placeholder="{{ __('auth_web.email_placeholder') }}"
                           class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-4 focus:ring-[#0056b3]/15 focus:border-[#0056b3] outline-none transition">
                </div>
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-semibold mb-2">{{ __('auth_web.password') }}</label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </span>
                    <input id="password" name="password" required autocomplete="current-password"
                           :type="showPassword ? 'text' : 'password'"
                           placeholder="{{ __('auth_web.password_placeholder') }}"
                           class="w-full ps-11 pe-11 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-4 focus:ring-[#0056b3]/15 focus:border-[#0056b3] outline-none transition">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
                            :aria-label="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                        <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                        <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                    </button>
                </div>
            </div>

            {{-- Remember + forgot --}}
            <div class="flex items-center justify-between flex-wrap gap-2">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3]">
                    <span>{{ __('auth_web.remember_me') }}</span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="text-sm font-semibold text-[#0056b3] hover:underline">
                    {{ __('auth_web.forgot_password') }}
                </a>
            </div>

            {{-- Submit --}}
            <button type="submit" :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-white font-bold bg-gradient-to-br from-[#0056b3] to-[#218838] hover:opacity-90 active:scale-[.99] disabled:opacity-60 disabled:cursor-not-allowed transition">
                <svg x-show="loading" x-cloak class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-show="!loading">{{ __('auth_web.login_cta') }}</span>
                <span x-show="loading" x-cloak>{{ __('auth_web.submitting') }}</span>
            </button>
        </form>

        {{-- Social login — only providers with credentials render a button. --}}
        @php
            $socialProviders = array_keys(array_filter([
                'google' => filled(config('services.google.client_id')),
                'facebook' => filled(config('services.facebook.client_id')),
            ]));
        @endphp
        @if (count($socialProviders) > 0)
            <div class="my-6 flex items-center gap-3 text-gray-400 text-sm">
                <span class="flex-1 h-px bg-gray-200 dark:bg-[#0f3460]"></span>
                <span>{{ __('auth_web.or') }}</span>
                <span class="flex-1 h-px bg-gray-200 dark:bg-[#0f3460]"></span>
            </div>
            <div class="grid gap-3 {{ count($socialProviders) > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1' }}">
                @if (in_array('google', $socialProviders, true))
                    <a href="{{ route('public.social.redirect', 'google') }}"
                       class="flex items-center justify-center gap-2 py-3 rounded-xl border border-gray-300 dark:border-[#0f3460] text-sm font-semibold hover:bg-gray-50 dark:hover:bg-[#0f3460]/40 transition">
                        <img src="{{ asset('images/site/google-g.svg') }}" alt="" aria-hidden="true" class="w-5 h-5">
                        <span class="truncate">{{ __('auth_web.continue_with_google') }}</span>
                    </a>
                @endif
                @if (in_array('facebook', $socialProviders, true))
                    <a href="{{ route('public.social.redirect', 'facebook') }}"
                       class="flex items-center justify-center gap-2 py-3 rounded-xl border border-gray-300 dark:border-[#0f3460] text-sm font-semibold hover:bg-gray-50 dark:hover:bg-[#0f3460]/40 transition">
                        <i data-lucide="facebook" class="w-5 h-5 text-[#1877F2]"></i>
                        <span class="truncate">{{ __('auth_web.continue_with_facebook') }}</span>
                    </a>
                @endif
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
            {{ __('auth_web.no_account') }}
            <a href="{{ route('public.register') }}" class="font-semibold text-[#0056b3] hover:underline">
                {{ __('auth_web.create_account_link') }}
            </a>
        </p>
    </div>
</div>
@endsection
