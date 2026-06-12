@extends('layouts.public')

@section('title', __('site.contact_title') . ' — ' . __('site.site_name'))
@section('meta_description', __('site.contact_subtitle'))

@section('content')
<section class="relative py-16 sm:py-20 overflow-hidden">
    {{-- Decorative brand backdrop (matches auth pages) --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-10 end-10 w-72 h-72 bg-gradient-to-br from-[#0056b3]/10 to-[#218838]/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 start-10 w-96 h-96 bg-gradient-to-br from-[#218838]/10 to-[#64CCC5]/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            {{-- Header --}}
            <header class="text-center mb-10 animate-fade-in-up motion-reduce:animate-none">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-2xl flex items-center justify-center shadow-lg">
                    <i data-lucide="mail" class="w-8 h-8 text-white"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-black bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                    {{ __('site.contact_title') }}
                </h1>
                <p class="mt-3 text-gray-600 dark:text-gray-300">{{ __('site.contact_subtitle') }}</p>
            </header>

            <div class="bg-white dark:bg-[#16213e] rounded-2xl shadow-xl p-6 sm:p-10 animate-fade-in-up motion-reduce:animate-none"
                 x-data="{ submitting: false }">

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

                <form method="POST" action="{{ route('public.contact.submit') }}"
                      @submit="submitting = true" class="space-y-5">
                    @csrf

                    {{-- Honeypot — hidden from humans, bots fill it and get rejected --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-semibold mb-2">{{ __('site.contact_name') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </span>
                            <input id="name" name="name" type="text" required maxlength="100"
                                   value="{{ old('name') }}"
                                   placeholder="{{ __('site.contact_name_placeholder') }}"
                                   class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border bg-white dark:bg-[#1a1a2e] border-gray-300 dark:border-gray-700 focus:border-[#0056b3] focus:ring-2 focus:ring-[#0056b3]/20 outline-none transition @error('name') border-red-400 @enderror">
                        </div>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-2">{{ __('site.contact_email') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </span>
                            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email"
                                   value="{{ old('email') }}"
                                   placeholder="{{ __('site.contact_email_placeholder') }}"
                                   class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border bg-white dark:bg-[#1a1a2e] border-gray-300 dark:border-gray-700 focus:border-[#0056b3] focus:ring-2 focus:ring-[#0056b3]/20 outline-none transition @error('email') border-red-400 @enderror">
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Subject --}}
                    <div>
                        <label for="subject" class="block text-sm font-semibold mb-2">{{ __('site.contact_subject') }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 pointer-events-none">
                                <i data-lucide="tag" class="w-5 h-5"></i>
                            </span>
                            <input id="subject" name="subject" type="text" required maxlength="200"
                                   value="{{ old('subject') }}"
                                   placeholder="{{ __('site.contact_subject_placeholder') }}"
                                   class="w-full ps-11 pe-4 py-3 text-base sm:text-sm rounded-xl border bg-white dark:bg-[#1a1a2e] border-gray-300 dark:border-gray-700 focus:border-[#0056b3] focus:ring-2 focus:ring-[#0056b3]/20 outline-none transition @error('subject') border-red-400 @enderror">
                        </div>
                        @error('subject')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Message --}}
                    <div>
                        <label for="message" class="block text-sm font-semibold mb-2">{{ __('site.contact_message') }}</label>
                        <textarea id="message" name="message" rows="6" required maxlength="5000"
                                  placeholder="{{ __('site.contact_message_placeholder') }}"
                                  class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border bg-white dark:bg-[#1a1a2e] border-gray-300 dark:border-gray-700 focus:border-[#0056b3] focus:ring-2 focus:ring-[#0056b3]/20 outline-none transition resize-y @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit (disabled + spinner while pending) --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                                :disabled="submitting"
                                :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-xl hover:scale-[1.02]'"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold px-8 py-3 rounded-xl shadow-lg transition-all">
                            <span x-show="!submitting" class="inline-flex items-center gap-2">
                                <i data-lucide="send" class="w-5 h-5"></i>
                                {{ __('site.contact_send') }}
                            </span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                {{ __('site.contact_sending') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
