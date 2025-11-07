@extends('layouts.admin')

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                DataTable Component Demo
            </h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Powerful data table with sorting, filtering, pagination, and bulk actions
            </p>
        </div>

        <a href="#"
           class="px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Add New</span>
        </a>
    </div>
@endsection

@section('content')

@php
    // Example data structure
    $columns = [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '80px', 'type' => 'number'],
        ['key' => 'image', 'label' => 'Image', 'sortable' => false, 'width' => '100px', 'type' => 'image'],
        ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'truncate' => 40],
        ['key' => 'category', 'label' => 'Category', 'sortable' => true],
        ['key' => 'author', 'label' => 'Author', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true, 'badgeColors' => [
            'published' => 'from-green-500 to-emerald-600',
            'draft' => 'from-gray-500 to-gray-600',
            'pending' => 'from-yellow-500 to-orange-600',
            'archived' => 'from-red-500 to-rose-600',
        ]],
        ['key' => 'views', 'label' => 'Views', 'sortable' => true, 'type' => 'number', 'align' => 'center'],
        ['key' => 'featured', 'label' => 'Featured', 'sortable' => true, 'type' => 'boolean', 'align' => 'center'],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'type' => 'datetime'],
    ];

    // Sample data
    $articles = collect([
        [
            'id' => 1,
            'image' => 'https://picsum.photos/seed/1/300/200',
            'title' => 'The Life of Prophet Muhammad: Early Years in Mecca',
            'category' => 'Seerah',
            'author' => 'Dr. Ahmed Hassan',
            'status' => 'published',
            'views' => 15420,
            'featured' => true,
            'created_at' => '2024-01-15T10:30:00',
        ],
        [
            'id' => 2,
            'image' => 'https://picsum.photos/seed/2/300/200',
            'title' => 'Understanding Tawheed: The Oneness of Allah',
            'category' => 'Aqeedah',
            'author' => 'Sheikh Omar Abdullah',
            'status' => 'published',
            'views' => 8932,
            'featured' => true,
            'created_at' => '2024-02-20T14:15:00',
        ],
        [
            'id' => 3,
            'image' => 'https://picsum.photos/seed/3/300/200',
            'title' => 'The Battle of Badr: A Turning Point in Islamic History',
            'category' => 'History',
            'author' => 'Dr. Fatima Al-Zahra',
            'status' => 'draft',
            'views' => 3241,
            'featured' => false,
            'created_at' => '2024-03-10T09:45:00',
        ],
        [
            'id' => 4,
            'image' => 'https://picsum.photos/seed/4/300/200',
            'title' => 'The Five Pillars of Islam Explained',
            'category' => 'Fiqh',
            'author' => 'Imam Yusuf Ibrahim',
            'status' => 'published',
            'views' => 22156,
            'featured' => true,
            'created_at' => '2024-01-05T16:20:00',
        ],
        [
            'id' => 5,
            'image' => 'https://picsum.photos/seed/5/300/200',
            'title' => 'The Companions of the Prophet: Abu Bakr As-Siddiq',
            'category' => 'Sahaba',
            'author' => 'Dr. Ahmed Hassan',
            'status' => 'pending',
            'views' => 5678,
            'featured' => false,
            'created_at' => '2024-04-12T11:30:00',
        ],
        [
            'id' => 6,
            'image' => 'https://picsum.photos/seed/6/300/200',
            'title' => 'Ramadan: The Month of Mercy and Forgiveness',
            'category' => 'Fiqh',
            'author' => 'Sheikh Omar Abdullah',
            'status' => 'published',
            'views' => 18234,
            'featured' => true,
            'created_at' => '2024-03-01T08:00:00',
        ],
        [
            'id' => 7,
            'image' => 'https://picsum.photos/seed/7/300/200',
            'title' => 'The Conquest of Mecca: A Historic Victory',
            'category' => 'History',
            'author' => 'Dr. Fatima Al-Zahra',
            'status' => 'archived',
            'views' => 12543,
            'featured' => false,
            'created_at' => '2023-12-15T13:45:00',
        ],
        [
            'id' => 8,
            'image' => 'https://picsum.photos/seed/8/300/200',
            'title' => 'The Farewell Sermon: Final Message of the Prophet',
            'category' => 'Seerah',
            'author' => 'Imam Yusuf Ibrahim',
            'status' => 'published',
            'views' => 31245,
            'featured' => true,
            'created_at' => '2024-02-28T15:30:00',
        ],
        [
            'id' => 9,
            'image' => 'https://picsum.photos/seed/9/300/200',
            'title' => 'Prayer in Islam: The Second Pillar',
            'category' => 'Fiqh',
            'author' => 'Dr. Ahmed Hassan',
            'status' => 'draft',
            'views' => 4521,
            'featured' => false,
            'created_at' => '2024-04-20T10:15:00',
        ],
        [
            'id' => 10,
            'image' => 'https://picsum.photos/seed/10/300/200',
            'title' => 'The Night Journey: Isra and Miraj',
            'category' => 'Seerah',
            'author' => 'Sheikh Omar Abdullah',
            'status' => 'published',
            'views' => 19876,
            'featured' => true,
            'created_at' => '2024-01-25T12:00:00',
        ],
        [
            'id' => 11,
            'image' => 'https://picsum.photos/seed/11/300/200',
            'title' => 'The Treaty of Hudaybiyyah: Wisdom in Diplomacy',
            'category' => 'History',
            'author' => 'Dr. Fatima Al-Zahra',
            'status' => 'published',
            'views' => 9234,
            'featured' => false,
            'created_at' => '2024-03-15T14:30:00',
        ],
        [
            'id' => 12,
            'image' => 'https://picsum.photos/seed/12/300/200',
            'title' => 'Zakat: The Third Pillar of Islam',
            'category' => 'Fiqh',
            'author' => 'Imam Yusuf Ibrahim',
            'status' => 'pending',
            'views' => 6543,
            'featured' => false,
            'created_at' => '2024-04-18T09:20:00',
        ],
    ]);
