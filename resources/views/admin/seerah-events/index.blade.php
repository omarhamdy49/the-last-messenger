@extends('layouts.admin')

@section('title', 'أحداث السيرة')

@section('content')
<div x-data="seerahEventsManager()" x-init="init()">
    <!-- Page Header with Glassmorphism -->
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <!-- Animated Background Gradient -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 gradient-text">أحداث السيرة</h1>
                <p class="text-gray-400 flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="timeline" class="w-4 h-4"></i>
                    <span>إدارة الأحداث التاريخية في السيرة النبوية الشريفة</span>
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3 space-x-reverse">
                <!-- Export Button -->
                <button @click="exportEvents" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 space-x-reverse backdrop-blur-sm border border-white/20 hover:scale-105">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>تصدير</span>
                </button>

                <!-- Create Button -->
                <a href="{{ route('admin.seerah-events.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2 space-x-reverse hover:scale-105 animate-pulse-glow">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    <span>حدث جديد</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Events -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="timeline" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.total">{{ $events->total() }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">إجمالي الأحداث</p>
            </div>
        </div>

        <!-- High Importance -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-red-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                        <i data-lucide="star" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.highImportance">{{ $events->where('importance', 'high')->count() }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">أهمية عالية</p>
            </div>
        </div>

        <!-- Battles -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="swords" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.battles">{{ $events->where('event_type', 'battle')->count() }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">غزوات ومعارك</p>
            </div>
        </div>

        <!-- With Location -->
        <div class="rounded-xl p-6 relative overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'" group hover:scale-105 transition-transform duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/20 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white" x-text="stats.withLocation">{{ $events->whereNotNull('latitude')->count() }}</span>
                </div>
                <p class="text-sm text-gray-400 font-semibold">بموقع جغرافي</p>
            </div>
        </div>
    </div>

    <!-- Filters & Search Section -->
    <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="filters.search"
                        @input.debounce.500ms="filterEvents"
                        placeholder="البحث عن حدث... (العنوان، الوصف، الموقع)"
                        class="w-full pr-12 pl-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    >
                </div>
            </div>

            <!-- Event Type Filter -->
            <div>
                <select
                    x-model="filters.eventType"
                    @change="filterEvents"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                    <option value="">كل أنواع الأحداث</option>
                    <option value="birth">ولادة</option>
                    <option value="death">وفاة</option>
                    <option value="battle">غزوة/معركة</option>
                    <option value="migration">هجرة</option>
                    <option value="revelation">نزول وحي</option>
                    <option value="treaty">معاهدة</option>
                    <option value="marriage">زواج</option>
                    <option value="miracle">معجزة</option>
                    <option value="other">حدث آخر</option>
                </select>
            </div>

            <!-- Importance Filter -->
            <div>
                <select
                    x-model="filters.importance"
                    @change="filterEvents"
                    class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                    <option value="">كل مستويات الأهمية</option>
                    <option value="high">أهمية عالية</option>
                    <option value="medium">أهمية متوسطة</option>
                    <option value="low">أهمية منخفضة</option>
                </select>
            </div>
        </div>

        <!-- Advanced Filters Toggle -->
        <div class="mt-4 pt-4 border-t border-white/10" x-show="showAdvancedFilters" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Hijri Year -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">السنة الهجرية</label>
                    <input
                        type="number"
                        x-model="filters.hijriYear"
                        @change="filterEvents"
                        placeholder="مثال: 1"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">الموقع</label>
                    <input
                        type="text"
                        x-model="filters.location"
                        @change="filterEvents"
                        placeholder="مثال: مكة"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Sort By -->
                <div>
                    <label class="block text-sm font-semibold text-gray-400 mb-2">ترتيب حسب</label>
                    <select
                        x-model="filters.sortBy"
                        @change="filterEvents"
                        class="w-full px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'bg-white/5 border border-white/10 text-white' : 'glass-light-strong text-gray-900' focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="chronological">التسلسل الزمني</option>
                        <option value="importance">الأهمية</option>
                        <option value="created_at">تاريخ الإضافة</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filter Actions -->
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

    <!-- Timeline View -->
    <div class="rounded-2xl overflow-hidden transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <!-- Timeline Header -->
        <div class="bg-gradient-to-r from-blue-500/10 to-green-500/10 border-b border-white/10 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i data-lucide="timeline" class="w-5 h-5 text-blue-400"></i>
                    <span class="text-white font-bold text-lg">الخط الزمني للسيرة النبوية</span>
                </div>

                <div class="text-sm text-gray-400">
                    عرض <span class="text-white font-bold" x-text="events.length"></span> من أصل <span class="text-white font-bold">{{ $events->total() }}</span>
                </div>
            </div>
        </div>

        <!-- Timeline Body -->
        <div class="p-6">
            @forelse($events as $index => $event)
                <!-- Timeline Item -->
                <div class="relative pb-10 group" :class="{ 'pb-0': {{ $loop->last ? 'true' : 'false' }} }">
                    <!-- Vertical Line (except for last item) -->
                    @if(!$loop->last)
                        <div class="absolute right-6 top-12 bottom-0 w-0.5 bg-gradient-to-b from-blue-500/50 to-green-500/50 group-hover:from-blue-500 group-hover:to-green-500 transition-all"></div>
                    @endif

                    <div class="flex items-start space-x-4 space-x-reverse relative">
                        <!-- Timeline Dot with Event Type Icon -->
                        <div class="relative z-10 flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br {{ $event->event_type->color() === 'blue' ? 'from-blue-500 to-blue-600' : ($event->event_type->color() === 'red' ? 'from-red-500 to-red-600' : ($event->event_type->color() === 'green' ? 'from-green-500 to-green-600' : 'from-purple-500 to-purple-600')) }} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="{{ $event->event_type->icon() }}" class="w-5 h-5 text-white"></i>
                            </div>

                            <!-- Importance Badge -->
                            @if($event->importance->value === 'high')
                                <div class="absolute -top-1 -left-1 w-4 h-4 bg-yellow-500 rounded-full border-2 border-gray-900 flex items-center justify-center">
                                    <i data-lucide="star" class="w-2 h-2 text-white"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Event Card -->
                        <div class="flex-1 glass-dark rounded-xl p-6 hover:bg-white/10 transition-all duration-300 group-hover:scale-[1.02] border border-white/10 hover:border-white/20">
                            <!-- Event Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <!-- Event Title -->
                                    <a href="{{ route('admin.seerah-events.show', $event->id) }}" class="text-xl font-bold text-white hover:text-brand-accent transition-colors mb-2 block">
                                        {{ $event->title['ar'] ?? $event->title['en'] ?? 'بدون عنوان' }}
                                    </a>

                                    <!-- Event Type & Importance -->
                                    <div class="flex items-center space-x-2 space-x-reverse mb-3">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $event->event_type->color() === 'blue' ? 'bg-blue-500/20 text-blue-300' : ($event->event_type->color() === 'red' ? 'bg-red-500/20 text-red-300' : ($event->event_type->color() === 'green' ? 'bg-green-500/20 text-green-300' : 'bg-purple-500/20 text-purple-300')) }}">
                                            {{ $event->event_type->labelArabic() }}
                                        </span>

                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $event->importance->value === 'high' ? 'bg-red-500/20 text-red-300 border border-red-500/30' : ($event->importance->value === 'medium' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-gray-500/20 text-gray-300') }}">
                                            {{ $event->importance->labelArabic() }}
                                        </span>

                                        @if($event->is_featured)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-500/20 to-green-500/20 text-cyan-300 border border-cyan-500/30">
                                                <i data-lucide="star" class="w-3 h-3 ml-1"></i>
                                                مميز
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Event Description -->
                                    <p class="text-gray-400 text-sm leading-relaxed mb-4">
                                        {{ Str::limit($event->description['ar'] ?? $event->description['en'] ?? '', 200) }}
                                    </p>

                                    <!-- Event Details Grid -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <!-- Hijri Date -->
                                        @if($event->hijri_date)
                                            <div class="flex items-center space-x-2 space-x-reverse">
                                                <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                                    <i data-lucide="calendar" class="w-4 h-4 text-blue-400"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">هجري</p>
                                                    <p class="text-sm text-white font-semibold">{{ $event->hijri_date }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Gregorian Date -->
                                        @if($event->gregorian_date)
                                            <div class="flex items-center space-x-2 space-x-reverse">
                                                <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center">
                                                    <i data-lucide="calendar-days" class="w-4 h-4 text-green-400"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">ميلادي</p>
                                                    <p class="text-sm text-white font-semibold">{{ $event->gregorian_date->format('Y/m/d') }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Location -->
                                        @if($event->location)
                                            <div class="flex items-center space-x-2 space-x-reverse">
                                                <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                                    <i data-lucide="map-pin" class="w-4 h-4 text-purple-400"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">الموقع</p>
                                                    <p class="text-sm text-white font-semibold">{{ $event->location }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Views -->
                                        <div class="flex items-center space-x-2 space-x-reverse">
                                            <div class="w-8 h-8 rounded-lg bg-gray-500/20 flex items-center justify-center">
                                                <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500">مشاهدات</p>
                                                <p class="text-sm text-white font-semibold">{{ number_format($event->views_count ?? 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Event Image (if available) -->
                                @if($event->featured_image)
                                    <div class="mr-4 flex-shrink-0">
                                        <img src="{{ $event->featured_image }}" alt="{{ $event->title['ar'] ?? 'Event' }}" class="w-32 h-32 rounded-lg object-cover shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                                <div class="flex items-center space-x-2 space-x-reverse text-xs text-gray-500">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    <span>{{ $event->created_at->diffForHumans() }}</span>
                                </div>

                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <!-- View -->
                                    <a href="{{ route('admin.seerah-events.show', $event->id) }}" class="p-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition-all hover:scale-110" title="عرض">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.seerah-events.edit', $event->id) }}" class="p-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg transition-all hover:scale-110" title="تعديل">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Delete -->
                                    <button @click="deleteEvent({{ $event->id }})" class="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all hover:scale-110" title="حذف">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="timeline" class="w-12 h-12 text-gray-500"></i>
                    </div>
                    <p class="text-white font-bold text-xl mb-2">لا توجد أحداث</p>
                    <p class="text-gray-400 mb-6">ابدأ بإضافة حدث جديد في السيرة النبوية</p>
                    <a href="{{ route('admin.seerah-events.create') }}" class="px-6 py-3 gradient-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 inline-flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>حدث جديد</span>
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($events->hasPages())
            <div class="border-t border-white/10 p-6">
                <div class="flex items-center justify-between">
                    <!-- Page Info -->
                    <div class="text-sm text-gray-400">
                        عرض
                        <span class="font-bold text-white">{{ $events->firstItem() }}</span>
                        إلى
                        <span class="font-bold text-white">{{ $events->lastItem() }}</span>
                        من أصل
                        <span class="font-bold text-white">{{ $events->total() }}</span>
                        نتيجة
                    </div>

                    <!-- Pagination Links -->
                    <div class="flex items-center space-x-2 space-x-reverse">
                        {{-- Previous Page Link --}}
                        @if ($events->onFirstPage())
                            <span class="px-4 py-2 bg-white/5 text-gray-600 rounded-lg cursor-not-allowed">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($events->links()->elements[0] as $page => $url)
                            @if ($page == $events->currentPage())
                                <span class="px-4 py-2 gradient-primary text-white rounded-lg font-bold shadow-lg">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-all hover:scale-105">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                        @else
                            <span class="px-4 py-2 bg-white/5 text-gray-600 rounded-lg cursor-not-allowed">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div
        x-show="showDeleteModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="showDeleteModal = false"></div>

            <!-- Modal -->
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="glass-dark rounded-2xl p-8 max-w-md w-full relative z-10 border border-red-500/30"
            >
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-red-400"></i>
                    </div>

                    <h3 class="text-2xl font-bold text-white mb-2">تأكيد الحذف</h3>
                    <p class="text-gray-400 mb-6">هل أنت متأكد من حذف هذا الحدث؟ لا يمكن التراجع عن هذا الإجراء.</p>

                    <div class="flex items-center space-x-3 space-x-reverse">
                        <button
                            @click="showDeleteModal = false"
                            class="flex-1 px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold transition-all"
                        >
                            إلغاء
                        </button>
                        <button
                            @click="confirmDelete"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl"
                        >
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seerahEventsManager() {
    return {
        events: @json($events->items()),
        showAdvancedFilters: false,
        showDeleteModal: false,
        deleteEventId: null,
        stats: {
            total: {{ $events->total() }},
            highImportance: {{ $events->where('importance', 'high')->count() }},
            battles: {{ $events->where('event_type', 'battle')->count() }},
            withLocation: {{ $events->whereNotNull('latitude')->count() }}
        },
        filters: {
            search: '',
            eventType: '',
            importance: '',
            hijriYear: '',
            location: '',
            sortBy: 'chronological'
        },

        init() {
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        filterEvents() {
            // In a real application, this would make an AJAX request to filter events
            console.log('Filtering with:', this.filters);
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });

            if (params.toString()) {
                window.location.href = `{{ route('admin.seerah-events.index') }}?${params.toString()}`;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                eventType: '',
                importance: '',
                hijriYear: '',
                location: '',
                sortBy: 'chronological'
            };
            window.location.href = '{{ route('admin.seerah-events.index') }}';
        },

        deleteEvent(id) {
            this.deleteEventId = id;
            this.showDeleteModal = true;
        },

        confirmDelete() {
            if (!this.deleteEventId) return;

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/su/portal/v1/admin/seerah-events/${this.deleteEventId}`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        },

        exportEvents() {
            // Implement export functionality
            showToast('جاري تصدير الأحداث...', 'success');
        }
    };
}
</script>
@endpush
