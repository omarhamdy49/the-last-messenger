@extends('layouts.public')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $booksJson = json_encode($books, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $explorerBase = url('/hadith/explorer');
@endphp

@section('title', $collection['name'])
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($collection['description'] ?? $collection['name']), 155))

@push('head')
<style type="text/tailwindcss">
    @theme {
        --font-family-cairo: 'Cairo', sans-serif;

        --color-brand: #0056b3;
        --color-secondary: #218838;
        --color-accent: #64CCC5;

        --animate-fade-in-up: fade-in-up 0.6s ease-out;
        --animate-fade-in: fade-in 0.8s ease-out;
        --animate-slide-in-right: slide-in-right 0.5s ease-out;
        --animate-shimmer: shimmer 2s infinite;
        --animate-pulse-glow: pulse-glow 2s ease-in-out infinite;
        --animate-float: float 3s ease-in-out infinite;
        --animate-scale-in: scale-in 0.4s ease-out;
        --animate-bounce-soft: bounce-soft 1s ease-in-out;
    }

    @keyframes fade-in-up {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes fade-in {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }

    @keyframes slide-in-right {
        0% { opacity: 0; transform: translateX(-30px); }
        100% { opacity: 1; transform: translateX(0); }
    }

    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }

    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(0, 86, 179, 0.4); }
        50% { box-shadow: 0 0 30px rgba(0, 86, 179, 0.8); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    @keyframes scale-in {
        0% { opacity: 0; transform: scale(0.9); }
        100% { opacity: 1; transform: scale(1); }
    }

    @keyframes bounce-soft {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }

    .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .glass-light {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .custom-scrollbar::-webkit-scrollbar {
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        border-radius: 10px;
    }

    .gradient-text {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .book-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .book-card:hover {
        transform: translateY(-4px) scale(1.02);
    }

    .hadith-card {
        position: relative;
        overflow: hidden;
    }

    .hadith-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(100, 204, 197, 0.2), transparent);
        transition: left 0.5s;
    }

    .hadith-card:hover::before {
        left: 100%;
    }

    .chapter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .chapter-grid {
            grid-template-columns: 1fr;
        }
    }

    .progress-ring {
        transition: stroke-dashoffset 0.35s;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }

    .floating-badge {
        animation: float 3s ease-in-out infinite;
    }

    .book-stats-card {
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.1) 0%, rgba(33, 136, 56, 0.1) 100%);
        border-left: 4px solid;
        border-image: linear-gradient(135deg, #0056b3 0%, #218838 100%) 1;
    }
</style>
@endpush

@section('content')

