@extends('layouts.public')

@section('title', __('auth_web.verify_title') . ' — ' . __('site.site_name'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="sx-wrap py-10 sm:py-16">
    <div class="sx-surface sx-empty mx-auto max-w-xl" x-data="{ sending: false }">

        <span class="sx-empty-icon"><i data-lucide="mail-check" aria-hidden="true"></i></span>

        <h1 class="text-2xl font-extrabold text-[color:var(--sx-ink)]">{{ __('auth_web.verify_heading') }}</h1>
        <p class="max-w-md text-[color:var(--sx-muted)] leading-relaxed">{{ __('auth_web.verify_subheading') }}</p>
        <p class="max-w-full truncate font-bold text-[color:var(--sx-ink)]" dir="ltr" title="{{ auth()->user()->email }}">
            {{ auth()->user()->email }}
        </p>

        @if (session('toast'))
            <div class="sx-note w-full text-start" role="status">
                <i data-lucide="mail-check" class="w-5 h-5 mt-1" aria-hidden="true"></i>
                <p class="text-sm break-words">{{ session('toast') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('public.verification.send') }}" @submit="sending = true" class="w-full">
            @csrf
            <button type="submit" :disabled="sending"
                    class="sx-btn is-primary w-full disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="!sending" class="inline-flex items-center gap-2">
                    <i data-lucide="send" aria-hidden="true"></i>
                    {{ __('auth_web.verify_resend_cta') }}
                </span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-2">
                    <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                    {{ __('auth_web.submitting') }}
                </span>
            </button>
        </form>

        <a href="{{ route('public.profile') }}"
           class="inline-flex min-h-11 items-center gap-1.5 text-sm font-bold text-[color:var(--sx-accent)] hover:underline">
            {{ __('auth_web.verify_back_to_profile') }}
        </a>
    </div>
</div>
@endsection
