@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.media-categories.index') }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="layout-grid" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.category_details') }}
        </h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <button type="button"
                class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                @click="$dispatch('open-category-edit', @js([
                    'key' => $category->key,
                    'name' => $category->name,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'display_order' => $category->display_order,
                    'redirect' => 'show',
                ]))">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            {{ __('admin.edit') }}
        </button>
    </div>
</div>
@endsection

@section('content')
@php
    $defaultIcons = ['seerah' => 'landmark', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'];
    $cardColor = $category->color;
@endphp

<!-- Hero -->
<div class="relative rounded-2xl overflow-hidden mb-6 animate-fadeInUp {{ $cardColor ? '' : 'bg-gradient-to-r from-[#0056b3] to-[#218838]' }}"
     @if($cardColor) style="background-color: {{ $cardColor }}" @endif>
    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
    <div class="relative p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-5">
        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center bg-white/15 backdrop-blur-sm ring-2 ring-white/30 flex-shrink-0">
            <i data-lucide="{{ $category->icon ?: ($defaultIcons[$category->key] ?? 'layout-grid') }}" class="w-9 h-9 sm:w-11 sm:h-11 text-white"></i>
        </div>
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-white drop-shadow-lg truncate">
                {{ $category->localizedName() }}
            </h2>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                <code class="px-2 py-0.5 rounded-full font-mono bg-black/30 text-white backdrop-blur-sm">{{ $category->key }}</code>
                <span class="px-2 py-0.5 rounded-full flex items-center gap-1 bg-white/15 text-white backdrop-blur-sm border border-white/10">
                    <i data-lucide="tv" class="w-3 h-3"></i>{{ number_format($category->programs_count) }} {{ __('admin.media_programs') }}
                </span>
                <span class="px-2 py-0.5 rounded-full flex items-center gap-1 bg-white/15 text-white backdrop-blur-sm border border-white/10">
                    <i data-lucide="eye" class="w-3 h-3"></i>{{ number_format($category->total_views) }} {{ __('admin.views') }}
                </span>
                @if($category->is_featured)
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/85 text-white backdrop-blur-sm">{{ __('admin.visible_on_site') }}</span>
                @else
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500/80 text-white backdrop-blur-sm">{{ __('admin.hidden_from_site') }}</span>
                @endif
            </div>
            @if($category->localizedDescription())
            <p class="mt-3 text-sm text-white/85 max-w-2xl">{{ $category->localizedDescription() }}</p>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Assigned programs -->
    <div class="lg:col-span-2">
        <div class="rounded-2xl border overflow-hidden animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <h3 class="text-lg font-semibold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="tv" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.assigned_programs') }}
                    <span class="text-sm font-normal" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">({{ number_format($programs->total()) }})</span>
                </h3>
                <a href="{{ route('admin.media-programs.create') }}"
                   class="px-4 py-1.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('admin.add_program') }}
                </a>
            </div>

            @if($programs->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                <i data-lucide="tv" class="w-12 h-12 mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                <p class="font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.no_programs_found') }}
                </p>
            </div>
            @else
            <ul class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                @foreach($programs as $program)
                <li class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                    <div class="px-6 py-3 flex items-center gap-3">
                        <a href="{{ route('admin.media-programs.show', $program->id) }}" class="flex items-center gap-3 flex-1 min-w-0 group/prog">
                            @if($program->poster_thumb_url)
                            <img src="{{ $program->poster_thumb_url }}" loading="lazy"
                                 class="w-10 h-10 rounded-lg object-cover flex-shrink-0 shadow-sm transition-transform group-hover/prog:scale-105"
                                 alt="{{ $program->getTranslation('title', 'ar') ?? $program->slug }}">
                            @else
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0">
                                <i data-lucide="tv" class="w-4 h-4 text-white"></i>
                            </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold truncate transition-colors group-hover/prog:text-[#0056b3]"
                                   :class="darkMode ? 'text-white group-hover/prog:!text-[#64CCC5]' : 'text-gray-900'">
                                    {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
                                </p>
                                <p class="text-xs flex items-center gap-2" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    @if($program->year)<span>{{ $program->year }}</span>@endif
                                    <span class="flex items-center gap-0.5"><i data-lucide="eye" class="w-3 h-3"></i>{{ number_format($program->views_count) }}</span>
                                </p>
                            </div>
                        </a>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @switch($program->publishStatus())
                                @case('published')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('admin.published') }}</span>
                                    @break
                                @case('scheduled')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ __('admin.scheduled') }}</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">{{ __('admin.draft') }}</span>
                            @endswitch
                            <a href="{{ route('admin.media-programs.edit', $program->id) }}"
                               class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.edit') }}"
                               :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>

            @if($programs->hasPages())
            <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                {{ $programs->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Details -->
        <div class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.category_details') }}
            </h3>
            <dl class="space-y-3">
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="key-round" class="w-3.5 h-3.5"></i>{{ __('admin.category_key') }}
                    </dt>
                    <dd class="text-sm font-mono" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $category->key }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="list-ordered" class="w-3.5 h-3.5"></i>{{ __('admin.display_order') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $category->display_order }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="tv" class="w-3.5 h-3.5"></i>{{ __('admin.media_programs') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($category->programs_count) }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>{{ __('admin.views') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($category->total_views) }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>{{ __('admin.show_on_programs_list') }}
                    </dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $category->is_featured ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $category->is_featured ? __('admin.yes') : __('admin.no') }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Actions -->
        <div class="space-y-3 animate-fadeInUp">
            <form method="POST" action="{{ route('admin.media-categories.update', $category->key) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect" value="show">
                <input type="hidden" name="is_featured" value="{{ $category->is_featured ? 0 : 1 }}">
                <button type="submit"
                        class="w-full py-2.5 rounded-xl font-semibold border transition-colors flex items-center justify-center gap-2"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="{{ $category->is_featured ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                    {{ $category->is_featured ? __('admin.hide_from_site') : __('admin.show_on_site') }}
                </button>
            </form>

            @if($category->programs_count === 0)
            <form method="POST" action="{{ route('admin.media-categories.destroy', $category->key) }}"
                  onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete_category') }}' })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full py-2.5 rounded-xl font-semibold text-red-600 border border-red-300 hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    {{ __('admin.delete') }}
                </button>
            </form>
            @else
            <button type="button" disabled title="{{ __('admin.category_has_programs') }}"
                    class="w-full py-2.5 rounded-xl font-semibold text-red-600/50 border border-red-200 cursor-not-allowed flex items-center justify-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('admin.delete') }}
            </button>
            <p class="text-xs text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                {{ __('admin.category_has_programs') }}
            </p>
            @endif
        </div>
    </div>
</div>

@include('admin.media-categories._edit-modal')
@endsection
