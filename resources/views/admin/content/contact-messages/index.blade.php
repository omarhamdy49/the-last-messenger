@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_contact_messages') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2 flex items-center gap-3">
                {{ __('admin.content_contact_messages') }}
                @if ($unreadCount > 0)
                    <span class="px-3 py-1 rounded-full text-sm font-bold bg-red-500 text-white">{{ $unreadCount }}</span>
                @endif
            </h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_contact_messages_hint') }}</p>
        </div>
    </div>

    @include('admin.content._flash')

    {{-- Read-state filter --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach (['all' => __('admin.content_filter_all'), 'unread' => __('admin.content_filter_unread'), 'read' => __('admin.content_filter_read')] as $filterKey => $filterLabel)
            <a href="{{ route('admin.content.contact-messages.index', $filterKey === 'all' ? [] : ['filter' => $filterKey]) }}"
               class="px-4 py-2 rounded-lg font-semibold transition-all {{ $activeFilter === $filterKey ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
               @if ($activeFilter !== $filterKey) :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                {{ $filterLabel }}
                @if ($filterKey === 'unread' && $unreadCount > 0)
                    <span class="ms-1 text-xs">({{ $unreadCount }})</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($messages->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_messages_empty') }}</p>
                @if ($activeFilter !== 'all')
                    <a href="{{ route('admin.content.contact-messages.index') }}"
                       class="mt-3 inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_reset_filter') }}</span>
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_sender') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_subject') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_received_at') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr class="border-b last:border-0 transition-colors {{ $message->is_read ? '' : 'font-semibold' }}"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @unless ($message->is_read)
                                            <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" title="{{ __('admin.content_unread') }}"></span>
                                        @endunless
                                        <div class="min-w-0">
                                            <p class="truncate max-w-[12rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $message->name }}</p>
                                            <p class="text-xs truncate max-w-[12rem]" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $message->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.content.contact-messages.show', $message->id) }}"
                                       class="hover:text-accent transition-colors truncate inline-block max-w-[20rem]"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                        {{ \Illuminate\Support\Str::limit($message->subject, 60) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $message->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($message->is_read)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400">{{ __('admin.content_read') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500">{{ __('admin.content_unread') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('admin.content.contact-messages.show', $message->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.view') }}">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @if ($message->is_read)
                                            <form method="POST" action="{{ route('admin.content.contact-messages.mark-unread', $message->id) }}">
                                                @csrf
                                                <button type="submit" class="p-2 rounded-lg transition-colors hover:text-accent"
                                                        :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                                        title="{{ __('admin.content_mark_unread') }}">
                                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.content.contact-messages.mark-read', $message->id) }}">
                                                @csrf
                                                <button type="submit" class="p-2 rounded-lg transition-colors hover:text-accent"
                                                        :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                                        title="{{ __('admin.content_mark_read') }}">
                                                    <i data-lucide="mail-open" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.content.contact-messages.destroy', $message->id) }}"
                                              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-colors"
                                                    title="{{ __('admin.delete') }}">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
