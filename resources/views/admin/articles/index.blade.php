@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>
            Articles Management
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Manage your articles, posts, and seerah content
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <!-- Filter Toggle Button -->
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                       border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">Filters</span>
        </button>

        <!-- Trash Button -->
        <a href="{{ route('admin.articles.trash') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                  border-2"
           :class="darkMode ?
               'border-orange-700 bg-orange-900/20 text-orange-400 hover:bg-orange-900/40' :
               'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100'">
            <i data-lucide="trash-2" class="w-5 h-5"></i>
            <span class="hidden sm:inline">Trash</span>
        </a>

        <!-- Create New Button -->
        <a href="{{ route('admin.articles.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Create Article</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: false
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="file-text"
            title="Total Articles"
            :value="$stats['total'] ?? 0"
            :gradient="true"
            href="{{ route('admin.articles.index') }}"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="Published"
            :value="$stats['published'] ?? 0"
            change="+{{ $stats['published_change'] ?? 0 }}%"
            changeType="up"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="edit"
            title="Drafts"
            :value="$stats['drafts'] ?? 0"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="clock"
            title="Scheduled"
            :value="$stats['scheduled'] ?? 0"
            delay="delay-300"
        />
    </div>

    <!-- DataTable Component -->
    <x-admin.data-table
        :columns="[
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '80px', 'type' => 'number'],
            ['key' => 'thumb', 'label' => 'Image', 'type' => 'image', 'width' => '100px'],
            ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'truncate' => 50],
            ['key' => 'category', 'label' => 'Category', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'badge' => true],
            ['key' => 'writer', 'label' => 'Author', 'sortable' => true],
            ['key' => 'views', 'label' => 'Views', 'sortable' => true, 'type' => 'number'],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'type' => 'date'],
        ]"
        :data="$articles"
        :actions="['view', 'edit', 'delete', 'duplicate']"
        :bulkActions="['delete', 'publish', 'export']"
        :searchable="true"
        :filterable="true"
        :exportable="true"
        :perPageOptions="[10, 25, 50, 100]"
        emptyTitle="No Articles Found"
        emptyMessage="Get started by creating your first article"
        emptyIcon="file-text"
        editRoute="admin.articles.edit"
        deleteRoute="admin.articles.destroy"
        viewRoute="admin.articles.show"
    >
        Articles List
    </x-admin.data-table>

    <!-- Advanced Filter Sidebar -->
    <x-admin.article-filter-sidebar
        :categories="$categories"
        :authors="$authors"
        :statuses="['published', 'draft', 'scheduled', 'archived']"
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
            if (confirm('Are you sure you want to duplicate this article?')) {
                // In production, this would make an AJAX request
                console.log('Duplicating article:', articleId);
                alert('Article duplicated successfully!');
            }
        };
    });

    // Enhanced DataTable with filter support
    function dataTableWithFilters() {
        return {
            ...dataTable(),
            appliedFilters: {},

            init() {
                // Call parent init
                this.filteredData = [...this.rawData];
                this.updatePagination();
                this.$nextTick(() => lucide.createIcons());

                // Listen for filter events
                this.$watch('appliedFilters', () => {
                    this.applyFiltersToData();
                });
            },

            applyFiltersToData() {
                this.loading = true;

                setTimeout(() => {
                    let filtered = [...this.rawData];

                    // Apply category filter
                    if (this.appliedFilters.category_id) {
                        filtered = filtered.filter(row =>
                            row.category_id == this.appliedFilters.category_id
                        );
                    }

                    // Apply status filter
                    if (this.appliedFilters.status && this.appliedFilters.status.length > 0) {
                        filtered = filtered.filter(row =>
                            this.appliedFilters.status.includes(row.status)
                        );
                    }

                    // Apply author filter
                    if (this.appliedFilters.author_id) {
                        filtered = filtered.filter(row =>
                            row.writer_id == this.appliedFilters.author_id
                        );
                    }

                    // Apply date range filter
                    if (this.appliedFilters.date_from) {
                        filtered = filtered.filter(row =>
                            new Date(row.created_at) >= new Date(this.appliedFilters.date_from)
                        );
                    }
                    if (this.appliedFilters.date_to) {
                        filtered = filtered.filter(row =>
                            new Date(row.created_at) <= new Date(this.appliedFilters.date_to)
                        );
                    }

                    // Apply feature filters
                    if (this.appliedFilters.is_featured) {
                        filtered = filtered.filter(row => row.is_featured);
                    }
                    if (this.appliedFilters.is_pinned) {
                        filtered = filtered.filter(row => row.is_pinned);
                    }

                    this.filteredData = filtered;
                    this.currentPage = 1;
                    this.updatePagination();
                    this.loading = false;
                    this.$nextTick(() => lucide.createIcons());
                }, 200);
            }
        };
    }
</script>
@endpush
