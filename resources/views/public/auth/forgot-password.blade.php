@extends('layouts.public')

@section('title', __('auth_web.forgot_title') . ' — ' . __('site.site_name'))

@section('content')
@php $backArrow = in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'arrow-right' : 'arrow-left'; @endphp
<div class="sx-wrap py-10 sm:py-16">
    <div class="sx-surface mx-auto max-w-5xl overflow-hidden">
        <div class="grid md:grid-cols-2">

            {{-- Reassurance column: the brand gradient plate, hidden on phones --}}
            <aside class="hidden flex-col justify-center gap-7 bg-[image:var(--sx-grad)] p-10 text-white md:flex lg:p-12">
                <span class="grid h-16 w-16 place-items-center rounded-2xl border border-white/25 bg-[rgb(255_255_255/0.15)]">
                    <i data-lucide="key-round" class="h-8 w-8"></i>
                </span>
                <div>
                    <h2 class="text-3xl font-extrabold leading-tight lg:text-4xl">{{ __('auth_web.forgot_heading') }}</h2>
                    <p class="mt-3 text-lg leading-relaxed text-white/90">{{ __('auth_web.forgot_subheading') }}</p>
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
                    <li class="flex items-center gap-4 rounded-2xl border border-white/20 bg-[rgb(255_255_255/0.10)] p-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[rgb(255_255_255/0.20)]">
                            <i data-lucide="mail-check" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <h3 class="font-bold">{{ __('auth_web.benefit_content_title') }}</h3>
                            <p class="text-sm text-white/80">{{ __('auth_web.benefit_content_desc') }}</p>
                        </div>
                    </li>
                </ul>
            </aside>

            {{-- Form column --}}
            <div class="p-7 sm:p-10 lg:p-12" x-data="{ loading: false }">

                <h1 class="text-2xl font-extrabold text-[color:var(--sx-ink)]">{{ __('auth_web.forgot_title') }}</h1>
                <p class="mt-1.5 mb-7 text-sm text-[color:var(--sx-muted)]">{{ __('auth_web.forgot_subheading') }}</p>

                {{-- Broker confirmation ("a reset link has been sent") --}}
                @if (session('toast'))
                    <div class="sx-note mb-6" role="status">
                        <i data-lucide="mail-check" class="w-5 h-5 mt-1" aria-hidden="true"></i>
                        <p class="text-sm">{{ session('toast') }}</p>
                    </div>
                @endif

                {{-- Error summary (kept alongside the inline error: throttling
                     reports against the request, not the field). --}}
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

                <form method="POST" action="{{ route('password.email') }}"
                      @submit="loading = true" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="sx-label">{{ __('auth_web.forgot_email_label') }} <span class="sx-req" aria-hidden="true">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </span>
                            <input id="email" name="email" type="email" required autocomplete="email" autofocus
                                   value="{{ old('email') }}"
                                   placeholder="{{ __('auth_web.email_placeholder') }}"
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                   class="sx-input ps-11 text-base sm:text-sm">
                        </div>
                        @error('email')
                            <p id="email-error" class="sx-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" :disabled="loading"
                            class="sx-btn is-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <i data-lucide="send" aria-hidden="true"></i>
                            {{ __('auth_web.forgot_cta') }}
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                            {{ __('auth_web.submitting') }}
                        </span>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-[color:var(--sx-muted)]">
                    {{ __('auth_web.forgot_help') }}
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
