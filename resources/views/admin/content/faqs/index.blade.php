@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_faqs') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_faqs') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_faqs_hint') }}</p>
        </div>
        <a href="{{ route('admin.content.faqs.create') }}"
           class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.content_faq_add') }}</span>
        </a>
    </div>

    @include('admin.content._flash')

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($faqs->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="circle-help" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_faqs_empty') }}</p>
                <a href="{{ route('admin.content.faqs.create') }}"
                   class="inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>{{ __('admin.content_faq_add') }}</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_question') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_category') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_sort_order') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($faqs as $faq)
                            <tr class="border-b last:border-0 transition-colors"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <span class="font-semibold truncate inline-block max-w-[24rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ \Illuminate\Support\Str::limit($faq->getTranslation('question', 'ar') ?? '—', 80) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $faq->category ?? '—' }}
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $faq->sort_order }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($faq->is_active)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">{{ __('admin.active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400">{{ __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('admin.content.faqs.edit', $faq->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.edit') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.content.faqs.destroy', $faq->id) }}"
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