<div x-data="{
    darkMode: ($store.theme && $store.theme.dark) ?? (localStorage.getItem('darkMode') === 'true'),
    bookmarked: false,
    activeTab: 'about',

    collectionId: {{ (int) $collection['id'] }},
    explorerBase: '{{ $explorerBase }}',
    searchQuery: '',
    books: {{ $booksJson }},

    get filteredBooks() {
        const q = this.searchQuery.trim().toLowerCase();
        if (!q) return this.books;
        return this.books.filter(b =>
            (b.title || '').toLowerCase().includes(q) ||
            (b.titleEn || '').toLowerCase().includes(q) ||
            String(b.number).includes(q)
        );
    },

    chapterUrlTemplate: '{{ route('public.hadith.chapter', ['slug' => $collection['slug'], 'chapter' => '__NUM__']) }}',

    bookHref(book) {
        // Books without a usable number can't be addressed by the chapter route —
        // fall back to the explorer filtered by this collection.
        if (book.number > 0) {
            return this.chapterUrlTemplate.replace('__NUM__', book.number);
        }
        return this.explorerBase + '?collection=' + this.collectionId;
    },

    toggleBookmark() {
        this.bookmarked = !this.bookmarked;
        const message = this.bookmarked ? 'تمت الإضافة إلى المفضلة' : 'تمت الإزالة من المفضلة';
        if (typeof window.toast === 'function') { window.toast(message, 'success'); }
    },

    scrollToSection(section) {
        document.getElementById(section)?.scrollIntoView({ behavior: 'smooth' });
    },

    init() {
        if (this.$store.theme) { this.$watch('$store.theme.dark', v => this.darkMode = v); }
        this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        this.$watch('activeTab', () => this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }));
        this.$watch('filteredBooks', () => this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }));
    }
}">

    <main class="py-8 md:py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Breadcrumb -->
            <nav class="flex mb-8 text-sm animate-[fade-in-up_0.6s_ease-out]" aria-label="Breadcrumb">
                <ol class="inline-flex items-center gap-2">
                    <li>
                        <a href="{{ url('/') }}" class="flex items-center gap-1 transition-colors" :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            <i data-lucide="home" class="w-4 h-4"></i>
                            الرئيسية
                        </a>
                    </li>
                    <i data-lucide="chevron-left" class="w-4 h-4 text-gray-400"></i>
                    <li>
                        <a href="{{ route('public.hadith') }}" class="transition-colors" :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">الحديث</a>
                    </li>
                    <i data-lucide="chevron-left" class="w-4 h-4 text-gray-400"></i>
                    <li>
                        <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $collection['name'] }}</span>
                    </li>
                </ol>
            </nav>

            <!-- Hero Section with Book Cover -->
            <section class="relative mb-12 rounded-3xl overflow-hidden shadow-2xl animate-[scale-in_0.4s_ease-out]">
                <!-- Background Pattern -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#0056b3] to-[#218838] opacity-95"></div>
                <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

                <div class="relative z-10 px-6 md:px-12 py-12 md:py-16">
                    <div class="flex flex-col md:flex-row items-center gap-8">
                        <!-- Book Cover Illustration -->
                        <div class="floating-badge">
                            <div class="w-48 h-64 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-50 shadow-2xl border-4 border-amber-200 flex items-center justify-center relative overflow-hidden">
                                <!-- Decorative Islamic Pattern -->
                                <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 100 100&quot;%3E%3Cpath d=&quot;M50 10 L60 30 L80 30 L65 45 L70 65 L50 50 L30 65 L35 45 L20 30 L40 30 Z&quot; fill=&quot;%23d97706&quot; opacity=&quot;0.3&quot;/%3E%3C/svg%3E'); background-size: 40px 40px;"></div>
                                <div class="relative text-center p-6">
                                    <i data-lucide="book-open" class="w-20 h-20 text-amber-600 mb-4 mx-auto"></i>
                                    <div class="text-amber-800 font-bold text-xl">{{ $collection['nameAr'] }}</div>
                                </div>
                                <!-- Book Spine Effect -->
                                <div class="absolute left-0 top-0 bottom-0 w-3 bg-gradient-to-b from-amber-600 to-amber-800"></div>
                            </div>
                        </div>

                        <!-- Book Info -->
                        <div class="flex-1 text-white text-center md:text-right">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm mb-4">
                                <i data-lucide="bookmark" class="w-4 h-4"></i>
                                <span class="text-sm font-medium">من كتب الحديث</span>
                            </div>

                            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black mb-4">{{ $collection['nameAr'] }}</h1>

                            @if ($collection['nameEn'])
                                <p class="text-xl md:text-2xl text-white/90 mb-2 leading-relaxed">
                                    {{ $collection['nameEn'] }}
                                </p>
                            @endif

                            @if ($collection['author'])
                                <p class="text-xl md:text-2xl text-white/90 mb-6 leading-relaxed">
                                    جمعه {{ $collection['author'] }}
                                </p>
                            @endif

                            <!-- Book Stats -->
                            <div class="flex flex-wrap justify-center md:justify-start gap-6 mb-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold">{{ number_format($collection['hadithCount']) }}</div>
                                    <div class="text-sm text-white/80">حديث</div>
                                </div>
                                <div class="w-px bg-white/30"></div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold">{{ number_format($collection['bookCount']) }}</div>
                                    <div class="text-sm text-white/80">كتاب</div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                                <button @click="activeTab = 'chapters'; scrollToSection('chapters')"
                                        class="px-8 py-3 bg-white text-[#0056b3] rounded-full font-bold shadow-xl hover:shadow-2xl hover:scale-105 transition-all flex items-center gap-2">
                                    <i data-lucide="list" class="w-5 h-5"></i>
                                    تصفح الأبواب
                                </button>
                                <button @click="toggleBookmark()"
                                        class="px-8 py-3 rounded-full font-bold shadow-lg hover:scale-105 transition-all flex items-center gap-2"
                                        :class="bookmarked ? 'bg-[#64CCC5] text-gray-900' : 'bg-white/20 backdrop-blur-sm text-white hover:bg-white/30'">
                                    <i :data-lucide="bookmarked ? 'bookmark-check' : 'bookmark-plus'" class="w-5 h-5"></i>
                                    <span x-text="bookmarked ? 'في المفضلة' : 'إضافة للمفضلة'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tab Navigation -->
            <div class="mb-8 animate-[fade-in-up_0.6s_ease-out_0.2s] opacity-0" style="animation-fill-mode: forwards;">
                <div class="flex gap-4 overflow-x-auto custom-scrollbar pb-2">
                    <button @click="activeTab = 'about'"
                            class="px-6 py-3 rounded-full font-semibold whitespace-nowrap transition-all"
                            :class="activeTab === 'about' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
                        <div class="flex items-center gap-2">
                            <i data-lucide="info" class="w-5 h-5"></i>
                            عن الكتاب
                        </div>
                    </button>
                    <button @click="activeTab = 'chapters'; scrollToSection('chapters')"
                            class="px-6 py-3 rounded-full font-semibold whitespace-nowrap transition-all"
                            :class="activeTab === 'chapters' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
                        <div class="flex items-center gap-2">
                            <i data-lucide="folder-tree" class="w-5 h-5"></i>
                            الأبواب
                        </div>
                    </button>
                    <button @click="activeTab = 'author'"
                            class="px-6 py-3 rounded-full font-semibold whitespace-nowrap transition-all"
                            :class="activeTab === 'author' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
                        <div class="flex items-center gap-2">
                            <i data-lucide="user" class="w-5 h-5"></i>
                            عن المؤلف
                        </div>
                    </button>
                </div>
            </div>

            <!-- About Section -->
            <section x-show="activeTab === 'about'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="mb-12">
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <!-- Stat Card 1 -->
                    <div class="book-stats-card rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <i data-lucide="book-marked" class="w-8 h-8 text-[#0056b3]"></i>
                            <div class="text-right">
                                <div class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($collection['hadithCount']) }}</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الأحاديث</div>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="book-stats-card rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <i data-lucide="folder-tree" class="w-8 h-8 text-[#218838]"></i>
                            <div class="text-right">
                                <div class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($collection['bookCount']) }}</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">كتاباً فقهياً</div>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="book-stats-card rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <i data-lucide="shield-check" class="w-8 h-8 text-[#64CCC5]"></i>
                            <div class="text-right">
                                <div class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">عالٍ</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">درجة الصحة</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description Card -->
                <div class="rounded-2xl p-8 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                            <i data-lucide="book-open-text" class="w-6 h-6 text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">نبذة عن الكتاب</h2>
                    </div>

                    <div class="prose prose-lg max-w-none space-y-6" :class="darkMode ? 'prose-invert' : ''">
                        @if ($collection['description'])
                            <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ $collection['description'] }}
                            </p>
                        @else
                            <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                يُعد كتاب <strong class="gradient-text">{{ $collection['nameAr'] }}</strong> من مصادر الحديث النبوي الشريف، @if ($collection['author']) جمعه {{ $collection['author'] }}، @endif ويضم <strong>{{ number_format($collection['hadithCount']) }}</strong> حديثاً موزعة على <strong>{{ number_format($collection['bookCount']) }}</strong> كتاباً وباباً فقهياً.
                            </p>
                        @endif

                        <div class="p-6 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gradient-to-br from-blue-50 to-green-50'">
                            <div class="flex items-start gap-4">
                                <i data-lucide="lightbulb" class="w-6 h-6 text-[#64CCC5] flex-shrink-0 mt-1"></i>
                                <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    رُتب الكتاب على الأبواب الفقهية، مما يجعله مرجعًا أساسيًا للفقهاء والمحدثين على حد سواء. تصفّح فهرس الأبواب أدناه للوصول إلى الأحاديث.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="mt-8 grid md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">دقة عالية في الأسانيد</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">شرط صارم في الرواة</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">ترتيب فقهي محكم</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أبواب مرتبة على الفقه</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">علم العلل والمقارنات</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ذكر الاختلافات في الأسانيد</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">من كتب الحديث المعتمدة</div>
                                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مرجع أساسي للمحدثين</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Chapters Section -->
            <section x-show="activeTab === 'chapters'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     id="chapters"
                     class="mb-12">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#218838] to-[#64CCC5] flex items-center justify-center">
                            <i data-lucide="folder-tree" class="w-6 h-6 text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">فهرس الأبواب</h2>
                    </div>
                    <div class="text-sm px-4 py-2 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">
                        <span x-text="books.length"></span> كتاباً
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="mb-6 flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input type="search"
                               x-model="searchQuery"
                               placeholder="ابحث في الأبواب..."
                               class="w-full px-5 py-3 pr-12 rounded-full text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                               :class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900 shadow-md'" />
                        <i data-lucide="search" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                    <button @click="searchQuery = ''"
                            class="px-6 py-3 rounded-full font-semibold flex items-center justify-center gap-2"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md'">
                        <i data-lucide="filter" class="w-5 h-5"></i>
                        تصفية
                    </button>
                </div>

                <!-- Chapters Grid -->
                <div class="chapter-grid" x-show="filteredBooks.length > 0">
                    <template x-for="(chapter, index) in filteredBooks" :key="chapter.id">
                        <a :href="bookHref(chapter)"
                           class="book-card block rounded-2xl p-6 shadow-lg group"
                           :class="darkMode ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-50'">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="text-sm px-3 py-1 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                    <span x-text="chapter.number > 0 ? chapter.number : (index + 1)"></span>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold mb-2 group-hover:text-[#64CCC5] transition-colors"
                                :class="darkMode ? 'text-white' : 'text-gray-900'"
                                x-text="chapter.title || ('كتاب ' + (chapter.number > 0 ? chapter.number : (index + 1)))"></h3>

                            <div class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                                <span x-text="Number(chapter.hadithCount).toLocaleString() + ' حديث'"></span>
                            </div>

                            <div class="mt-4 pt-4 border-t flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                                <span class="text-sm font-medium text-[#64CCC5]">تصفح الأحاديث</span>
                                <i data-lucide="arrow-left" class="w-5 h-5 text-[#64CCC5] group-hover:translate-x-[-4px] transition-transform"></i>
                            </div>
                        </a>
                    </template>
                </div>

                <!-- Empty (client-side search) -->
                <div x-show="filteredBooks.length === 0" x-cloak
                     class="rounded-2xl p-12 text-center shadow-lg" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                    <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                    <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">لا توجد نتائج</h3>
                    <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لم نعثر على أبواب مطابقة لبحثك.</p>
                    <button @click="searchQuery = ''"
                            class="px-6 py-2 rounded-full font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        مسح البحث
                    </button>
                </div>

                @if (count($books) === 0)
                    <div class="rounded-2xl p-12 text-center shadow-lg" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                        <i data-lucide="book-x" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                        <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">لا توجد أبواب</h3>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لم تتوفر أبواب لهذا الكتاب بعد.</p>
                    </div>
                @endif

                {{-- Server pagination (books) --}}
                @isset($paginator)
                    @if ($paginator)
                        <div class="mt-8">
                            <x-site.pagination :paginator="$paginator" />
                        </div>
                    @endif
                @endisset
            </section>

            <!-- Author Section -->
            <section x-show="activeTab === 'author'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="mb-12">
                <div class="rounded-2xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                    <!-- Author Header -->
                    <div class="relative h-48 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                    </div>

                    <div class="px-8 pb-8">
                        <!-- Author Avatar -->
                        <div class="flex justify-center -mt-16 mb-6">
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-amber-100 to-amber-50 shadow-2xl border-4 border-white flex items-center justify-center">
                                <i data-lucide="user" class="w-16 h-16 text-amber-600"></i>
                            </div>
                        </div>

                        <!-- Author Info -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $collection['author'] ?: 'المؤلف' }}
                            </h2>
                            <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                مؤلف {{ $collection['nameAr'] }}
                            </p>
                        </div>

                        <!-- Biography -->
                        <div class="prose prose-lg max-w-none space-y-6 mb-8" :class="darkMode ? 'prose-invert' : ''">
                            <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                @if ($collection['author'])
                                    هو مؤلف كتاب <strong>{{ $collection['nameAr'] }}</strong>، أحد مصادر الحديث النبوي الشريف، الذي يضم {{ number_format($collection['hadithCount']) }} حديثاً.
                                @else
                                    يضم هذا الكتاب {{ number_format($collection['hadithCount']) }} حديثاً موزعة على {{ number_format($collection['bookCount']) }} كتاباً.
                                @endif
                            </p>

                            <div class="p-6 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gradient-to-br from-blue-50 to-green-50'">
                                <div class="flex items-start gap-4">
                                    <i data-lucide="award" class="w-6 h-6 text-[#64CCC5] flex-shrink-0 mt-1"></i>
                                    <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        من كتب الحديث المعتمدة لدى أهل العلم، ويُعد مرجعًا للفقهاء والمحدثين.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Achievements -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="flex items-start gap-4 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="book-marked" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">الكتاب</h3>
                                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $collection['nameAr'] }}@if ($collection['nameEn']) ({{ $collection['nameEn'] }})@endif</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">عدد الأحاديث</h3>
                                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ number_format($collection['hadithCount']) }} حديث</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="folder-tree" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">عدد الكتب</h3>
                                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ number_format($collection['bookCount']) }} كتاباً وباباً</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 p-4 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="list" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">تصفح المحتوى</h3>
                                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">انتقل إلى تبويب الأبواب لقراءة الأحاديث</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
