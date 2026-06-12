@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('admin.content.contact-messages.index') }}" class="hover:text-accent transition-colors">{{ __('admin.content_contact_messages') }}</a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">#{{ $message->id }}</span>
    </nav>

    @include('admin.content._flash')

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h1 class="text-2xl font-bold mb-3 break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $message->subject }}
            </h1>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                <span class="flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-accent"></i>
                    {{ $message->name }}
                </span>
                <a href="mailto:{{ $message->email }}" class="flex items-center gap-2 hover:text-accent transition-colors" dir="ltr">
                    <i data-lucide="mail" class="w-4 h-4 text-accent"></i>
                    {{ $message->email }}
                </a>
                <span class="flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-accent"></i>
                    {{ $message->created_at->format('Y-m-d H:i') }}
                </span>
                @if ($message->read_at)
                    <span class="flex items-center gap-2">
                        <i data-lucide="mail-open" class="w-4 h-4 text-accent"></i>
                        {{ __('admin.content_read_at', ['time' => $message->read_at->format('Y-m-d H:i')]) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="p-6">
            <p class="whitespace-pre-wrap break-words leading-relaxed" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">{{ $message->body }}</p>
        </div>

        <div class="p-6 border-t flex flex-wrap items-center justify-between gap-3"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <a href="{{ route('admin.content.contact-messages.index') }}"
               class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 border-2"
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'right' : 'left' }}" class="w-5 h-5"></i>
                <span>{{ __('admin.back') }}</span>
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.content.contact-messages.mark-unread', $message->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 border-2"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                        <span>{{ __('admin.content_mark_unread') }}</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.content.contact-messages.destroy', $message->id) }}"
                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-6 py-3 rounded-lg font-semibold bg-red-500 text-white hover:bg-red-600 transition-all flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                        <span>{{ __('admin.delete') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
