@extends('layouts.admin')

@section('title', 'القرآن الكريم - عرض محسّن')

@section('content')
<div x-data="quranManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">القرآن الكريم</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>إدارة السور والآيات</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <a href="{{ route('admin.quran.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>سورة جديدة</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">السور</p>
                    <span class="text-2xl font-black text-white">114</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="book" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">مكية</p>
                    <span class="text-2xl font-black text-white" x-text="stats.makkan">0</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">مدنية</p>
                    <span class="text-2xl font-black text-white" x-text="stats.madinan">0</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="map" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">الآيات</p>
                    <span class="text-2xl font-black text-white">6236</span>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                    <i data-lucide="text" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterSurahs"
                        placeholder="البحث في السور..."
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div>
                <select
                    x-model="filters.revelationPlace"
                    @change="filterSurahs"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">كل السور</option>
                    <option value="makkah">مكية</option>
                    <option value="madinah">مدنية</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Surahs Grid --}}
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-if="items.length === 0">
            <div class="col-span-full glass-dark rounded-2xl p-16 text-center">
                <p class="text-white font-bold text-xl mb-2">لا توجد سور</p>
            </div>
        </template>

        <template x-for="surah in items" :key="surah.id">
            <div class="glass-dark rounded-2xl p-6 hover:scale-105 transition-transform duration-300 group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white font-black text-lg">
                            <span x-text="surah.number"></span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-1" x-text="surah.name_ar" dir="rtl"></h3>
                            <p class="text-sm text-gray-400" x-text="surah.name_en"></p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold" :class="surah.revelation_place === 'makkah' ? 'bg-green-500/20 text-green-400' : 'bg-purple-500/20 text-purple-400'" x-text="surah.revelation_place === 'makkah' ? 'مكية' : 'مدنية'"></span>
                </div>

                <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                    <span><span x-text="surah.verses_count"></span> آية</span>
                    <span>الصفحة <span x-text="surah.page_number"></span></span>
                </div>

                <div class="flex items-center gap-2">
                    <a :href="`/su/portal/v1/admin/quran/${surah.id}`" class="flex-1 px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all text-center font-semibold">
                        عرض
                    </a>
                    <a :href="`/su/portal/v1/admin/quran/${surah.id}/edit`" class="flex-1 px-4 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all text-center font-semibold">
                        تعديل
                    </a>
                </div>
            </div>
        </template>
    </div>

    {{-- Loading Skeleton --}}
    <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="glass-dark rounded-2xl p-6 animate-pulse" x-for="i in 9">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 bg-white/10 rounded-xl"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-6 bg-white/10 rounded w-3/4"></div>
                    <div class="h-4 bg-white/10 rounded w-1/2"></div>
                </div>
            </div>
            <div class="flex gap-2">
                <div class="h-10 bg-white/10 rounded flex-1"></div>
                <div class="h-10 bg-white/10 rounded flex-1"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function quranManager() {
    return {
        loading: true,
        items: [],
        stats: {
            makkan: 0,
            madinan: 0
        },
        filters: {
            search: '',
            revelationPlace: ''
        },

        async init() {
            await this.loadData();
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadData() {
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`/su/portal/v1/admin/quran?${params}`, {
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
            }
        },

        filterSurahs() {
            this.loadData();
        }
    };
}
</script>
@endpush