@endphp

<!-- DataTable Component Usage -->
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :actions="['view', 'edit', 'duplicate', 'delete']"
    :bulkActions="['delete', 'publish', 'export']"
    :searchable="true"
    :filterable="true"
    :exportable="true"
    :per-page-options="[5, 10, 25, 50]"
    edit-route="/admin/articles/{id}/edit"
    delete-route="/admin/articles/{id}"
    view-route="/admin/articles/{id}"
    empty-title="No Articles Found"
    empty-message="Start by creating your first article."
    empty-icon="file-text"
>
    Articles Management
</x-admin.data-table>

<!-- Implementation Guide -->
<div class="mt-12 rounded-2xl p-8 border"
     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

    <h2 class="text-2xl font-bold mb-6 gradient-text">
        Implementation Guide
    </h2>

    <!-- Quick Start -->
    <div class="mb-8">
        <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Quick Start
        </h3>

        <div class="rounded-xl p-6 font-mono text-sm overflow-x-auto"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
<pre :class="darkMode ? 'text-gray-300' : 'text-gray-800'"><code>&lt;x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :actions="['view', 'edit', 'delete']"
    :bulkActions="['delete', 'publish']"
    :searchable="true"
    :exportable="true"
/&gt;</code></pre>
        </div>
    </div>

    <!-- Column Configuration -->
    <div class="mb-8">
        <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Column Configuration
        </h3>

        <div class="rounded-xl p-6 font-mono text-sm overflow-x-auto"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
<pre :class="darkMode ? 'text-gray-300' : 'text-gray-800'"><code>$columns = [
    [
        'key' => 'id',              // Data key
        'label' => 'ID',             // Display label
        'sortable' => true,          // Enable sorting
        'width' => '80px',           // Column width
        'type' => 'number',          // Data type
        'align' => 'left',           // Text alignment
    ],
    [
        'key' => 'title',
        'label' => 'Title',
        'sortable' => true,
        'truncate' => 50,            // Truncate text
    ],
    [
        'key' => 'status',
        'label' => 'Status',
        'badge' => true,             // Display as badge
        'badgeColors' => [
            'published' => 'from-green-500 to-emerald-600',
            'draft' => 'from-gray-500 to-gray-600',
        ],
    ],
    [
        'key' => 'created_at',
        'label' => 'Created',
        'type' => 'datetime',        // Date/time formatting
        'sortable' => true,
    ],
];</code></pre>
        </div>
    </div>

    <!-- Supported Types -->
    <div class="mb-8">
        <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Supported Column Types
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">text</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Default text display with optional truncation
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">number</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Formatted numbers with gradient styling
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">image</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Thumbnail image with fallback icon
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">badge</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Status badges with custom colors
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">date</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Formatted date (e.g., Jan 15, 2024)
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">datetime</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Date and time with separate lines
                </p>
            </div>

            <div class="p-4 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <h4 class="font-bold mb-2 text-accent">boolean</h4>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Yes/No badges with icons
                </p>
            </div>
        </div>
    </div>

    <!-- Features List -->
    <div>
        <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Features
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Sorting</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Click column headers to sort ascending/descending
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Search</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Real-time search with 300ms debounce
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Pagination</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Configurable per-page options with page numbers
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Bulk Actions</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Select multiple rows for batch operations
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Export</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Export to CSV, Excel, or PDF formats
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h4 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">Dark Mode</h4>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Full support for light and dark themes
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
