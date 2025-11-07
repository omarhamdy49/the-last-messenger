{{--
/**
 * Articles Index Page - Example with Sample Data
 *
 * This is a working example showing how to integrate all components
 * with sample data for testing and demonstration purposes.
 */
--}}

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
        <button @click="filtersOpen = !filtersOpen"
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">Filters</span>
        </button>

        <!-- Trash Button -->
        <a href="#"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ?
               'border-orange-700 bg-orange-900/20 text-orange-400 hover:bg-orange-900/40' :
               'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100'">
            <i data-lucide="trash-2" class="w-5 h-5"></i>
            <span class="hidden sm:inline">Trash</span>
        </a>

        <!-- Create New Button -->
        <a href="#"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Create Article</span>
        </a>
    </div>
</div>
@endsection

@section('content')
@php
    // Sample data for demonstration
    $sampleArticles = [
        [
            'id' => 1,
            'thumb' => 'https://picsum.photos/seed/1/400/300',
            'title' => 'The Life of Prophet Muhammad (PBUH) - Early Years',
            'category' => 'Seerah',
            'category_id' => 1,
            'status' => 'published',
            'writer' => 'Dr. Ahmad Hassan',
            'writer_id' => 1,
            'views' => 15234,
            'is_featured' => true,
            'is_pinned' => false,
            'created_at' => '2024-01-15T10:30:00Z',
        ],
        [
            'id' => 2,
            'thumb' => 'https://picsum.photos/seed/2/400/300',
            'title' => 'Understanding Tawheed: The Oneness of Allah',
            'category' => 'Aqeedah',
            'category_id' => 2,
            'status' => 'published',
            'writer' => 'Sheikh Yusuf Ali',
            'writer_id' => 2,
            'views' => 8721,
            'is_featured' => false,
            'is_pinned' => true,
            'created_at' => '2024-02-20T14:45:00Z',
        ],
        [
            'id' => 3,
            'thumb' => null,
            'title' => 'The Battle of Badr - A Turning Point in Islamic History',
            'category' => 'History',
            'category_id' => 3,
            'status' => 'draft',
            'writer' => 'Dr. Fatima Zahra',
            'writer_id' => 3,
            'views' => 3456,
            'is_featured' => false,
            'is_pinned' => false,
            'created_at' => '2024-03-10T09:15:00Z',
        ],
        [
            'id' => 4,
            'thumb' => 'https://picsum.photos/seed/4/400/300',
            'title' => 'The Companions of the Prophet: Abu Bakr As-Siddiq',
            'category' => 'Sahaba',
            'category_id' => 4,
            'status' => 'scheduled',
            'writer' => 'Dr. Ahmad Hassan',
            'writer_id' => 1,
            'views' => 12890,
            'is_featured' => true,
            'is_pinned' => false,
            'created_at' => '2024-04-05T16:20:00Z',
        ],
        [
            'id' => 5,
            'thumb' => 'https://picsum.photos/seed/5/400/300',
            'title' => 'Islamic Ethics and Morality in Modern Times',
            'category' => 'Ethics',
            'category_id' => 5,
            'status' => 'published',
            'writer' => 'Sheikh Yusuf Ali',
            'writer_id' => 2,
            'views' => 6543,
            'is_featured' => false,
            'is_pinned' => false,
            'created_at' => '2024-05-12T11:30:00Z',
        ],
        [
            'id' => 6,
            'thumb' => 'https://picsum.photos/seed/6/400/300',
            'title' => 'The Hijrah: Migration from Makkah to Madinah',
            'category' => 'Seerah',
            'category_id' => 1,
            'status' => 'archived',
            'writer' => 'Dr. Fatima Zahra',
            'writer_id' => 3,
            'views' => 19876,
            'is_featured' => false,
            'is_pinned' => false,
            'created_at' => '2024-01-08T08:00:00Z',
        ],
    ];

    $sampleCategories = [
        (object)['id' => 1, 'name' => 'Seerah'],
        (object)['id' => 2, 'name' => 'Aqeedah'],
        (object)['id' => 3, 'name' => 'History'],
        (object)['id' => 4, 'name' => 'Sahaba'],
        (object)['id' => 5, 'name' => 'Ethics'],
        (object)['id' => 6, 'name' => 'Hadith'],
        (object)['id' => 7, 'name' => 'Fiqh'],
    ];

    $sampleAuthors = [
        (object)['id' => 1, 'name' => 'Dr. Ahmad Hassan'],
        (object)['id' => 2, 'name' => 'Sheikh Yusuf Ali'],
        (object)['id' => 3, 'name' => 'Dr. Fatima Zahra'],
        (object)['id' => 4, 'name' => 'Imam Abdullah'],
    ];

    $stats = [
        'total' => 156,
        'published' => 98,
        'drafts' => 42,
        'scheduled' => 16,
        'published_change' => 12,
    ];
@endphp

<div x-data="{ filtersOpen: false }">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-admin.stats-card
            icon="file-text"
            title="Total Articles"
            :value="$stats['total']"
            :gradient="true"
            href="#"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="Published"
            :value="$stats['published']"
            change="+{{ $stats['published_change'] }}%"
            changeType="up"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="edit"
            title="Drafts"
            :value="$stats['drafts']"
            delay="delay-200"
        />

        <x-admin.stats-card
            icon="clock"
            title="Scheduled"
            :value="$stats['scheduled']"
            delay="delay-300"
        />
    </div>

    <!-- Status Badge Examples (Hidden - for reference) -->
    <div class="hidden mb-8 space-y-4 p-6 rounded-2xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
        <h3 class="text-lg font-bold mb-4">Status Badge Examples:</h3>
        <div class="flex flex-wrap gap-3">
            <x-admin.article-status-badge status="published" />
            <x-admin.article-status-badge status="draft" />
            <x-admin.article-status-badge status="scheduled" />
            <x-admin.article-status-badge status="archived" />
            <x-admin.article-status-badge status="draft" locale="ar" :isReady="false" />
            <x-admin.article-status-badge status="published" locale="en" :isReady="true" />
        </div>
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
        :data="$sampleArticles"
        :actions="['view', 'edit', 'delete', 'duplicate']"
        :bulkActions="['delete', 'publish', 'export']"
        :searchable="true"
        :filterable="true"
        :exportable="true"
        :perPageOptions="[5, 10, 25, 50]"
        emptyTitle="No Articles Found"
        emptyMessage="Get started by creating your first article"
        emptyIcon="file-text"
        editRoute="/admin/articles/{id}/edit"
        deleteRoute="/admin/articles/{id}"
        viewRoute="/admin/articles/{id}"
    >
        Articles List
    </x-admin.data-table>

    <!-- Advanced Filter Sidebar -->
    <x-admin.article-filter-sidebar
        :categories="$sampleCategories"
        :authors="$sampleAuthors"
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
        });

        // Demo notification
        setTimeout(() => {
            console.log('Articles Index Page loaded successfully with sample data!');
        }, 500);
    });
</script>
@endpush
