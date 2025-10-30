@extends('layouts.admin')

@section('title', 'المقالات - عرض محسّن')

@section('content')
<div x-data="articlesManager()" x-init="init()">
    {{-- Page Header with Glassmorphism --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        {{-- Animated Background Gradient --}}
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">المقالات</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>إدارة وتنظيم المقالات والمحتوى التعليمي</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                {{-- Export Button --}}
                <button @click="exportArticles" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse backdrop-blur-sm border border-white/20 hover:scale-105">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>تصدير</span>
                </button>

                {{-- Create Button --}}
                <a href="{{ route('admin.articles.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>مقال جديد</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Total Articles --}}
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي المقالات</p>
            </div>
        </div>

        {{-- Published --}}
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.published">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">منشور</p>
            </div>
        </div>

        {{-- Draft --}}
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <i data-lucide="edit" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.draft">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مسودة</p>
            </div>
        </div>

        {{-- Archived --}}
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gray-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
                        <i data-lucide="archive" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.archived">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مؤرشف</p>
            </div>
        </div>
    </div>

    {{-- Filters & Search Section --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterArticles"
                        placeholder="البحث عن مقال... (العنوان، المحتوى، الكاتب)"
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
                        :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                    >
                </div>
            </div>

            {{-- Status Filter --}}
            <div>
                <select
                    x-model="filters.status"
                    @change="filterArticles"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل الحالات</option>
                    <option value="published">منشور</option>
                    <option value="draft">مسودة</option>
                    <option value="archived">مؤرشف</option>
                </select>
            </div>

            {{-- Category Filter --}}
            <div>
                <select
                    x-model="filters.category"
                    @change="filterArticles"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل التصنيفات</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}">{{ $category->name['ar'] ?? $category->name['en'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Advanced Filters Toggle --}}
        <div class="mt-4 pt-4 border-t border-white/10" x-show="showAdvancedFilters" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Date From --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">من تاريخ</label>
                    <input
                        type="date"
                        x-model="filters.dateFrom"
                        @change="filterArticles"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">إلى تاريخ</label>
                    <input
                        type="date"
                        x-model="filters.dateTo"
                        @change="filterArticles"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                {{-- Author Filter --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الكاتب</label>
                    <select
                        x-model="filters.author"
                        @change="filterArticles"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">كل الكتاب</option>
                        {{-- Authors populated dynamically --}}
                    </select>
                </div>

                {{-- Sort By --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">ترتيب حسب</label>
                    <select
                        x-model="filters.sortBy"
                        @change="filterArticles"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="created_at">تاريخ الإنشاء</option>
                        <option value="updated_at">تاريخ التحديث</option>
                        <option value="views">المشاهدات</option>
                        <option value="title">العنوان</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Filter Actions --}}
        <div class="mt-4 flex items-center justify-between">
            <button
                @click="showAdvancedFilters = !showAdvancedFilters"
                class="text-sm font-semibold text-brand-accent hover:text-blue-400 transition-colors flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                <span x-text="showAdvancedFilters ? 'إخفاء الفلاتر المتقدمة' : 'إظهار الفلاتر المتقدمة'"></span>
            </button>

            <button
                @click="resetFilters"
                class="text-sm font-semibold text-gray-400 hover:text-white transition-colors flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="x-circle" class="w-4 h-4"></i>
                <span>إعادة تعيين</span>
            </button>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div
        x-show="selectedArticles.length > 0"
        x-collapse
        class="glass-dark rounded-xl p-4 mb-6 flex items-center justify-between border-2 border-blue-500/30"
    >
        <div class="flex items-center space-x-3 space-x-reverse text-white">
            <i data-lucide="check-square" class="w-5 h-5 text-blue-400"></i>
            <span class="font-bold" x-text="`تم تحديد ${selectedArticles.length} مقال`"></span>
        </div>

        <div class="flex items-center space-x-2 space-x-reverse">
            <button
                @click="bulkPublish"
                class="px-4 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg font-semibold transition-all flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>نشر</span>
            </button>

            <button
                @click="bulkArchive"
                class="px-4 py-2 bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-400 rounded-lg font-semibold transition-all flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="archive" class="w-4 h-4"></i>
                <span>أرشفة</span>
            </button>

            <button
                @click="bulkDelete"
                class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg font-semibold transition-all flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>حذف</span>
            </button>

            <button
                @click="selectedArticles = []"
                class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg font-semibold transition-all"
            >
                إلغاء
            </button>
        </div>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @include('admin.components.skeleton-table')
    </div>

    {{-- Articles Table --}}
    <div x-show="!loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        {{-- Table Header --}}
        <div class="bg-gradient-to-r from-blue-500/10 to-green-500/10 border-b border-white/10 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <input
                        type="checkbox"
                        @change="toggleSelectAll"
                        :checked="selectedArticles.length === items.length && items.length > 0"
                        class="w-5 h-5 rounded border-white/20 bg-white/5 text-blue-500 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                    <span class="text-white font-bold text-lg">قائمة المقالات</span>
                </div>

                <div class="text-sm text-gray-400">
                    عرض <span class="text-white font-bold" x-text="items.length"></span> مقال
                </div>
            </div>
        </div>

        {{-- Table Body --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <input
                                type="checkbox"
                                @change="toggleSelectAll"
                                :checked="selectedArticles.length === items.length && items.length > 0"
                                class="w-4 h-4 rounded border-white/20 bg-white/5 text-blue-500 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                            >
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">العنوان</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">التصنيف</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الكاتب</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">المشاهدات</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">التاريخ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                        <i data-lucide="file-text" class="w-12 h-12 text-gray-500"></i>
                                    </div>
                                    <p class="text-white font-bold text-xl mb-2">لا توجد مقالات</p>
                                    <p class="text-gray-400 mb-6">ابدأ بإضافة مقال جديد الآن</p>
                                    <a
                                        href="{{ route('admin.articles.create') }}"
                                        class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse"
                                    >
                                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                                        <span>مقال جديد</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="(article, index) in items" :key="article.id">
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            {{-- Checkbox --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :value="article.id"
                                    x-model="selectedArticles"
                                    class="w-4 h-4 rounded border-white/20 bg-white/5 text-blue-500 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                >
                            </td>

                            {{-- Title --}}
                            <td class="px-6 py-4">
                                <div class="flex items-start space-x-3 space-x-reverse">
                                    {{-- Thumbnail --}}
                                    <img
                                        :src="article.featured_image || '/placeholder-image.jpg'"
                                        :alt="article.title"
                                        class="w-16 h-16 rounded-lg object-cover group-hover:scale-110 transition-transform duration-300"
                                    >

                                    {{-- Title & Excerpt --}}
                                    <div class="flex-1 min-w-0">
                                        <a
                                            :href="`/su/portal/v1/admin/articles/${article.id}`"
                                            class="text-white font-bold hover:text-brand-accent transition-colors line-clamp-1"
                                            x-text="article.title"
                                        ></a>
                                        <p class="text-sm text-gray-400 line-clamp-2 mt-1" x-text="article.excerpt || 'لا يوجد ملخص'"></p>
                                    </div>
                                </div>
                            </td>

                            {{-- Category --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-500/20 text-purple-300" x-text="article.category || 'غير محدد'"></span>
                            </td>

                            {{-- Author --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-bold text-sm" x-text="(article.author || 'A').charAt(0)"></div>
                                    <p class="text-white font-semibold text-sm" x-text="article.author || 'غير محدد'"></p>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold"
                                    :class="{
                                        'bg-green-500/20 text-green-400 border border-green-500/30': article.status === 'published',
                                        'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': article.status === 'draft',
                                        'bg-gray-500/20 text-gray-400 border border-gray-500/30': article.status === 'archived'
                                    }"
                                >
                                    <span class="w-2 h-2 rounded-full ml-2" :class="{
                                        'bg-green-400 animate-pulse': article.status === 'published',
                                        'bg-yellow-400': article.status === 'draft',
                                        'bg-gray-400': article.status === 'archived'
                                    }"></span>
                                    <span x-text="article.status === 'published' ? 'منشور' : (article.status === 'draft' ? 'مسودة' : 'مؤرشف')"></span>
                                </span>
                            </td>

                            {{-- Views --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-1 space-x-reverse text-gray-300">
                                    <i data-lucide="eye" class="w-4 h-4 text-gray-500"></i>
                                    <span class="font-semibold" x-text="article.views || 0"></span>
                                </div>
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <p class="text-white font-semibold" x-text="article.created_at"></p>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    {{-- Edit --}}
                                    <a
                                        :href="`/su/portal/v1/admin/articles/${article.id}/edit`"
                                        class="p-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all hover:scale-110"
                                        title="تعديل"
                                    >
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>

                                    {{-- Delete --}}
                                    <button
                                        @click="deleteArticle(article.id)"
                                        class="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all hover:scale-110"
                                        title="حذف"
                                    >
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-white/10 p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    صفحة <span class="text-white font-bold" x-text="currentPage"></span> من <span class="text-white font-bold" x-text="totalPages"></span>
                </div>

                <div class="flex items-center space-x-2 space-x-reverse">
                    <button
                        @click="currentPage > 1 && changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/20'"
                        class="px-4 py-2 bg-white/10 text-white rounded-lg transition-all"
                    >
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>

                    <button
                        @click="currentPage < totalPages && changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/20'"
                        class="px-4 py-2 bg-white/10 text-white rounded-lg transition-all"
                    >
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function articlesManager() {
    return {
        loading: true,
        items: [],
        selectedArticles: [],
        showAdvancedFilters: false,
        currentPage: 1,
        totalPages: 1,
        perPage: 50,
        stats: {
            total: 0,
            published: 0,
            draft: 0,
            archived: 0
        },
        filters: {
            search: '',
            status: '',
            category: '',
            author: '',
            dateFrom: '',
            dateTo: '',
            sortBy: 'created_at'
        },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.perPage,
                    ...this.filters
                });

                const response = await fetch(`/su/portal/v1/admin/articles?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.data || [];
                    this.stats = data.stats || this.stats;
                    this.totalPages = data.meta?.last_page || 1;
                    this.currentPage = data.meta?.current_page || 1;
                }
            } catch (error) {
                console.error('Error loading articles:', error);
                window.showToast('حدث خطأ أثناء تحميل المقالات', 'error');
            }
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedArticles = this.items.map(item => item.id);
            } else {
                this.selectedArticles = [];
            }
        },

        filterArticles() {
            this.currentPage = 1;
            this.loadData();
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                category: '',
                author: '',
                dateFrom: '',
                dateTo: '',
                sortBy: 'created_at'
            };
            this.loadData();
        },

        changePage(page) {
            this.currentPage = page;
            this.loadData();
        },

        async deleteArticle(id) {
            if (!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/articles/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.showToast('تم حذف المقال بنجاح', 'success');
                    await this.loadData();
                }
            } catch (error) {
                window.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        async bulkPublish() {
            if (this.selectedArticles.length === 0) return;
            if (!confirm(`نشر ${this.selectedArticles.length} مقال؟`)) return;

            // Implement bulk publish logic
            window.showToast(`تم نشر ${this.selectedArticles.length} مقال بنجاح`, 'success');
            this.selectedArticles = [];
        },

        async bulkArchive() {
            if (this.selectedArticles.length === 0) return;
            if (!confirm(`أرشفة ${this.selectedArticles.length} مقال؟`)) return;

            // Implement bulk archive logic
            window.showToast(`تم أرشفة ${this.selectedArticles.length} مقال بنجاح`, 'success');
            this.selectedArticles = [];
        },

        async bulkDelete() {
            if (this.selectedArticles.length === 0) return;
            if (!confirm(`حذف ${this.selectedArticles.length} مقال؟ لا يمكن التراجع.`)) return;

            // Implement bulk delete logic
            window.showToast(`تم حذف ${this.selectedArticles.length} مقال بنجاح`, 'success');
            this.selectedArticles = [];
        },

        exportArticles() {
            window.showToast('جاري تصدير المقالات...', 'info');
            // Implement export functionality
        }
    };
}
</script>
@endpush
