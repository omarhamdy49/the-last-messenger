@extends('layouts.public')

@section('title', __('auth_web.reset_title') . ' — ' . __('site.site_name'))

@section('content')
<section class="relative min-h-[calc(100vh-5rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">

    {{-- Decorative floating shapes --}}
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
                        <i data-lucide="lock-keyhole" class="w-16 h-16 mb-6"></i>
                        <h2 class="text-3xl lg:text-4xl font-bold font-amiri mb-4">{{ __('auth_web.reset_heading') }}</h2>
                        <p class="text-lg text-white/90 mb-10">{{ __('auth_web.reset_subheading') }}</p>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 bg-white/10 backdrop-blur p-4 rounded-2xl">
                                <span class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                                    <i data-lucide="shield-check" class="w-6 h-6"></i>
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
                     x-data="resetPasswordForm()">

                    <h1 class="text-2xl font-bold mb-1">{{ __('auth_web.reset_title') }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-8">{{ __('auth_web.reset_subheading') }}</p>

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

                    <form method="POST" action="{{ route('password.update') }}"
                          @submit="loading = true" class="space-y-5">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-semibold mb-2">{{ __('auth_web.email') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </span>
                                <input id="email" name="email" type="email" required autocomplete="email"
                                       value="{{ old('email', $email) }}"
                                       placeholder="{{ __('auth_web.email_placeholder') }}"
                                       class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
                            </div>
                        </div>

                        {{-- New password --}}
                        <div>
                            <label for="password" class="block text-sm font-semibold mb-2">{{ __('auth_web.new_password') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                    <i data-lucide="lock" class="w-5 h-5"></i>
                                </span>
                                <input id="password" name="password" required autocomplete="new-password"
                                       x-model="password"
                                       :type="showPassword ? 'text' : 'password'"
                                       placeholder="{{ __('auth_web.new_password_placeholder') }}"
                                       class="w-full ps-11 pe-11 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
                                        :aria-label="showPassword ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                                    <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                    <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                                </button>
                            </div>

                            {{-- 4-segment strength meter (mirrors ResetPasswordRequest rules) --}}
                            <div x-show="password.length > 0" x-cloak class="mt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-300">{{ __('auth_web.password_strength') }}</span>
                                    <span class="text-xs font-bold" :class="strengthTextClass" x-text="strengthLabel"></span>
                                </div>
                                <div class="grid grid-cols-4 gap-1.5">
                                    <template x-for="i in 4" :key="i">
                                        <div class="h-1.5 rounded-full transition-colors"
                                             :class="i <= strength ? strengthBarClass : 'bg-gray-200 dark:bg-gray-700'"></div>
                                    </template>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('auth_web.password_hint') }}</p>
                            </div>
                        </div>

                        {{-- Confirm new password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold mb-2">{{ __('auth_web.confirm_new_password') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                    <i data-lucide="lock" class="w-5 h-5"></i>
                                </span>
                                <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                                       :type="showConfirm ? 'text' : 'password'"
                                       placeholder="{{ __('auth_web.confirm_new_password_placeholder') }}"
                                       class="w-full ps-11 pe-11 py-3 text-base sm:text-sm rounded-xl border border-gray-300 dark:border-[#0f3460] bg-white dark:bg-[#1a1a2e] focus:ring-2 focus:ring-[#0056b3] focus:border-transparent outline-none transition">
                                <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
                                        :aria-label="showConfirm ? '{{ __('auth_web.hide_password') }}' : '{{ __('auth_web.show_password') }}'">
                                    <i x-show="!showConfirm" data-lucide="eye" class="w-5 h-5"></i>
                                    <i x-show="showConfirm" data-lucide="eye-off" class="w-5 h-5" x-cloak></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" :disabled="loading"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-white font-bold bg-gradient-to-br from-[#0056b3] to-[#218838] hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed transition">
                            <svg x-show="loading" x-cloak class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-show="!loading">{{ __('auth_web.reset_cta') }}</span>
                            <span x-show="loading" x-cloak>{{ __('auth_web.submitting') }}</span>
                        </button>
                    </form>

                    <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
                        <a href="{{ route('public.login') }}" class="inline-flex items-center gap-1 font-semibold text-[#0056b3] hover:underline">
                            <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                            {{ __('auth_web.back_to_login') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
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
            get strengthBarClass() {
                return ['bg-red-500', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'][this.strength];
            },
            get strengthTextClass() {
                return ['text-red-500', 'text-red-500', 'text-yellow-500', 'text-blue-500', 'text-green-500'][this.strength];
            },
        };
    }
</script>
@endpush
