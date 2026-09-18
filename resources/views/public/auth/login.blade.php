@extends('layouts.guest')

@section('title', __('auth_web.login_title') . ' — ' . __('site.site_name'))

@section('content')
<div class="w-full max-w-md">
    <div class="sx-surface p-7 sm:p-9" x-data="{ showPassword: false, loading: false }">

        {{-- Brand + heading --}}
        <div class="text-center mb-7">
            <x-site.brand-icon size="w-16 h-16" :eager="true" class="mx-auto mb-4" />
            <h1 class="text-2xl font-extrabold text-[color:var(--sx-ink)]">{{ __('auth_web.login_heading') }}</h1>
            <p class="mt-1.5 text-sm text-[color:var(--sx-muted)]">{{ __('auth_web.login_subheading') }}</p>
        </div>

        {{-- Error summary (kept alongside the inline errors: throttle and
             credential failures are not always tied to a rendered field). --}}
        @if ($errors->any())
            <div class="sx-note mb-6 !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]" role="alert">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-1 !text-[color:var(--sx-danger)]" aria-hidden="true"></i>
                <ul class="space-y-1 list-disc list-inside text-sm">
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
                <label for="email" class="sx-label">{{ __('auth_web.email') }} <span class="sx-req" aria-hidden="true">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </span>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           value="{{ old('email') }}"
                           placeholder="{{ __('auth_web.email_placeholder') }}"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                           class="sx-input ps-11 text-base sm:text-sm">
                </div>
                @error('email')
                    <p id="email-error" class="sx-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="sx-label">{{ __('auth_web.password') }} <span class="sx-req" aria-hidden="true">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </span>
                    <input id="password" name="password" required autocomplete="current-password"
                           :type="showPassword ? 'text' : 'password'"
                           placeholder="{{ __('auth_web.password_placeholder') }}"
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                           class="sx-input ps-11 pe-14 text-base sm:text-sm">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute end-1 top-1/2 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:text-[color:var(--sx-accent)]"
                            :aria-label="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'"
                            :title="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                        <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                        <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="sx-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember + forgot --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-[color:var(--sx-ink-2)]">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-[color:var(--sx-line-strong)] accent-[color:var(--sx-accent)]">
                    <span>{{ __('auth_web.remember_me') }}</span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="inline-flex min-h-11 items-center text-sm font-bold text-[color:var(--sx-accent)] hover:underline">
                    {{ __('auth_web.forgot_password') }}
                </a>
            </div>

            {{-- Submit --}}
            <button type="submit" :disabled="loading"
                    class="sx-btn is-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="!loading" class="inline-flex items-center gap-2">
                    <i data-lucide="log-in" aria-hidden="true"></i>
                    {{ __('auth_web.login_cta') }}
                </span>
                <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                    <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                    {{ __('auth_web.submitting') }}
                </span>
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
            <div class="my-6 flex items-center gap-3 text-sm text-[color:var(--sx-muted)]">
                <span class="h-px flex-1 bg-[color:var(--sx-line)]"></span>
                <span>{{ __('auth_web.or') }}</span>
                <span class="h-px flex-1 bg-[color:var(--sx-line)]"></span>
            </div>
            <div class="grid gap-3 {{ count($socialProviders) > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1' }}">
                @if (in_array('google', $socialProviders, true))
                    <a href="{{ route('public.social.redirect', 'google') }}" class="sx-btn w-full">
                        <img src="{{ asset('images/site/google-g.svg') }}" alt="" aria-hidden="true" class="w-5 h-5">
                        <span class="truncate">{{ __('auth_web.continue_with_google') }}</span>
                    </a>
                @endif
                @if (in_array('facebook', $socialProviders, true))
                    <a href="{{ route('public.social.redirect', 'facebook') }}" class="sx-btn w-full">
                        <i data-lucide="facebook" aria-hidden="true"></i>
                        <span class="truncate">{{ __('auth_web.continue_with_facebook') }}</span>
                    </a>
                @endif
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-[color:var(--sx-muted)]">
            {{ __('auth_web.no_account') }}
            <a href="{{ route('public.register') }}" class="font-bold text-[color:var(--sx-accent)] hover:underline">
                {{ __('auth_web.create_account_link') }}
            </a>
        </p>
    </div>
</div>
@endsection
