@extends('layouts.public')

@section('title', __('site.contact_title') . ' — ' . __('site.site_name'))
@section('meta_description', __('site.contact_subtitle'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-label="__('site.contact')" eyebrow-icon="mail"
            :title="__('site.contact_title')" :lead="__('site.contact_subtitle')" />

        <div class="sx-surface max-w-2xl p-6 sm:p-8" x-data="{ submitting: false }">

            {{-- Validation errors --}}
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
                    <label for="name" class="sx-label">{{ __('site.contact_name') }} <span class="sx-req" aria-hidden="true">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </span>
                        <input id="name" name="name" type="text" required maxlength="100"
                               value="{{ old('name') }}"
                               placeholder="{{ __('site.contact_name_placeholder') }}"
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                               class="sx-input ps-11 text-base sm:text-sm">
                    </div>
                    @error('name')
                        <p id="name-error" class="sx-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="sx-label">{{ __('site.contact_email') }} <span class="sx-req" aria-hidden="true">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </span>
                        <input id="email" name="email" type="email" required maxlength="255" autocomplete="email"
                               value="{{ old('email') }}"
                               placeholder="{{ __('site.contact_email_placeholder') }}"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                               class="sx-input ps-11 text-base sm:text-sm">
                    </div>
                    @error('email')
                        <p id="email-error" class="sx-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subject --}}
                <div>
                    <label for="subject" class="sx-label">{{ __('site.contact_subject') }} <span class="sx-req" aria-hidden="true">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-[color:var(--sx-muted)]">
                            <i data-lucide="tag" class="w-5 h-5"></i>
                        </span>
                        <input id="subject" name="subject" type="text" required maxlength="150"
                               value="{{ old('subject') }}"
                               placeholder="{{ __('site.contact_subject_placeholder') }}"
                               @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror
                               class="sx-input ps-11 text-base sm:text-sm">
                    </div>
                    @error('subject')
                        <p id="subject-error" class="sx-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Message --}}
                <div>
                    <label for="message" class="sx-label">{{ __('site.contact_message') }} <span class="sx-req" aria-hidden="true">*</span></label>
                    <textarea id="message" name="message" rows="6" required maxlength="200"
                              placeholder="{{ __('site.contact_message_placeholder') }}"
                              @error('message') aria-invalid="true" aria-describedby="message-error message-hint" @else aria-describedby="message-hint" @enderror
                              class="sx-input resize-y py-3 text-base sm:text-sm">{{ old('message') }}</textarea>
                    <p id="message-hint" class="mt-1.5 text-xs text-[color:var(--sx-muted)]">{{ __('site.contact_message_charset') }}</p>
                    @error('message')
                        <p id="message-error" class="sx-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit (disabled + spinner while pending) --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="submitting"
                            class="sx-btn is-primary disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!submitting" class="inline-flex items-center gap-2">
                            <i data-lucide="send" aria-hidden="true"></i>
                            {{ __('site.contact_send') }}
                        </span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                            <i data-lucide="loader-2" class="animate-spin" aria-hidden="true"></i>
                            {{ __('site.contact_sending') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
