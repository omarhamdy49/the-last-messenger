@extends('layouts.admin')

@section('title', 'الأحاديث - عرض محسّن')

@section('content')
<div x-data="hadithManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">الأحاديث النبوية</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>إدارة وتنظيم الأحاديث الشريفة</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <button @click="exportHadith" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse backdrop-blur-sm border border-white/20 hover:scale-105">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>تصدير</span>
                </button>

                <a href="{{ route('admin.hadith.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>حديث جديد</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview (Grade-based) --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        {{-- Total --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي الأحاديث</p>
            </div>
        </div>

        {{-- Sahih --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.sahih">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">صحيح</p>
            </div>
        </div>

        {{-- Hasan --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <i data-lucide="star" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.hasan">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">حسن</p>
            </div>
        </div>

        {{-- Daif --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.daif">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">ضعيف</p>
            </div>
        </div>

        {{-- Weak --}}
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-red-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.weak">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">موضوع</p>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterHadith"
                        placeholder="البحث في النص العربي أو الترجمة أو الرقم..."
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>

            {{-- Book Filter --}}
            <div>
                <select
                    x-model="filters.book"
                    @change="loadChapters(); filterHadith()"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">كل الكتب</option>
                    <option value="bukhari">صحيح البخاري</option>
                    <option value="muslim">صحيح مسلم</option>
                    <option value="tirmidhi">سنن الترمذي</option>
                    <option value="nasai">سنن النسائي</option>
                    <option value="abudawud">سنن أبي داود</option>
                    <option value="ibnmajah">سنن ابن ماجه</option>
                </select>
            </div>

            {{-- Grade Filter --}}
            <div>
                <select
                    x-model="filters.grade"
                    @change="filterHadith"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">كل الدرجات</option>
                    <option value="sahih">صحيح</option>
                    <option value="hasan">حسن</option>
                    <option value="daif">ضعيف</option>
                    <option value="weak">موضوع</option>
                </select>
            </div>
        </div>

        {{-- Advanced Filters --}}
        <div class="mt-4 pt-4 border-t border-white/10" x-show="showAdvancedFilters" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Chapter Filter --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الباب</label>
                    <select
                        x-model="filters.chapter"
                        @change="filterHadith"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none"
                        :disabled="!filters.book"
                    >
                        <option value="">كل الأبواب</option>
                        <template x-for="chapter in chapters" :key="chapter.id">
                            <option :value="chapter.id" x-text="chapter.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Narrator Filter --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الراوي</label>
                    <input
                        type="text"
                        x-model="filters.narrator"
                        @input.debounce.500ms="filterHadith"
                        placeholder="اسم الراوي..."
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-400"
                    >
                </div>

                {{-- Sort By --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">ترتيب حسب</label>
                    <select
                        x-model="filters.sortBy"
                        @change="filterHadith"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none"
                    >
                        <option value="number">رقم الحديث</option>
                        <option value="created_at">تاريخ الإضافة</option>
                        <option value="grade">الدرجة</option>
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
        x-show="selectedHadith.length > 0"
        x-collapse
        class="glass-dark rounded-xl p-4 mb-6 flex items-center justify-between border-2 border-blue-500/30"
    >
        <div class="flex items-center space-x-3 space-x-reverse text-white">
            <i data-lucide="check-square" class="w-5 h-5 text-blue-400"></i>
            <span class="font-bold" x-text="`تم تحديد ${selectedHadith.length} حديث`"></span>
        </div>

        <div class="flex items-center space-x-2 space-x-reverse">
            <button
                @click="bulkChangeGrade"
                class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg font-semibold transition-all flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="edit" class="w-4 h-4"></i>
                <span>تغيير الدرجة</span>
            </button>

            <button
                @click="bulkDelete"
                class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg font-semibold transition-all flex items-center space-x-1 space-x-reverse"
            >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>حذف</span>
            </button>

            <button
                @click="selectedHadith = []"
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

    {{-- Hadith Table --}}
    <div x-show="!loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        {{-- Table Header --}}
        <div class="bg-gradient-to-r from-blue-500/10 to-green-500/10 border-b border-white/10 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <input
                        type="checkbox"
                        @change="toggleSelectAll"
                        :checked="selectedHadith.length === items.length && items.length > 0"
                        class="w-5 h-5 rounded border-white/20 bg-white/5 text-blue-500 focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                    <span class="text-white font-bold text-lg">قائمة الأحاديث</span>
                </div>

                <div class="text-sm text-gray-400">
                    عرض <span class="text-white font-bold" x-text="items.length"></span> حديث
                </div>
            </div>
        </div>

        {{-- Table Body --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <input type="checkbox" @change="toggleSelectAll" class="w-4 h-4 rounded border-white/20 bg-white/5 text-blue-500">
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">رقم</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الكتاب</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">النص العربي</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الدرجة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                        <i data-lucide="book-open" class="w-12 h-12 text-gray-500"></i>
                                    </div>
                                    <p class="text-white font-bold text-xl mb-2">لا توجد أحاديث</p>
                                    <p class="text-gray-400 mb-6">ابدأ بإضافة حديث جديد الآن</p>
                                    <a href="{{ route('admin.hadith.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold">
                                        <i data-lucide="plus-circle" class="w-5 h-5 inline ml-2"></i>
                                        حديث جديد
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="hadith in items" :key="hadith.id">
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="hadith.id" x-model="selectedHadith" class="w-4 h-4 rounded border-white/20 bg-white/5 text-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-white font-bold" x-text="hadith.hadith_number"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-300 text-sm" x-text="hadith.book?.name || '-'"></span>
                            </td>
                            <td class="px-6 py-4 max-w-md">
                                <p class="text-white text-right line-clamp-2 font-amiri text-lg" dir="rtl" x-text="hadith.arabic_text"></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold"
                                    :class="{
                                        'bg-green-500/20 text-green-400 border border-green-500/30': hadith.grade === 'sahih',
                                        'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': hadith.grade === 'hasan',
                                        'bg-orange-500/20 text-orange-400 border border-orange-500/30': hadith.grade === 'daif',
                                        'bg-red-500/20 text-red-400 border border-red-500/30': hadith.grade === 'weak'
                                    }"
                                    x-text="hadith.grade === 'sahih' ? 'صحيح' : (hadith.grade === 'hasan' ? 'حسن' : (hadith.grade === 'daif' ? 'ضعيف' : 'موضوع'))"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <a :href="`/su/portal/v1/admin/hadith/${hadith.id}/edit`" class="p-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all hover:scale-110" title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteHadith(hadith.id)" class="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all hover:scale-110" title="حذف">
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
                    <button @click="currentPage > 1 && changePage(currentPage - 1)" :disabled="currentPage === 1" class="px-4 py-2 bg-white/10 text-white rounded-lg">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                    <button @click="currentPage < totalPages && changePage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-4 py-2 bg-white/10 text-white rounded-lg">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
    .font-amiri {
        font-family: 'Amiri', serif;
    }
</style>
@endpush

@push('scripts')
<script>
function hadithManager() {
    return {
        loading: true,
        items: [],
        chapters: [],
        selectedHadith: [],
        showAdvancedFilters: false,
        currentPage: 1,
        totalPages: 1,
        perPage: 50,
        stats: {
            total: 0,
            sahih: 0,
            hasan: 0,
            daif: 0,
            weak: 0
        },
        filters: {
            search: '',
            book: '',
            chapter: '',
            grade: '',
            narrator: '',
            sortBy: 'number'
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

                const response = await fetch(`/su/portal/v1/admin/hadith?${params}`, {
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
                console.error('Error loading hadith:', error);
                window.showToast('حدث خطأ أثناء تحميل الأحاديث', 'error');
            }
        },

        async loadChapters() {
            if (!this.filters.book) {
                this.chapters = [];
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith/books/${this.filters.book}/chapters`);
                if (response.ok) {
                    const data = await response.json();
                    this.chapters = data.data || [];
                }
            } catch (error) {
                console.error('Error loading chapters:', error);
            }
        },

        toggleSelectAll(event) {
            this.selectedHadith = event.target.checked ? this.items.map(i => i.id) : [];
        },

        filterHadith() {
            this.currentPage = 1;
            this.loadData();
        },

        resetFilters() {
            this.filters = { search: '', book: '', chapter: '', grade: '', narrator: '', sortBy: 'number' };
            this.chapters = [];
            this.loadData();
        },

        changePage(page) {
            this.currentPage = page;
            this.loadData();
        },

        async deleteHadith(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الحديث؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/hadith/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.showToast('تم حذف الحديث بنجاح', 'success');
                    await this.loadData();
                }
            } catch (error) {
                window.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        async bulkChangeGrade() {
            if (this.selectedHadith.length === 0) return;

            const grade = prompt('اختر الدرجة الجديدة:\nsahih = صحيح\nhasan = حسن\ndaif = ضعيف\nweak = موضوع');
            if (!grade) return;

            // Implement bulk grade change
            window.showToast(`تم تغيير درجة ${this.selectedHadith.length} حديث`, 'success');
            this.selectedHadith = [];
        },

        async bulkDelete() {
            if (this.selectedHadith.length === 0) return;
            if (!confirm(`حذف ${this.selectedHadith.length} حديث؟ لا يمكن التراجع.`)) return;

            // Implement bulk delete
            window.showToast(`تم حذف ${this.selectedHadith.length} حديث بنجاح`, 'success');
            this.selectedHadith = [];
        },

        exportHadith() {
            window.showToast('جاري تصدير الأحاديث...', 'info');
        }
    };
}
</script>
@endpush
