@extends('layouts.admin')

@section('title', 'السور - القرآن الكريم')

@section('content')
<div x-data="surahsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 mb-8 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">إدارة السور</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>إدارة سور القرآن الكريم</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <button @click="exportData" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse backdrop-blur-sm border border-white/20 hover:scale-105">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>تصدير</span>
                </button>
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
                        <i data-lucide="book" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">114</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي السور</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.makkan">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مكية</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="map" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.madinan">0</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">مدنية</p>
            </div>
        </div>

        <div class="group rounded-xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="text" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white">6236</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">الآيات</p>
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
                        placeholder="البحث في السور... (الاسم، الرقم)"
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400"
                        :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                    >
                </div>
            </div>

            <div>
                <select
                    x-model="filters.revelationPlace"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="">كل السور</option>
                    <option value="makkah">مكية</option>
                    <option value="madinah">مدنية</option>
                </select>
            </div>

            <div>
                <select
                    x-model="filters.sortBy"
                    @change="filterData"
                    class="w-full px-4 py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900'"
                >
                    <option value="number">الترتيب في المصحف</option>
                    <option value="name">الاسم</option>
                    <option value="verses_count">عدد الآيات</option>
                    <option value="revelation_order">ترتيب النزول</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        @include('admin.components.skeleton-table')
    </div>

    {{-- Surahs Grid --}}
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-if="items.length === 0">
            <div class="col-span-full rounded-2xl p-16 text-center transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="book-open" class="w-12 h-12 text-gray-500"></i>
                    </div>
                    <p class="text-white font-bold text-xl mb-2">لا توجد سور</p>
                    <p class="text-gray-400">لم يتم العثور على أي سورة</p>
                </div>
            </div>
        </template>

        <template x-for="surah in items" :key="surah.id">
            <div class="group rounded-2xl p-6 relative overflow-hidden hover:scale-105 transition-all duration-300 cursor-pointer" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-black text-xl shadow-lg">
                            <span x-text="surah.number"></span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1 group-hover:text-brand-accent transition-colors" x-text="surah.name_ar" dir="rtl"></h3>
                            <p class="text-sm text-gray-400" x-text="surah.name_transliteration"></p>
                        </div>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-bold border"
                        :class="surah.revelation_place === 'makkah' ? 'bg-green-500/20 text-green-400 border-green-500/30' : 'bg-purple-500/20 text-purple-400 border-purple-500/30'"
                        x-text="surah.revelation_place === 'makkah' ? 'مكية' : 'مدنية'"
                    ></span>
                </div>

                <div class="flex items-center justify-between text-sm mb-4">
                    <div class="flex items-center space-x-2 space-x-reverse text-gray-400">
                        <i data-lucide="list" class="w-4 h-4"></i>
                        <span><span x-text="surah.verses_count"></span> آية</span>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse text-gray-400">
                        <i data-lucide="bookmark" class="w-4 h-4"></i>
                        <span>صفحة <span x-text="surah.page_number"></span></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-4">
                    <a
                        :href="`/su/portal/v1/admin/surahs/${surah.id}`"
                        class="flex-1 px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all text-center font-semibold flex items-center justify-center space-x-1 space-x-reverse"
                    >
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span>عرض</span>
                    </a>
                    <a
                        :href="`/su/portal/v1/admin/surahs/${surah.id}/edit`"
                        class="flex-1 px-4 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all text-center font-semibold flex items-center justify-center space-x-1 space-x-reverse"
                    >
                        <i data-lucide="edit" class="w-4 h-4"></i>
                        <span>تعديل</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function surahsManager() {
    return {
        loading: true,
        items: [],
        stats: {
            total: 114,
            makkan: 0,
            madinan: 0
        },
        filters: {
            search: '',
            revelationPlace: '',
            sortBy: 'number'
        },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/su/portal/v1/admin/surahs?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.items = data.data || [];
                    this.stats = data.stats || this.stats;
                }
            } catch (error) {
                console.error('Error loading surahs:', error);
                window.showToast('حدث خطأ أثناء تحميل السور', 'error');
            }
        },

        filterData() {
            this.loadData();
        },

        exportData() {
            window.showToast('جاري تصدير البيانات...', 'info');
        }
    };
}
</script>
@endpush
