@extends('layouts.public')

@section('title', __('auth_web.login_title') . ' — ' . __('site.site_name'))

@section('content')
<section class="relative min-h-[calc(100vh-5rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">

    {{-- Decorative floating shapes (match prototype) --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-20 end-10 w-72 h-72 bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 rounded-full blur-3xl animate-fade-in-up motion-reduce:animate-none"></div>
        <div class="absolute bottom-20 start-10 w-96 h-96 bg-gradient-to-br from-[#218838]/20 to-[#64CCC5]/20 rounded-full blur-3xl animate-fade-in-up motion-reduce:animate-none"></div>
    </div>

    <div class="relative w-full max-w-5xl">
        <div class="glass rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up motion-reduce:animate-none"
             :class="$store.theme.dark ? 'border-white/10' : 'border-gray-200'">
            <div class="grid md:grid-cols-2">

                {{-- Marketing / hero column --}}
                <div class="hidden md:flex relative bg-gradient-to-br from-[#0056b3] to-[#218838] p-12 text-white flex-col justify-center overflow-hidden">
                    <div class="relative z-10">
                        {{-- Brand seal on a frosted backing so it reads on the gradient panel --}}
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/25 shadow-lg mb-6">
                            <x-site.brand-icon size="w-14 h-14" :eager="true" />
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-bold font-amiri mb-4">{{ __('auth_web.login_heading') }}</h2>
                        <p class="text-lg text-white/90 mb-10">{{ __('auth_web.login_subheading') }}</p>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 bg-white/10 backdrop-blur p-4 rounded-2xl">
                                <span class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="library" class="w-6 h-6"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold">{{ __('auth_web.benefit_content_title') }}</h3>
                                    <p class="text-sm text-white/80">{{ __('auth_web.benefit_content_desc') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-white/10 backdrop-blur p-4 rounded-2xl">
                                <span class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="badge-check" class="w-6 h-6"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold">{{ __('auth_web.benefit_verified_title') }}</h3>
                                    <p class="text-sm text-white/80">{{ __('auth_web.benefit_verified_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form column --}}
                <div class="p-8 sm:p-12 bg-white dark:bg-[#16213e]"
                     x-data="{ showPassword: false, loading: false }">

                    <h1 class="text-2xl font-bold mb-1">{{ __('auth_web.login_title') }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-8">{{ __('auth_web.login_subheading') }}</p>

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
                          @submit="loading = true" class="space-y-5">
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
                                       class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
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
                                       class="w-full ps-11 pe-11 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
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
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-white font-bold bg-gradient-to-br from-[#0056b3] to-[#218838] hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed transition">
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
                                    <i data-lucide="chrome" class="w-5 h-5 text-[#EA4335]"></i>
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
        </div>
    </div>
</section>
@endsection
