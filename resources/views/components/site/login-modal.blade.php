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
     class="sx fixed inset-0 z-[70]" style="display:none" role="dialog" aria-modal="true">

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
             class="sx-surface relative w-full max-w-md p-6 sm:p-8 max-h-[90vh] overflow-y-auto">

            <button type="button" @click="open = false"
                    class="absolute top-3 end-3 grid h-11 w-11 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:text-[color:var(--sx-ink)] hover:bg-[color:var(--sx-surface-2)]"
                    aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="text-center mb-6">
                <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl text-white"
                     style="background: var(--sx-grad)">
                    <i data-lucide="log-in" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-extrabold text-[color:var(--sx-ink)]">{{ __('site.login') }}</h2>
            </div>

            @if ($autoOpen)
                <div class="sx-note mb-4 !grid-cols-1 !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]" role="alert">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.login.submit') }}" @submit="loading = true" class="space-y-4">
                @csrf
                <input type="hidden" name="redirect" value="{{ $here }}">

                <div>
                    <label for="lm_email" class="sx-label">{{ __('auth_web.email') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </span>
                        <input id="lm_email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"
                               placeholder="{{ __('auth_web.email_placeholder') }}"
                               class="sx-input ps-11 text-base sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="lm_password" class="sx-label">{{ __('auth_web.password') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </span>
                        <input id="lm_password" name="password" required autocomplete="current-password"
                               :type="showPassword ? 'text' : 'password'"
                               placeholder="{{ __('auth_web.password_placeholder') }}"
                               class="sx-input ps-11 pe-12 text-base sm:text-sm">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-[color:var(--sx-muted)] transition-colors hover:text-[color:var(--sx-ink)]"
                                :aria-label="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'"
                                :title="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-[color:var(--sx-ink-2)]">
                        <input type="checkbox" name="remember" value="1"
                               class="h-4 w-4 rounded border-[color:var(--sx-line-strong)] text-[color:var(--sx-accent)] focus:ring-[color:var(--sx-accent)]">
                        <span>{{ __('auth_web.remember_me') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-bold text-[color:var(--sx-accent)] hover:underline underline-offset-4">
                        {{ __('auth_web.forgot_password') }}
                    </a>
                </div>

                <button type="submit" :disabled="loading" class="sx-btn is-primary w-full disabled:opacity-60">
                    <i data-lucide="loader-2" class="animate-spin" x-show="loading" x-cloak></i>
                    <span x-show="!loading">{{ __('auth_web.login_cta') }}</span>
                    <span x-show="loading" x-cloak>{{ __('auth_web.submitting') }}</span>
                </button>
            </form>

            @if (count($socialProviders) > 0)
                <div class="my-5 flex items-center gap-3 text-sm text-[color:var(--sx-muted)]">
                    <span class="sx-divider flex-1 !my-0"></span>
                    <span>{{ __('auth_web.or') }}</span>
                    <span class="sx-divider flex-1 !my-0"></span>
                </div>
                <div class="grid gap-3 {{ count($socialProviders) > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1' }}">
                    @if (in_array('google', $socialProviders, true))
                        <a href="{{ route('public.social.redirect', 'google') }}?redirect={{ urlencode($here) }}" class="sx-btn">
                            <img src="{{ asset('images/site/google-g.svg') }}" alt="" aria-hidden="true" class="w-5 h-5">
                            <span class="sx-clamp-1">{{ __('auth_web.continue_with_google') }}</span>
                        </a>
                    @endif
                    @if (in_array('facebook', $socialProviders, true))
                        <a href="{{ route('public.social.redirect', 'facebook') }}?redirect={{ urlencode($here) }}" class="sx-btn">
                            <i data-lucide="facebook" aria-hidden="true"></i>
                            <span class="sx-clamp-1">{{ __('auth_web.continue_with_facebook') }}</span>
                        </a>
                    @endif
                </div>
            @endif

            <p class="mt-6 text-center text-sm text-[color:var(--sx-muted)]">
                {{ __('auth_web.no_account') }}
                <a href="{{ route('public.register') }}" class="font-bold text-[color:var(--sx-accent)] hover:underline underline-offset-4">
                    {{ __('auth_web.create_account_link') }}
                </a>
            </p>
        </div>
    </div>
</div>
