@extends('layouts.public')

@section('title', __('auth_web.reset_title') . ' — ' . __('site.site_name'))

@section('content')
@php $backArrow = in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'arrow-right' : 'arrow-left'; @endphp
<div class="sx-wrap py-10 sm:py-16">
    <div class="sx-surface mx-auto max-w-5xl overflow-hidden">
        <div class="grid md:grid-cols-2">

            {{-- Reassurance column: the brand gradient plate, hidden on phones --}}
            <aside class="hidden flex-col justify-center gap-7 bg-[image:var(--sx-grad)] p-10 text-white md:flex lg:p-12">
                <span class="grid h-16 w-16 place-items-center rounded-2xl border border-white/25 bg-[rgb(255_255_255/0.15)]">
                    <i data-lucide="lock-keyhole" class="h-8 w-8"></i>
                </span>
                <div>
                    <h2 class="text-3xl font-extrabold leading-tight lg:text-4xl">{{ __('auth_web.reset_heading') }}</h2>
                    <p class="mt-3 text-lg leading-relaxed text-white/90">{{ __('auth_web.reset_subheading') }}</p>
                </div>

                <ul class="space-y-3">
                    <li class="flex items-center gap-4 rounded-2xl border border-white/20 bg-[rgb(255_255_255/0.10)] p-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[rgb(255_255_255/0.20)]">
                            <i data-lucide="shield-check" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <h3 class="font-bold">{{ __('auth_web.benefit_verified_title') }}</h3>
                            <p class="text-sm text-white/80">{{ __('auth_web.benefit_verified_desc') }}</p>
                        </div>
                    </li>
                </ul>
            </aside>

            {{-- Form column --}}
            <div class="p-7 sm:p-10 lg:p-12" x-data="resetPasswordForm()">

                <h1 class="text-2xl font-extrabold text-[color:var(--sx-ink)]">{{ __('auth_web.reset_title') }}</h1>
                <p class="mt-1.5 mb-7 text-sm text-[color:var(--sx-muted)]">{{ __('auth_web.reset_subheading') }}</p>

                {{-- Error summary (kept alongside the inline errors: an expired
                     token reports against the request, not a single field). --}}
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

                <form method="POST" action="{{ route('password.update') }}"
                      @submit="loading = true" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- Email --}}
                    <div>
                        <label for="email" class="sx-label">{{ __('auth_web.email') }} <span class="sx-req" aria-hidden="true">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </span>
                            <input id="email" name="email" type="email" required autocomplete="email"
                                   value="{{ old('email', $email) }}"
                                   placeholder="{{ __('auth_web.email_placeholder') }}"
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                   class="sx-input ps-11 text-base sm:text-sm">
                        </div>
                        @error('email')
                            <p id="email-error" class="sx-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- New password --}}
                    <div>
                        <label for="password" class="sx-label">{{ __('auth_web.new_password') }} <span class="sx-req" aria-hidden="true">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </span>
                            <input id="password" name="password" required autocomplete="new-password"
                                   x-model="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   placeholder="{{ __('auth_web.new_password_placeholder') }}"
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

                        {{-- 4-segment strength meter (mirrors ResetPasswordRequest rules) --}}
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
                        <p id="password-hint" class="mt-2 text-xs text-[color:var(--sx-muted)]">{{ __('auth_web.password_hint') }}</p>
                    </div>

                    {{-- Confirm new password --}}
                    <div>
                        <label for="password_confirmation" class="sx-label">{{ __('auth_web.confirm_new_password') }} <span class="sx-req" aria-hidden="true">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </span>
                            <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                                   :type="showConfirm ? 'text' : 'password'"
                                   placeholder="{{ __('auth_web.confirm_new_password_placeholder') }}"
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

                    <button type="submit" :disabled="loading"
                            class="sx-btn is-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <i data-lucide="shield-check" aria-hidden="true"></i>
                            {{ __('auth_web.reset_cta') }}
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                            {{ __('auth_web.submitting') }}
                        </span>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm">
                    <a href="{{ route('public.login') }}"
                       class="inline-flex min-h-11 items-center gap-1.5 font-bold text-[color:var(--sx-accent)] hover:underline">
                        <i data-lucide="{{ $backArrow }}" class="w-4 h-4" aria-hidden="true"></i>
                        {{ __('auth_web.back_to_login') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function resetPasswordForm() {
        return {
            password: '',
            showPassword: false,
            showConfirm: false,
            loading: false,

            // 4-segment scoring mirrors the ResetPasswordRequest rules
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
