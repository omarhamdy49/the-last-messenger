@extends('layouts.public')

@section('title', __('auth_web.verify_title') . ' — ' . __('site.site_name'))
@section('robots', 'noindex, nofollow')

@section('content')
<section class="relative min-h-[calc(100vh-5rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">

    {{-- Decorative floating shapes (match auth pages) --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-20 end-10 w-72 h-72 bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 rounded-full blur-3xl animate-fade-in-up motion-reduce:animate-none"></div>
        <div class="absolute bottom-20 start-10 w-96 h-96 bg-gradient-to-br from-[#218838]/20 to-[#64CCC5]/20 rounded-full blur-3xl animate-fade-in-up motion-reduce:animate-none"></div>
    </div>

    <div class="relative w-full max-w-xl">
        <div class="glass rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up motion-reduce:animate-none">
            <div class="p-8 sm:p-12 bg-white dark:bg-[#16213e] text-center" x-data="{ sending: false }">

                <span class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white flex items-center justify-center mb-6">
                    <i data-lucide="mail-check" class="w-8 h-8"></i>
                </span>

                <h1 class="text-2xl font-bold mb-2">{{ __('auth_web.verify_heading') }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2 break-words">{{ __('auth_web.verify_subheading') }}</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-8 truncate" dir="ltr">{{ auth()->user()->email }}</p>

                @if (session('toast'))
                    <div class="mb-6 rounded-xl border border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300 flex items-start gap-2 text-start"
                         role="status">
                        <i data-lucide="check-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                        <span class="break-words">{{ session('toast') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('public.verification.send') }}" @submit="sending = true">
                    @csrf
                    <button type="submit" :disabled="sending"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:opacity-90 transition-opacity disabled:opacity-60">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="sending" x-cloak></i>
                        <span x-text="sending ? '{{ __('auth_web.submitting') }}' : '{{ __('auth_web.verify_resend_cta') }}'"></span>
                    </button>
                </form>

                <a href="{{ route('public.profile') }}"
                   class="inline-flex items-center gap-1.5 mt-6 text-sm font-semibold text-primary hover:underline">
                    {{ __('auth_web.verify_back_to_profile') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
