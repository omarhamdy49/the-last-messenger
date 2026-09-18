@extends('layouts.guest')

@section('title', __('auth_web.register_title') . ' — ' . __('site.site_name'))

@section('content')
<div class="w-full max-w-md">
    <div class="sx-surface p-7 sm:p-9" x-data="registerForm()">

        {{-- Brand + heading --}}
        <div class="text-center mb-7">
            <x-site.brand-icon size="w-16 h-16" :eager="true" class="mx-auto mb-4" />
            <h1 class="text-2xl font-extrabold text-[color:var(--sx-ink)]">{{ __('auth_web.register_heading') }}</h1>
            <p class="mt-1.5 text-sm text-[color:var(--sx-muted)]">{{ __('auth_web.register_subheading') }}</p>
        </div>

        {{-- Error summary (kept alongside the inline errors: some rules report
             against keys that are not rendered as their own field). --}}
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

        <form method="POST" action="{{ route('public.register.submit') }}"
              @submit="loading = true" class="space-y-5 auth-stagger">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="sx-label">{{ __('auth_web.name') }} <span class="sx-req" aria-hidden="true">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </span>
                    <input id="name" name="name" type="text" required autocomplete="name"
                           value="{{ old('name') }}"
                           placeholder="{{ __('auth_web.name_placeholder') }}"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                           class="sx-input ps-11 text-base sm:text-sm">
                </div>
                @error('name')
                    <p id="name-error" class="sx-error">{{ $message }}</p>
                @enderror
            </div>

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
                    <input id="password" name="password" required autocomplete="new-password"
                           x-model="password"
                           :type="showPassword ? 'text' : 'password'"
                           placeholder="{{ __('auth_web.password_placeholder') }}"
                           aria-describedby="password-hint @error('password') password-error @enderror"
                           @error('password') aria-invalid="true" @enderror
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

                {{-- 4-segment strength meter --}}
                <div x-show="password.length > 0" x-cloak class="mt-3">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-xs text-[color:var(--sx-muted)]">{{ __('auth_web.password_strength') }}</span>
                        <span class="text-xs font-bold" :class="strengthTextClass" x-text="strengthLabel"></span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5" role="presentation">
                        <template x-for="i in 4" :key="i">
                            <div class="h-1.5 rounded-full transition-colors"
                                 :class="i <= strength ? strengthBarClass : 'bg-[color:var(--sx-line-strong)]'"></div>
                        </template>
                    </div>
                </div>
                <p id="password-hint" class="sx-hint">{{ __('auth_web.password_hint') }}</p>
            </div>

            {{-- Confirm password --}}
            <div>
                <label for="password_confirmation" class="sx-label">{{ __('auth_web.password_confirmation') }} <span class="sx-req" aria-hidden="true">*</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </span>
                    <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           :type="showConfirm ? 'text' : 'password'"
                           placeholder="{{ __('auth_web.password_confirmation_placeholder') }}"
                           @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror
                           class="sx-input ps-11 pe-14 text-base sm:text-sm">
                    <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute end-1 top-1/2 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:text-[color:var(--sx-accent)]"
                            :aria-label="showConfirm ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'"
                            :title="showConfirm ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                        <i x-show="!showConfirm" data-lucide="eye" class="w-5 h-5"></i>
                        <i x-show="showConfirm" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <p id="password_confirmation-error" class="sx-error">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs text-[color:var(--sx-muted)]">{{ __('auth_web.agree_terms') }}</p>

            {{-- Submit --}}
            <button type="submit" :disabled="loading"
                    class="sx-btn is-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="!loading" class="inline-flex items-center gap-2">
                    <i data-lucide="user-plus" aria-hidden="true"></i>
                    {{ __('auth_web.register_cta') }}
                </span>
                <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                    <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                    {{ __('auth_web.submitting') }}
                </span>
            </button>
        </form>

        {{-- Social login --}}
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
            {{ __('auth_web.have_account') }}
            <a href="{{ route('public.login') }}" class="font-bold text-[color:var(--sx-accent)] hover:underline">
                {{ __('auth_web.login_link') }}
            </a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function registerForm() {
        return {
            password: '',
            showPassword: false,
            showConfirm: false,
            loading: false,

            // 4-segment scoring mirrors the RegisterRequest rules
            // (length, mixed case, digit, symbol).
            get strength() {
                const pwd = this.password;
                let score = 0;
                if (pwd.length >= 8) score++;
                if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
                if (/[0-9]/.test(pwd)) score++;
                if (/[^a-zA-Z0-9]/.test(pwd)) score++;
                return score;
            },
            get strengthLabel() {
                return [
                    @json(__('auth_web.strength_very_weak')),
                    @json(__('auth_web.strength_weak')),
                    @json(__('auth_web.strength_medium')),
                    @json(__('auth_web.strength_strong')),
                    @json(__('auth_web.strength_very_strong')),
                ][this.strength];
            },
            // Danger → amber → accent → brand green: the only non-token step is
            // the amber "medium", which the palette has no equivalent for.
            get strengthBarClass() {
                return [
                    'bg-[color:var(--sx-danger)]',
                    'bg-[color:var(--sx-danger)]',
                    'bg-amber-500',
                    'bg-[color:var(--sx-accent)]',
                    'bg-[color:var(--sx-green)]',
                ][this.strength];
            },
            // The label already says the strength in words, so it only needs to
            // stay readable: danger while weak, normal ink once it is not.
            get strengthTextClass() {
                return this.strength <= 1
                    ? 'text-[color:var(--sx-danger)]'
                    : 'text-[color:var(--sx-ink)]';
            },
        };
    }
</script>
@endpush
