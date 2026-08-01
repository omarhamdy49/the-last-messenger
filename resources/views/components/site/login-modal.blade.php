{{--
    Global login modal — opened via the `open-login` window event
    (e.g. $dispatch('open-login') from the header or the Ask Quran guest CTA).
    Backdrop fades in; the panel scales/fades. Redirects back to the page that
    opened it (hidden `redirect` field + `?redirect=` on the social links).
--}}
@php
    $here = request()->getRequestUri();
    $socialProviders = array_keys(array_filter([
        'google' => filled(config('services.google.client_id')),
        'facebook' => filled(config('services.facebook.client_id')),
    ]));
    // Re-open automatically after a failed modal login (back() with an email error),
    // except on the dedicated auth pages which show their own errors.
    $autoOpen = $errors->has('email')
        && ! request()->routeIs('public.login')
        && ! request()->routeIs('public.register');
@endphp

<div x-data="{ open: {{ $autoOpen ? 'true' : 'false' }}, showPassword: false, loading: false }"
     x-on:open-login.window="open = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-[70]" style="display:none" role="dialog" aria-modal="true">

    {{-- Backdrop (fade) --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- Panel (scale + fade) --}}
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="relative w-full max-w-md rounded-2xl shadow-2xl p-6 sm:p-8 max-h-[90vh] overflow-y-auto
                    bg-white text-gray-900 dark:bg-[#16213e] dark:text-white">

            <button type="button" @click="open = false"
                    class="absolute top-4 end-4 text-gray-400 hover:text-gray-600 dark:hover:text-white"
                    aria-label="{{ __('common.close') }}">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-3 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white">
                    <i data-lucide="log-in" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold">{{ __('site.login') }}</h2>
            </div>

            @if ($autoOpen)
                <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.login.submit') }}" @submit="loading = true" class="space-y-4">
                @csrf
                <input type="hidden" name="redirect" value="{{ $here }}">

                <div>
                    <label for="lm_email" class="block text-sm font-semibold mb-2">{{ __('auth_web.email') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </span>
                        <input id="lm_email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"
                               placeholder="{{ __('auth_web.email_placeholder') }}"
                               class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
                    </div>
                </div>

                <div>
                    <label for="lm_password" class="block text-sm font-semibold mb-2">{{ __('auth_web.password') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </span>
                        <input id="lm_password" name="password" required autocomplete="current-password"
                               :type="showPassword ? 'text' : 'password'"
                               placeholder="{{ __('auth_web.password_placeholder') }}"
                               class="w-full ps-11 pe-11 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3]">
                        <span>{{ __('auth_web.remember_me') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#0056b3] hover:underline">
                        {{ __('auth_web.forgot_password') }}
                    </a>
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-white font-bold bg-gradient-to-br from-[#0056b3] to-[#218838] hover:opacity-90 disabled:opacity-60 transition">
                    <span x-show="!loading">{{ __('auth_web.login_cta') }}</span>
                    <span x-show="loading" x-cloak>{{ __('auth_web.submitting') }}</span>
                </button>
            </form>

            @if (count($socialProviders) > 0)
                <div class="my-5 flex items-center gap-3 text-gray-400 text-sm">
                    <span class="flex-1 h-px bg-gray-200 dark:bg-[#0f3460]"></span>
                    <span>{{ __('auth_web.or') }}</span>
                    <span class="flex-1 h-px bg-gray-200 dark:bg-[#0f3460]"></span>
                </div>
                <div class="grid gap-3 {{ count($socialProviders) > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1' }}">
                    @if (in_array('google', $socialProviders, true))
                        <a href="{{ route('public.social.redirect', 'google') }}?redirect={{ urlencode($here) }}"
                           class="flex items-center justify-center gap-2 py-3 rounded-xl border border-gray-300 dark:border-[#0f3460] text-sm font-semibold hover:bg-gray-50 dark:hover:bg-[#0f3460]/40 transition">
                            <img src="{{ asset('images/site/google-g.svg') }}" alt="" aria-hidden="true" class="w-5 h-5">
                            <span class="truncate">{{ __('auth_web.continue_with_google') }}</span>
                        </a>
                    @endif
                    @if (in_array('facebook', $socialProviders, true))
                        <a href="{{ route('public.social.redirect', 'facebook') }}?redirect={{ urlencode($here) }}"
                           class="flex items-center justify-center gap-2 py-3 rounded-xl border border-gray-300 dark:border-[#0f3460] text-sm font-semibold hover:bg-gray-50 dark:hover:bg-[#0f3460]/40 transition">
                            <i data-lucide="facebook" class="w-5 h-5 text-[#1877F2]"></i>
                            <span class="truncate">{{ __('auth_web.continue_with_facebook') }}</span>
                        </a>
                    @endif
                </div>
            @endif

            <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-300">
                {{ __('auth_web.no_account') }}
                <a href="{{ route('public.register') }}" class="font-semibold text-[#0056b3] hover:underline">
                    {{ __('auth_web.create_account_link') }}
                </a>
            </p>
        </div>
    </div>
</div>
