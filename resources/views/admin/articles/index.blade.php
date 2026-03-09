@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('articles.index') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('articles.description') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <!-- Filter Toggle Button -->
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                       border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <!-- Trash Button -->
        <a href="{{ route('admin.articles.trash') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                  border-2"
           :class="darkMode ?
               'border-orange-700 bg-orange-900/20 text-orange-400 hover:bg-orange-900/40' :
               'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100'">
            <i data-lucide="trash-2" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.trash') }}</span>
        </a>

        <!-- Create New Button -->
        <a href="{{ route('admin.articles.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('articles.actions.create_new') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: false
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Row (compact) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-admin.stats-card
            icon="file-text"
            :title="__('articles.stats.total')"
            :value="$stats['total'] ?? 0"
            :gradient="true"
            :compact="true"
        />
        <x-admin.stats-card
            icon="check-circle"
            :title="__('articles.stats.published')"
            :value="$stats['published'] ?? 0"
            :compact="true"
        />
        <x-admin.stats-card
            icon="file-edit"
            :title="__('articles.stats.drafts')"
            :value="$stats['drafts'] ?? 0"
            :compact="true"
        />
        <x-admin.stats-card
            icon="clock"
            :title="__('articles.stats.scheduled')"
            :value="$stats['scheduled'] ?? 0"
            :compact="true"
        />
    </div>

    <!-- DataTable Component -->
    <x-admin.data-table
        :columns="[
            ['key' => 'title', 'type' => 'image_link', 'image_key' => 'thumb', 'label' => __('articles.fields.title'), 'sortable' => true, 'truncate' => 55, 'link' => true],
            ['key' => 'category', 'label' => __('articles.fields.category'), 'sortable' => true],
            ['key' => 'status', 'label' => __('articles.fields.status'), 'badge' => true],
            ['key' => 'views', 'label' => __('articles.fields.views'), 'sortable' => true, 'type' => 'number'],
            ['key' => 'is_featured', 'label' => __('articles.fields.is_featured'), 'type' => 'boolean'],
            ['key' => 'is_pinned', 'label' => __('articles.fields.is_pinned'), 'type' => 'boolean'],
            ['key' => 'created_at', 'label' => __('articles.fields.created_at'), 'sortable' => true, 'type' => 'date'],
        ]"
        :data="$articles"
        :actions="['view', 'edit', 'delete', 'duplicate']"
        :bulkActions="['delete', 'publish', 'archive', 'export']"
        :searchable="true"
        :filterable="true"
        :exportable="true"
        :perPageOptions="[15, 25, 50, 100]"
        :emptyTitle="__('articles.empty.title')"
        :emptyMessage="__('articles.empty.description')"
        emptyIcon="file-text"
        editRoute="admin.articles.edit"
        deleteRoute="admin.articles.destroy"
        viewRoute="admin.articles.show"
    >
        {{ __('articles.list_title') }}
    </x-admin.data-table>

    <!-- Advanced Filter Sidebar -->
    <x-admin.article-filter-sidebar
        :statuses="['published', 'pending', 'draft', 'scheduled', 'archived']"
    />

</div>
@endsection

@push('styles')
<style>
    /* Enhanced table row hover */
    tbody tr {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    tbody tr:hover {
        transform: scale(1.01);
    }

    /* Smooth scroll for filter sidebar */
    [x-cloak] {
        display: none !important;
    }

    /* Custom checkbox and radio styles */
    .form-checkbox,
    .form-radio {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 0.375rem;
        cursor: pointer;
    }

    .form-checkbox:checked,
    .form-radio:checked {
        background-color: #64CCC5;
        border-color: #64CCC5;
    }

    /* Stagger animation delays */
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Lucide icons
        lucide.createIcons();

        // Listen for filter changes
        document.addEventListener('filters-changed', (event) => {
            console.log('Filters changed:', event.detail);
            // In production, this would trigger an AJAX request to filter data
            // For now, we'll just log the filters
        });

        // Handle bulk actions
        window.handleBulkAction = (action, selectedIds) => {
            console.log(`Bulk action: ${action}`, selectedIds);
            // In production, this would trigger an AJAX request
        };

        // Handle row actions
        window.handleRowAction = (action, rowId) => {
            console.log(`Row action: ${action}`, rowId);
            // In production, this would navigate or trigger appropriate action
        };

        // Duplicate article function
        window.duplicateArticle = (articleId) => {
            window.showConfirm({
                title: '{{ __("articles.confirm.duplicate") }}',
                message: '{{ __("admin.delete_warning") }}',
                type: 'warning',
                onConfirm: () => {
                    // In production, this would make an AJAX request
                    console.log('Duplicating article:', articleId);
                    showToast('{{ __("articles.messages.duplicated") }}', 'success');
                }
            });
        };
    });

    // Filtering is handled by the data-table component via @filters-changed.window event.
</script>
@endpush
