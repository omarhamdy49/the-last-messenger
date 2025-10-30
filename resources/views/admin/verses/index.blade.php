@extends('layouts.admin')

@section('title', 'الآيات - القرآن الكريم')

@section('content')
<div x-data="versesManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">إدارة الآيات</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="text" class="w-4 h-4"></i>
                    <span>إدارة آيات القرآن الكريم</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <button @click="exportData" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse backdrop-blur-sm border border-white/20 hover:scale-105">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>تصدير</span>
                </button>

                <a href="{{ route('admin.verses.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>آية جديدة</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="text" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي الآيات</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.with_tafseer">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">آيات مع التفسير</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="volume-2" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.with_audio">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">آيات مع التلاوة</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="book" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white">114</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">السور</p>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterData"
                        placeholder="البحث في الآيات... (النص العربي أو الترجمة)"
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
                        :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                    >
                </div>
            </div>

            <div>
                <select
                    x-model="filters.surah_id"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل السور</option>
                    @foreach($surahs ?? [] as $surah)
                        <option value="{{ $surah->id }}">{{ $surah->number }}. {{ $surah->name['ar'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select
                    x-model="filters.sortBy"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="verse_number">رقم الآية</option>
                    <option value="surah_id">السورة</option>
                    <option value="created_at">تاريخ الإضافة</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @include('admin.components.skeleton-table')
    </div>

    {{-- Verses Table --}}
    <div x-show="!loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        {{-- Table Header --}}
        <div class="bg-gradient-to-r from-blue-500/10 to-green-500/10 border-b border-white/10 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-white font-bold text-lg">قائمة الآيات</span>
                </div>
                <div class="text-sm text-gray-400">
                    عرض <span class="text-white font-bold" x-text="items.length"></span> آية
                </div>
            </div>
        </div>

        {{-- Table Body --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">السورة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">رقم الآية</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">النص العربي</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الترجمة</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                                        <i data-lucide="text" class="w-12 h-12 text-gray-500"></i>
                                    </div>
                                    <p class="text-white font-bold text-xl mb-2">لا توجد آيات</p>
                                    <p class="text-gray-400 mb-6">ابدأ بإضافة آية جديدة الآن</p>
                                    <a
                                        href="{{ route('admin.verses.create') }}"
                                        class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse"
                                    >
                                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                                        <span>آية جديدة</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="verse in items" :key="verse.id">
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            {{-- Surah --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-white font-semibold" x-text="verse.surah_name"></span>
                            </td>

                            {{-- Verse Number --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-green-500 text-white font-bold" x-text="verse.verse_number"></span>
                            </td>

                            {{-- Arabic Text --}}
                            <td class="px-6 py-4">
                                <p class="text-white font-semibold text-lg line-clamp-2" dir="rtl" x-text="verse.text_ar"></p>
                            </td>

                            {{-- Translation --}}
                            <td class="px-6 py-4">
                                <p class="text-gray-400 text-sm line-clamp-2" x-text="verse.text_en"></p>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <a
                                        :href="`/su/portal/v1/admin/verses/${verse.id}`"
                                        class="p-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all hover:scale-110"
                                        title="عرض"
                                    >
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a
                                        :href="`/su/portal/v1/admin/verses/${verse.id}/edit`"
                                        class="p-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all hover:scale-110"
                                        title="تعديل"
                                    >
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    <button
                                        @click="deleteVerse(verse.id)"
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
function versesManager() {
    return {
        loading: true,
        items: [],
        currentPage: 1,
        totalPages: 1,
        perPage: 50,
        stats: {
            total: 0,
            with_tafseer: 0,
            with_audio: 0
        },
        filters: {
            search: '',
            surah_id: '',
            sortBy: 'verse_number'
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

                const response = await fetch(`/su/portal/v1/admin/verses?${params}`, {
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
                console.error('Error loading verses:', error);
                window.showToast('حدث خطأ أثناء تحميل الآيات', 'error');
            }
        },

        filterData() {
            this.currentPage = 1;
            this.loadData();
        },

        changePage(page) {
            this.currentPage = page;
            this.loadData();
        },

        async deleteVerse(id) {
            if (!confirm('هل أنت متأكد من حذف هذه الآية؟')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/verses/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.showToast('تم حذف الآية بنجاح', 'success');
                    await this.loadData();
                }
            } catch (error) {
                window.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        exportData() {
            window.showToast('جاري تصدير البيانات...', 'info');
        }
    };
}
</script>
@endpush
