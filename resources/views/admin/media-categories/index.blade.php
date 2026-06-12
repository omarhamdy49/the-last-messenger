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
@php
    $defaultIcons = ['seerah' => 'landmark', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'];
    $visibleCount = $categories->where('is_featured', true)->count();
    $totalPrograms = $categories->sum('programs_count');
    $emptyCount = $categories->where('programs_count', 0)->count();
@endphp

<div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-admin.stats-card icon="layout-grid" :title="__('admin.total_categories')" :value="$categories->count()" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="eye" :title="__('admin.visible_categories')" :value="$visibleCount" :compact="true" />
        <x-admin.stats-card icon="tv" :title="__('admin.total_programs')" :value="number_format($totalPrograms)" :compact="true" />
        <x-admin.stats-card icon="folder-x" :title="__('admin.empty_categories')" :value="$emptyCount" :compact="true" />
    </div>

    @if ($categories->isEmpty())
    <div class="rounded-2xl border py-16 text-center" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <i data-lucide="layout-grid" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
        <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            {{ __('admin.no_data_message') }}
        </p>
    </div>
    @else
    <p class="mb-3 text-xs flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
        <i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i>
        {{ __('admin.drag_to_reorder_categories') }}
    </p>

    <!-- Category Cards -->
    <div id="categories-sortable" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($categories as $category)
        @php $cardColor = $category->color; @endphp
        <div data-key="{{ $category->key }}"
             class="rounded-2xl border p-5 transition-all hover:shadow-lg animate-fadeInUp"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="flex items-start gap-4">
                <button type="button" class="category-drag-handle mt-1 p-1 cursor-grab active:cursor-grabbing rounded-lg"
                        :class="darkMode ? 'text-gray-600 hover:text-gray-400' : 'text-gray-300 hover:text-gray-500'"
                        aria-label="{{ __('admin.drag_to_reorder_categories') }}">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </button>

                <a href="{{ route('admin.media-categories.show', $category->key) }}" class="flex items-start gap-3 flex-1 min-w-0 group/cat">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow transition-transform group-hover/cat:scale-105 {{ $cardColor ? '' : 'bg-gradient-to-br from-[#0056b3] to-[#218838]' }}"
                         @if($cardColor) style="background-color: {{ $cardColor }}" @endif>
                        <i data-lucide="{{ $category->icon ?: ($defaultIcons[$category->key] ?? 'layout-grid') }}" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold truncate transition-colors group-hover/cat:text-[#0056b3]"
                           :class="darkMode ? 'text-white group-hover/cat:!text-[#64CCC5]' : 'text-gray-900'">
                            {{ $category->localizedName() }}
                        </p>
                        <p class="text-xs mt-0.5 flex items-center gap-2 flex-wrap">
                            <code class="px-1.5 py-0.5 rounded" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">{{ $category->key }}</code>
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                {{ number_format($category->programs_count) }} {{ __('admin.media_programs') }}
                            </span>
                        </p>
                    </div>
                </a>

                {{-- Visibility toggle --}}
                <form method="POST" action="{{ route('admin.media-categories.update', $category->key) }}"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_featured" value="{{ $category->is_featured ? 0 : 1 }}">
                    <button type="submit" :disabled="submitting"
                            role="switch" aria-checked="{{ $category->is_featured ? 'true' : 'false' }}"
                            aria-label="{{ __('admin.show_on_programs_list') }}: {{ $category->localizedName() }}"
                            title="{{ __('admin.show_on_programs_list') }}"
                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors disabled:opacity-60
                                   {{ $category->is_featured ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : '' }}"
                            @unless($category->is_featured) :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'" @endunless>
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform
                                     {{ $category->is_featured ? 'ltr:translate-x-6 rtl:-translate-x-6' : 'ltr:translate-x-1 rtl:-translate-x-1' }}"></span>
                    </button>
                </form>
            </div>

            @if($category->localizedDescription())
            <p class="mt-3 text-sm line-clamp-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ $category->localizedDescription() }}
            </p>
            @endif

            <div class="mt-4 pt-3 border-t flex items-center justify-between gap-2"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <span class="text-xs flex items-center gap-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>{{ number_format($category->total_views) }}
                </span>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('admin.media-categories.show', $category->key) }}"
                       class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.view') }}"
                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                    <button type="button"
                            class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.edit') }}"
                            :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'"
                            @click="$dispatch('open-category-edit', @js([
                                'key' => $category->key,
                                'name' => $category->name,
                                'description' => $category->description,
                                'icon' => $category->icon,
                                'color' => $category->color,
                                'display_order' => $category->display_order,
                            ]))">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    @if($category->programs_count === 0)
                    <form method="POST" action="{{ route('admin.media-categories.destroy', $category->key) }}"
                          onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete_category') }}' })" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.delete') }}"
                                :class="darkMode ? 'hover:bg-gray-700 text-red-400' : 'hover:bg-red-50 text-red-600'">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                    @else
                    <span class="p-1.5 rounded-lg cursor-not-allowed opacity-40" title="{{ __('admin.category_has_programs') }}"
                          :class="darkMode ? 'text-red-400' : 'text-red-600'">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@include('admin.media-categories._edit-modal')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('categories-sortable');
    if (!grid || typeof Sortable === 'undefined') return;

    new Sortable(grid, {
        animation: 150,
        handle: '.category-drag-handle',
        ghostClass: 'opacity-40',
        onEnd: async () => {
            const keys = Array.from(grid.querySelectorAll('[data-key]')).map(el => el.dataset.key);
            try {
                const response = await fetch('{{ route('admin.media-categories.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ keys }),
                });
                if (!response.ok) throw new Error('reorder failed');
                window.showToast(@json(__('admin.order_saved')), 'success');
            } catch (e) {
                window.showToast(@json(__('admin.something_went_wrong')), 'error');
            }
        },
    });
});
</script>
@endpush
@endsection
