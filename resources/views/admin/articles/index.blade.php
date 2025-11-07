@extends('layouts.admin')

@section('title', 'إدارة المقالات')

@push('styles')
<style>
    /* ===== PREMIUM ARTICLE INDEX STYLES ===== */

    /* Animated Mesh Background */
    .hero-mesh-bg {
        background:
            radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(100, 204, 197, 0.1) 0%, transparent 50%);
        background-size: 200% 200%;
        animation: meshMove 20s ease-in-out infinite;
    }

    @keyframes meshMove {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    /* Floating Orbs */
    .floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        animation: float 8s ease-in-out infinite;
        pointer-events: none;
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(20px, -20px) scale(1.1); }
    }

    /* Premium Card Hover */
    .premium-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .premium-card:hover {
        transform: scale(1.03) translateY(-8px);
        z-index: 10;
    }

    .premium-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }

    .premium-card:hover::before {
        left: 100%;
    }

    /* Card Overlay */
    .card-overlay {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .premium-card:hover .card-overlay {
        opacity: 1;
    }

    /* Shimmer Effect */
    .shimmer {
        position: relative;
        overflow: hidden;
    }

    .shimmer::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    /* Staggered Animations */
    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    /* Loading Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    .dark .skeleton {
        background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
        background-size: 200% 100%;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* View Toggle Buttons */
    .view-btn {
        transition: all 0.3s ease;
    }

    .view-btn.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
    }

    /* Timeline View */
    .timeline-line {
        position: absolute;
        right: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #0056b3, #218838);
    }

    .timeline-dot {
        position: relative;
        width: 12px;
        height: 12px;
        background: white;
        border: 3px solid #0056b3;
        border-radius: 50%;
        z-index: 10;
    }

    /* Bulk Action Bar */
    .bulk-action-bar {
        transform: translateY(100%);
        transition: transform 0.3s ease;
    }

    .bulk-action-bar.show {
        transform: translateY(0);
    }

    /* Category Pills */
    .category-pill {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .category-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.2);
    }

    /* Responsive Grid */
    @media (max-width: 640px) {
        .premium-card:hover {
            transform: scale(1.02) translateY(-4px);
        }

        .hero-mesh-bg,
        .floating-orb {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div x-data="articlesIndex()" x-init="init()" class="space-y-6">
    <!-- Premium Header with Animated Background -->
    <div class="relative overflow-hidden rounded-2xl hero-mesh-bg p-8 md:p-12"
         :class="darkMode ? 'bg-gray-900' : 'bg-white'">
        <!-- Floating Orbs -->
        <div class="floating-orb w-32 h-32 bg-[#0056b3] opacity-20 top-4 left-10" style="animation-delay: 0s;"></div>
        <div class="floating-orb w-24 h-24 bg-[#218838] opacity-20 bottom-4 right-20" style="animation-delay: 2s;"></div>
        <div class="floating-orb w-20 h-20 bg-[#64CCC5] opacity-20 top-20 right-10" style="animation-delay: 4s;"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex-1">
                <h1 class="text-4xl md:text-5xl font-black mb-3 gradient-text">
                    إدارة المقالات
                </h1>
                <p class="text-lg" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                    إدارة شاملة لمقالات السيرة النبوية والمحتوى التعليمي
                </p>
                <div class="flex items-center gap-6 mt-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-text" :class="darkMode ? 'text-[#64CCC5]' : 'text-[#0056b3]'" class="w-5 h-5"></i>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="pagination.total"></span>
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مقالة</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="eye" :class="darkMode ? 'text-[#64CCC5]' : 'text-[#0056b3]'" class="w-5 h-5"></i>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="formatNumber(totalViews)"></span>
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مشاهدة</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="star" :class="darkMode ? 'text-[#64CCC5]' : 'text-[#0056b3]'" class="w-5 h-5"></i>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="featuredCount"></span>
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مميزة</span>
                    </div>
                </div>
            </div>

            <!-- Search Bar with Glassmorphism -->
            <div class="md:w-96">
                <div class="relative glass-light-soft rounded-2xl p-1">
                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.500ms="loadArticles()"
                        placeholder="ابحث في المقالات..."
                        class="w-full px-6 py-4 pr-12 bg-transparent border-none rounded-xl focus:ring-2 focus:ring-[#64CCC5] transition-all"
                        :class="darkMode ? 'text-white placeholder-gray-400' : 'text-gray-900 placeholder-gray-500'"
                    >
                    <i data-lucide="search" class="w-5 h-5 text-[#0056b3] absolute right-4 top-1/2 transform -translate-y-1/2"></i>
                </div>
            </div>

            <!-- New Article CTA -->
            <a href="{{ route('admin.articles.create') }}"
               class="shimmer px-8 py-4 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-2xl font-bold text-lg shadow-lg hover:shadow-2xl transition-all duration-300 flex items-center gap-3 whitespace-nowrap hover:scale-105">
                <i data-lucide="plus-circle" class="w-6 h-6"></i>
                <span>مقالة جديدة</span>
            </a>
        </div>
    </div>

    <!-- Smart Filters Section (Sticky) -->
    <section class="sticky top-16 z-40 backdrop-blur-lg rounded-2xl p-6 shadow-lg"
             :class="darkMode ? 'bg-gray-900/95 border border-gray-800' : 'bg-white/95 border border-gray-200'">
        <div class="space-y-4">
            <!-- First Row: Status Pills & Language -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Status Pills -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="filters.status = ''; loadArticles()"
                            class="category-pill px-5 py-2 rounded-full font-semibold text-sm transition-all"
                            :class="filters.status === '' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                                (darkMode ? 'glass-dark text-gray-300 hover:text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        <i data-lucide="grid-3x3" class="w-4 h-4 inline-block ml-1"></i>
                        الكل
                    </button>
                    <button @click="filters.status = 'featured'; loadArticles()"
                            class="category-pill px-5 py-2 rounded-full font-semibold text-sm transition-all"
                            :class="filters.status === 'featured' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                                (darkMode ? 'glass-dark text-gray-300 hover:text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        <i data-lucide="star" class="w-4 h-4 inline-block ml-1"></i>
                        المميزة
                    </button>
                    <button @click="filters.status = 'pinned'; loadArticles()"
                            class="category-pill px-5 py-2 rounded-full font-semibold text-sm transition-all"
                            :class="filters.status === 'pinned' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                                (darkMode ? 'glass-dark text-gray-300 hover:text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        <i data-lucide="pin" class="w-4 h-4 inline-block ml-1"></i>
                        المثبتة
                    </button>
                    <button @click="filters.status = 'draft'; loadArticles()"
                            class="category-pill px-5 py-2 rounded-full font-semibold text-sm transition-all"
                            :class="filters.status === 'draft' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                                (darkMode ? 'glass-dark text-gray-300 hover:text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        <i data-lucide="file-edit" class="w-4 h-4 inline-block ml-1"></i>
                        المسودات
                    </button>
                </div>

                <div class="h-8 w-px" :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'"></div>

                <!-- Language Filter -->
                <div class="flex items-center gap-2">
                    <template x-for="lang in languages" :key="lang.code">
                        <button @click="filters.locale = filters.locale === lang.code ? '' : lang.code; loadArticles()"
                                class="px-4 py-2 rounded-lg font-semibold text-sm transition-all"
                                :class="filters.locale === lang.code ?
                                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md' :
                                    (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                            <span x-text="lang.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Second Row: Category, Sort, View Toggle -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-[300px]">
                    <!-- Category Filter -->
                    <select x-model="filters.category_id" @change="loadArticles()"
                            class="px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#0056b3] transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">جميع الأقسام</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name.ar || category.name.en"></option>
                        </template>
                    </select>

                    <!-- Sort Options -->
                    <select x-model="filters.sort" @change="loadArticles()"
                            class="px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#0056b3] transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="latest">الأحدث</option>
                        <option value="popular">الأكثر مشاهدة</option>
                        <option value="alphabetical">أبجدي</option>
                        <option value="oldest">الأقدم</option>
                    </select>
                </div>

                <!-- View Toggle -->
                <div class="flex items-center gap-2 p-1 rounded-lg"
                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <button @click="viewMode = 'grid'"
                            class="view-btn px-4 py-2 rounded-lg transition-all"
                            :class="viewMode === 'grid' ? 'active' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        <i data-lucide="grid-3x3" class="w-5 h-5"></i>
                    </button>
                    <button @click="viewMode = 'list'"
                            class="view-btn px-4 py-2 rounded-lg transition-all"
                            :class="viewMode === 'list' ? 'active' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        <i data-lucide="list" class="w-5 h-5"></i>
                    </button>
                    <button @click="viewMode = 'timeline'"
                            class="view-btn px-4 py-2 rounded-lg transition-all"
                            :class="viewMode === 'timeline' ? 'active' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Bulk Selection Bar (Fixed at bottom when items selected) -->
    <div x-show="selected.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-full"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 max-w-4xl w-full px-4">
        <div class="backdrop-blur-lg rounded-2xl shadow-2xl p-6"
             :class="darkMode ? 'bg-gray-900/95 border border-gray-800' : 'bg-white/95 border border-gray-200'">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold">
                        <span x-text="selected.length"></span>
                    </div>
                    <div>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            تم تحديد <span x-text="selected.length"></span> مقالة
                        </p>
                        <button @click="selected = []" class="text-sm" :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                            إلغاء التحديد
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="bulkAction('feature')"
                            class="px-5 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 hover:scale-105"
                            :class="darkMode ? 'bg-yellow-600 hover:bg-yellow-700 text-white' : 'bg-yellow-500 hover:bg-yellow-600 text-white'">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        <span>تمييز</span>
                    </button>
                    <button @click="bulkAction('pin')"
                            class="px-5 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 hover:scale-105"
                            :class="darkMode ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-green-500 hover:bg-green-600 text-white'">
                        <i data-lucide="pin" class="w-4 h-4"></i>
                        <span>تثبيت</span>
                    </button>
                    <button @click="bulkAction('archive')"
                            class="px-5 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 hover:scale-105"
                            :class="darkMode ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-blue-500 hover:bg-blue-600 text-white'">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                        <span>أرشفة</span>
                    </button>
                    <button @click="bulkAction('delete')"
                            class="px-5 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 hover:scale-105 bg-red-600 hover:bg-red-700 text-white">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>حذف</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="i in 6" :key="i">
            <div class="rounded-2xl overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                <div class="skeleton h-48 w-full"></div>
                <div class="p-6 space-y-3">
                    <div class="skeleton h-6 w-3/4 rounded"></div>
                    <div class="skeleton h-4 w-full rounded"></div>
                    <div class="skeleton h-4 w-2/3 rounded"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && articles.length === 0"
         class="rounded-2xl p-12" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <div class="flex flex-col items-center justify-center gap-4 text-center">
            <div class="w-24 h-24 rounded-full flex items-center justify-center"
                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                <i data-lucide="file-text" class="w-12 h-12 text-[#0056b3]"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    لا توجد مقالات
                </h3>
                <p class="mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    لم يتم العثور على مقالات. جرب تغيير معايير البحث أو أنشئ مقالة جديدة.
                </p>
                <a href="{{ route('admin.articles.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl font-bold hover:shadow-lg transition-all">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>إنشاء مقالة جديدة</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div x-show="!loading && articles.length > 0 && viewMode === 'grid'"
         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="(article, index) in articles" :key="article.id">
            <div class="premium-card rounded-2xl overflow-hidden shadow-lg animate-fade-in-up"
                 :class="[
                     darkMode ? 'bg-gray-800' : 'bg-white',
                     'stagger-' + ((index % 4) + 1)
                 ]">
                <!-- Checkbox for bulk selection -->
                <div class="absolute top-4 right-4 z-20">
                    <input type="checkbox"
                           :value="article.id"
                           x-model="selected"
                           class="w-5 h-5 rounded border-2 border-white shadow-lg focus:ring-2 focus:ring-[#64CCC5]">
                </div>

                <!-- Thumbnail -->
                <div class="relative aspect-video group">
                    <img :src="article.thumb || '/images/placeholder.jpg'"
                         :alt="article.title"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                    <!-- Overlay on hover -->
                    <div class="card-overlay absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center gap-3">
                        <a :href="`/su/portal/v1/admin/articles/${article.id}`"
                           class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center hover:bg-white transition-all hover:scale-110">
                            <i data-lucide="eye" class="w-5 h-5 text-[#0056b3]"></i>
                        </a>
                        <a :href="`/su/portal/v1/admin/articles/${article.id}/edit`"
                           class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center hover:bg-white transition-all hover:scale-110">
                            <i data-lucide="edit" class="w-5 h-5 text-[#218838]"></i>
                        </a>
                    </div>

                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <span x-show="article.is_featured"
                              class="px-3 py-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3"></i>
                            مميزة
                        </span>
                        <span x-show="article.is_pinned"
                              class="px-3 py-1 bg-gradient-to-r from-green-400 to-green-600 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                            <i data-lucide="pin" class="w-3 h-3"></i>
                            مثبتة
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Category & Date -->
                    <div class="flex items-center justify-between mb-3 text-sm">
                        <span class="px-3 py-1 rounded-lg font-semibold"
                              :class="darkMode ? 'bg-gray-700 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'"
                              x-text="article.category?.name?.ar || 'بدون قسم'"></span>
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                              x-text="formatDate(article.created_at)"></span>
                    </div>

                    <!-- Title -->
                    <h3 class="font-bold text-xl mb-2 line-clamp-2 group-hover:text-[#64CCC5] transition-colors"
                        :class="darkMode ? 'text-white' : 'text-gray-900'"
                        x-text="article.title"></h3>

                    <!-- Excerpt -->
                    <p class="text-sm mb-4 line-clamp-2"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                       x-text="article.excerpt || 'لا يوجد ملخص متاح'"></p>

                    <!-- Writer & Stats -->
                    <div class="flex items-center justify-between pt-4 border-t"
                         :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold text-xs">
                                <span x-text="article.writer?.name?.charAt(0) || 'A'"></span>
                            </div>
                            <span class="text-sm font-semibold"
                                  :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                  x-text="article.writer?.name || 'غير محدد'"></span>
                        </div>

                        <div class="flex items-center gap-4 text-sm">
                            <div class="flex items-center gap-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span x-text="formatNumber(article.views)"></span>
                            </div>
                            <div class="flex items-center gap-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                <span x-text="article.read_time + ' د'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- List View -->
    <div x-show="!loading && articles.length > 0 && viewMode === 'list'"
         class="rounded-2xl overflow-hidden shadow-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'" class="border-b">
                    <tr>
                        <th class="px-6 py-4 text-right">
                            <input type="checkbox"
                                   @change="toggleAll()"
                                   :checked="selected.length === articles.length && articles.length > 0"
                                   class="rounded border-gray-300 focus:ring-[#0056b3]">
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">الصورة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">العنوان</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">الكاتب</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">القسم</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">المشاهدات</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'" class="divide-y">
                    <template x-for="article in articles" :key="article.id">
                        <tr class="hover:bg-opacity-50 transition-colors"
                            :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'">
                            <td class="px-6 py-4">
                                <input type="checkbox"
                                       :value="article.id"
                                       x-model="selected"
                                       class="rounded border-gray-300 focus:ring-[#0056b3]">
                            </td>
                            <td class="px-6 py-4">
                                <img :src="article.thumb || '/images/placeholder.jpg'"
                                     :alt="article.title"
                                     class="w-16 h-16 rounded-lg object-cover">
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                     x-text="article.title"></div>
                                <div class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    <i data-lucide="clock" class="w-3 h-3 inline"></i>
                                    <span x-text="article.read_time + ' دقيقة قراءة'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                      x-text="article.writer?.name || 'غير محدد'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold"
                                      :class="darkMode ? 'bg-gray-700 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'"
                                      x-text="article.category?.name?.ar || 'بدون قسم'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="eye" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                          x-text="formatNumber(article.views)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-1">
                                    <span x-show="article.is_featured"
                                          class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded">
                                        <i data-lucide="star" class="w-3 h-3 inline"></i>
                                    </span>
                                    <span x-show="article.is_pinned"
                                          class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded">
                                        <i data-lucide="pin" class="w-3 h-3 inline"></i>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/articles/${article.id}`"
                                       class="p-2 rounded-lg transition-all hover:bg-[#0056b3] hover:bg-opacity-10 text-[#0056b3]"
                                       title="عرض">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/articles/${article.id}/edit`"
                                       class="p-2 rounded-lg transition-all hover:bg-[#218838] hover:bg-opacity-10 text-[#218838]"
                                       title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteArticle(article.id)"
                                            class="p-2 rounded-lg transition-all hover:bg-red-50 text-red-600"
                                            title="حذف">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Timeline View -->
    <div x-show="!loading && articles.length > 0 && viewMode === 'timeline'"
         class="space-y-0">
        <template x-for="(article, index) in articles" :key="article.id">
            <div class="relative pr-12 pb-8 animate-fade-in-up"
                 :class="'stagger-' + ((index % 4) + 1)">
                <!-- Timeline Line -->
                <div x-show="index < articles.length - 1" class="timeline-line"></div>

                <!-- Timeline Dot -->
                <div class="absolute right-0 top-0 timeline-dot"></div>

                <!-- Content Card -->
                <div class="premium-card rounded-2xl overflow-hidden shadow-lg"
                     :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                    <div class="flex flex-col md:flex-row gap-6 p-6">
                        <!-- Thumbnail -->
                        <div class="md:w-48 h-48 flex-shrink-0 relative group rounded-xl overflow-hidden">
                            <input type="checkbox"
                                   :value="article.id"
                                   x-model="selected"
                                   class="absolute top-3 right-3 z-10 w-5 h-5 rounded shadow-lg">
                            <img :src="article.thumb || '/images/placeholder.jpg'"
                                 :alt="article.title"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

                            <!-- Badges -->
                            <div class="absolute bottom-3 left-3 flex gap-2">
                                <span x-show="article.is_featured"
                                      class="px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded shadow-lg">
                                    <i data-lucide="star" class="w-3 h-3 inline"></i>
                                </span>
                                <span x-show="article.is_pinned"
                                      class="px-2 py-1 bg-green-500 text-white text-xs font-bold rounded shadow-lg">
                                    <i data-lucide="pin" class="w-3 h-3 inline"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <!-- Date Badge -->
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg mb-4"
                                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#0056b3]"></i>
                                <span class="text-sm font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                      x-text="formatDate(article.created_at)"></span>
                            </div>

                            <!-- Title & Category -->
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <h3 class="font-bold text-2xl group-hover:text-[#64CCC5] transition-colors"
                                    :class="darkMode ? 'text-white' : 'text-gray-900'"
                                    x-text="article.title"></h3>
                                <span class="px-3 py-1 rounded-lg text-sm font-semibold whitespace-nowrap"
                                      :class="darkMode ? 'bg-gray-700 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'"
                                      x-text="article.category?.name?.ar || 'بدون قسم'"></span>
                            </div>

                            <!-- Excerpt -->
                            <p class="text-sm mb-4 line-clamp-2"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                               x-text="article.excerpt || 'لا يوجد ملخص متاح'"></p>

                            <!-- Meta Info -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <!-- Writer -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold text-xs">
                                            <span x-text="article.writer?.name?.charAt(0) || 'A'"></span>
                                        </div>
                                        <span class="text-sm font-semibold"
                                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                              x-text="article.writer?.name || 'غير محدد'"></span>
                                    </div>

                                    <!-- Stats -->
                                    <div class="flex items-center gap-4 text-sm">
                                        <div class="flex items-center gap-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                            <span x-text="formatNumber(article.views)"></span>
                                        </div>
                                        <div class="flex items-center gap-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                            <span x-text="article.read_time + ' د'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <a :href="`/su/portal/v1/admin/articles/${article.id}`"
                                       class="px-4 py-2 rounded-lg transition-all flex items-center gap-2"
                                       :class="darkMode ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900'">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        <span>عرض</span>
                                    </a>
                                    <a :href="`/su/portal/v1/admin/articles/${article.id}/edit`"
                                       class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                        <span>تعديل</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.total > 0"
         class="flex items-center justify-between p-6 rounded-2xl shadow-lg"
         :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            عرض
            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="pagination.from"></span>
            إلى
            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="pagination.to"></span>
            من
            <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="pagination.total"></span>
            مقالة
        </div>

        <div class="flex items-center gap-2">
            <button @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'"
                    class="px-4 py-2 rounded-lg border transition-all"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                السابق
            </button>

            <template x-for="page in visiblePages" :key="page">
                <button @click="changePage(page)"
                        :class="page === pagination.current_page ?
                            'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                            (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100')"
                        class="px-4 py-2 rounded-lg border transition-all hover:scale-105"
                        :class="darkMode && page !== pagination.current_page ? 'border-gray-600' : 'border-gray-300'"
                        x-text="page"></button>
            </template>

            <button @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'"
                    class="px-4 py-2 rounded-lg border transition-all"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                التالي
            </button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-transition
         class="fixed bottom-6 left-6 max-w-sm z-50">
        <div class="px-6 py-4 rounded-xl shadow-2xl backdrop-blur-lg flex items-center gap-3"
             :class="{
                 'bg-gradient-to-r from-green-500 to-green-600 text-white': toast.type === 'success',
                 'bg-gradient-to-r from-red-500 to-red-600 text-white': toast.type === 'error',
                 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white': toast.type === 'warning'
             }">
            <i :data-lucide="toast.type === 'success' ? 'check-circle' : (toast.type === 'error' ? 'x-circle' : 'alert-circle')"
               class="w-6 h-6"></i>
            <span class="font-semibold" x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function articlesIndex() {
    return {
        articles: [],
        categories: @json($categories ?? []),
        loading: true,
        darkMode: localStorage.getItem('darkMode') === 'true',
        viewMode: localStorage.getItem('articlesViewMode') || 'grid',
        search: '',
        filters: {
            status: '',
            locale: '',
            category_id: '',
            sort: 'latest'
        },
        selected: [],
        languages: [
            { code: 'ar', name: 'ع' },
            { code: 'en', name: 'EN' },
            { code: 'fr', name: 'FR' },
            { code: 'tr', name: 'TR' },
            { code: 'ur', name: 'UR' }
        ],
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },
        totalViews: 0,
        featuredCount: 0,
        toast: {
            show: false,
            type: 'success',
            message: ''
        },

        async init() {
            await this.loadArticles();
            this.$watch('viewMode', value => {
                localStorage.setItem('articlesViewMode', value);
            });
            lucide.createIcons();
        },

        async loadArticles(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 12,
                    search: this.search,
                    ...this.filters
                });

                const response = await fetch(`/su/portal/v1/admin/articles/api?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Prepare articles with localized data
                    this.articles = data.data.map(article => {
                        const locale = article.locales?.find(l => l.locale === (this.filters.locale || 'ar'))
                                    || article.locales?.[0]
                                    || {};
                        return {
                            ...article,
                            title: locale.title || 'بدون عنوان',
                            excerpt: locale.excerpt || '',
                            slug: locale.slug || ''
                        };
                    });

                    this.pagination = {
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        from: data.meta.from,
                        to: data.meta.to,
                        total: data.meta.total
                    };

                    // Calculate stats
                    this.totalViews = this.articles.reduce((sum, a) => sum + (a.views || 0), 0);
                    this.featuredCount = this.articles.filter(a => a.is_featured).length;

                    this.$nextTick(() => lucide.createIcons());
                } else {
                    this.showToast('error', 'فشل تحميل البيانات');
                }
            } catch (error) {
                console.error('Error loading articles:', error);
                this.showToast('error', 'حدث خطأ في تحميل البيانات');
            } finally {
                this.loading = false;
            }
        },

        async deleteArticle(id) {
            if (!confirm('هل أنت متأكد من حذف هذه المقالة؟ لا يمكن التراجع عن هذا الإجراء.')) {
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/articles/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'تم حذف المقالة بنجاح');
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل حذف المقالة');
                }
            } catch (error) {
                console.error('Error deleting article:', error);
                this.showToast('error', 'حدث خطأ في حذف المقالة');
            }
        },

        async bulkAction(action) {
            if (this.selected.length === 0) return;

            const confirmMessages = {
                feature: `هل تريد تمييز ${this.selected.length} مقالة؟`,
                pin: `هل تريد تثبيت ${this.selected.length} مقالة؟`,
                archive: `هل تريد أرشفة ${this.selected.length} مقالة؟`,
                delete: `هل أنت متأكد من حذف ${this.selected.length} مقالة؟ لا يمكن التراجع عن هذا الإجراء.`
            };

            if (!confirm(confirmMessages[action])) {
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/articles/bulk-${action}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ids: this.selected })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message || 'تم تنفيذ الإجراء بنجاح');
                    this.selected = [];
                    await this.loadArticles(this.pagination.current_page);
                } else {
                    this.showToast('error', data.message || 'فشل تنفيذ الإجراء');
                }
            } catch (error) {
                console.error('Error performing bulk action:', error);
                this.showToast('error', 'حدث خطأ في تنفيذ الإجراء');
            }
        },

        toggleAll() {
            if (this.selected.length === this.articles.length) {
                this.selected = [];
            } else {
                this.selected = this.articles.map(a => a.id);
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadArticles(page);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        get visiblePages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;

            for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
                pages.push(i);
            }

            return pages;
        },

        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'م';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'ك';
            }
            return num?.toString() || '0';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => {
                this.toast.show = false;
            }, 5000);
        }
    }
}
</script>
@endsection
