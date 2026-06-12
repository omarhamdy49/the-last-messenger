@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="layout-grid" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.media_categories') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('admin.manage_media_categories') }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.media-programs.index') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:text-white' : 'border-gray-300 text-gray-700 hover:text-gray-900'">
            <i data-lucide="tv" class="w-5 h-5"></i>
            <span>{{ __('admin.media_programs') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    @if ($categories->isEmpty())
        <div class="py-16 text-center">
            <i data-lucide="layout-grid" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('admin.no_data_message') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start border-b" :class="darkMode ? 'border-gray-800 text-gray-400' : 'border-gray-200 text-gray-600'">
                        <th class="px-4 sm:px-6 py-4 text-start font-semibold">{{ __('admin.category') }}</th>
                        <th class="px-4 sm:px-6 py-4 text-start font-semibold">{{ __('admin.programs_count') }}</th>
                        <th class="px-4 sm:px-6 py-4 text-start font-semibold">{{ __('admin.show_on_programs_list') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="border-b last:border-0 transition-colors"
                            :class="darkMode ? 'border-gray-800 hover:bg-white/5' : 'border-gray-100 hover:bg-gray-50'">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('media.public.category_'.$category->key) }}
                                    </span>
                                    <code class="text-xs px-2 py-0.5 rounded"
                                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">{{ $category->key }}</code>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ number_format($category->programs_count) }}
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <form method="POST" action="{{ route('admin.media-categories.update', $category->key) }}"
                                      x-data="{ submitting: false }" @submit="submitting = true">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_featured" value="{{ $category->is_featured ? 0 : 1 }}">
                                    <button type="submit" :disabled="submitting"
                                            role="switch" aria-checked="{{ $category->is_featured ? 'true' : 'false' }}"
                                            aria-label="{{ __('admin.show_on_programs_list') }}: {{ __('media.public.category_'.$category->key) }}"
                                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors disabled:opacity-60
                                                   {{ $category->is_featured ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : '' }}"
                                            @unless($category->is_featured) :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'" @endunless>
                                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform
                                                     {{ $category->is_featured ? 'ltr:translate-x-6 rtl:-translate-x-6' : 'ltr:translate-x-1 rtl:-translate-x-1' }}"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
