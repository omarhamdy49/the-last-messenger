@extends('layouts.admin')

@section('content')
@php($rtl = in_array(app()->getLocale(), ['ar', 'ur']))
<div class="w-full">
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.quran.ai-inquiries.index') }}" class="hover:text-accent transition-colors inline-flex items-center gap-1">
            <i data-lucide="{{ $rtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i> {{ __('admin.quran_ai_inquiries') }}
        </a>
    </nav>

    @if (session('success'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm bg-green-100 text-green-800 border border-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm bg-red-100 text-red-800 border border-red-200">{{ session('error') }}</div>
    @endif

    {{-- Metadata --}}
    <div class="rounded-2xl border p-5 mb-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            <div>
                <span class="opacity-60">{{ __('admin.quran_ai_user') }}:</span>
                @if ($inquiry->user)
                    <a href="{{ route('admin.users.show', $inquiry->user_id) }}" class="text-accent font-semibold hover:underline">{{ $inquiry->user->name }}</a>
                @else — @endif
            </div>
            <div><span class="opacity-60">{{ __('admin.quran_ai_source') }}:</span> {{ $inquiry->source === 'cache' ? __('admin.quran_ai_source_cache') : __('admin.quran_ai_source_llm') }}</div>
            <div><span class="opacity-60">{{ __('admin.quran_ai_cost') }}:</span> ${{ number_format((float) $inquiry->cost_usd, 4) }}</div>
            <div><span class="opacity-60">{{ __('admin.quran_ai_tokens') }}:</span> {{ $inquiry->input_tokens + $inquiry->output_tokens }}</div>
            <div><span class="opacity-60">{{ __('admin.quran_ai_geo') }}:</span> {{ $inquiry->geo['country'] ?? '—' }}{{ isset($inquiry->geo['city']) ? ' · '.$inquiry->geo['city'] : '' }}</div>
            <div><span class="opacity-60">{{ __('admin.quran_ai_created') }}:</span> {{ $inquiry->created_at?->diffForHumans() }}</div>
        </div>
    </div>

    @include('components.quran.answer-renderer')

    {{-- Premium preview: the answer as users see it, with go-to-verse links --}}
    <div class="rounded-2xl border p-5 mb-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         x-data="{ html: '' }"
         x-init="html = window.renderQuranAnswer(@js($inquiry->answer), @js(url('/quran')), true); $nextTick(() => window.lucide?.createIcons())">
        <p class="text-xs font-semibold uppercase tracking-wider mb-3" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.quran_ai_answer_preview') }}</p>
        <div dir="{{ $rtl ? 'rtl' : 'ltr' }}" class="qa-prose" :class="darkMode ? 'qa-dark text-gray-100' : 'text-gray-800'" x-html="html"></div>

        @if (! empty($verses))
            <div class="mt-4 pt-3 border-t flex flex-wrap gap-1.5" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                @foreach ($verses as $verse)
                    <a href="{{ $verse['url'] }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                       :class="darkMode ? 'bg-gray-800 text-[#64CCC5] border-gray-700' : 'bg-blue-50 text-[#0056b3] border-blue-100'">
                        <i data-lucide="book-open" class="w-3.5 h-3.5"></i>{{ $verse['key'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Edit question + answer (answer in a WYSIWYG editor) --}}
    <form method="POST" action="{{ route('admin.quran.ai-inquiries.answer', $inquiry->id) }}"
          class="rounded-2xl border p-5 mb-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @csrf
        @method('PUT')

        <label class="block text-xs font-semibold uppercase tracking-wider mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.quran_ai_question') }}</label>
        <input type="text" name="question" maxlength="500" value="{{ old('question', $inquiry->question) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}"
               class="w-full rounded-lg border px-3 py-2 mb-4 text-base sm:text-sm font-semibold"
               :class="darkMode ? 'bg-gray-950 border-gray-700 text-white' : 'bg-gray-50 border-gray-300 text-gray-900'">
        @error('question')<p class="text-red-500 text-sm -mt-3 mb-3">{{ $message }}</p>@enderror

        <label class="block text-xs font-semibold uppercase tracking-wider mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.quran_ai_answer') }}</label>
        <x-admin.form.wysiwyg name="answer" :value="old('answer', $seedAnswer)" :locale="$inquiry->locale" toolbar="compact" height="260px" />
        @error('answer')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white inline-flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> {{ __('admin.quran_ai_save_answer') }}
            </button>
        </div>
    </form>

    <div class="flex flex-wrap items-start gap-4">
        {{-- Publish as FAQ --}}
        <form method="POST" action="{{ route('admin.quran.ai-inquiries.publish', $inquiry->id) }}"
              class="rounded-2xl border p-5 flex-1 min-w-[280px]" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            @csrf
            <p class="text-sm font-semibold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.quran_ai_publish_faq') }}</p>
            @if ($inquiry->published_faq_id)
                <p class="text-sm text-green-600 inline-flex items-center gap-1"><i data-lucide="check-circle" class="w-4 h-4"></i> {{ __('admin.quran_ai_already_published') }}</p>
            @else
                <div class="flex flex-wrap items-end gap-3">
                    <input type="text" name="category" placeholder="{{ __('admin.quran_ai_category') }}"
                           class="text-base sm:text-sm rounded-lg border px-3 py-2 flex-1 min-w-[140px]"
                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white' : 'bg-gray-50 border-gray-300'">
                    <label class="inline-flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        <input type="checkbox" name="is_active" value="1" class="rounded"> {{ __('admin.quran_ai_active') }}
                    </label>
                    <button type="submit" class="px-4 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white inline-flex items-center gap-2">
                        <i data-lucide="megaphone" class="w-4 h-4"></i> {{ __('admin.quran_ai_publish_faq') }}
                    </button>
                </div>
            @endif
        </form>

        {{-- Delete --}}
        <form method="POST" action="{{ route('admin.quran.ai-inquiries.destroy', $inquiry->id) }}"
              onsubmit="return window.confirmSubmit(this, { message: {{ Js::from(__('admin.quran_ai_delete_confirm')) }} })">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded-lg font-semibold border border-red-300 text-red-600 hover:bg-red-50 inline-flex items-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('admin.quran_ai_delete') }}
            </button>
        </form>
    </div>
</div>
@endsection
