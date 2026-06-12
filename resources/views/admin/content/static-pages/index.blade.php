@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_static_pages') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_static_pages') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_static_pages_hint') }}</p>
        </div>
        <a href="{{ route('admin.content.static-pages.create') }}"
           class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.content_page_add') }}</span>
        </a>
    </div>

    @include('admin.content._flash')

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($pages->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_pages_empty') }}</p>
                <a href="{{ route('admin.content.static-pages.create') }}"
                   class="inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>{{ __('admin.content_page_add') }}</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.title') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.slug') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_published_at') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr class="border-b last:border-0 transition-colors"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <span class="font-semibold truncate inline-block max-w-[20rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $page->getTranslation('title', 'ar') ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $page->slug }}
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $page->published_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($page->isPublished())
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">{{ __('admin.published') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-500/10 text-yellow-500">{{ __('admin.draft') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('admin.content.static-pages.edit', $page->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.edit') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.content.static-pages.destroy', $page->id) }}"
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
        @endif
    </div>
</div>
@endsection
