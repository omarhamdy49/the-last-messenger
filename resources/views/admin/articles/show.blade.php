@extends('layouts.admin')

@section('title', 'عرض المقالة - ' . ($article->locales->firstWhere('locale', 'ar')->title ?? 'المقالة'))

@push('styles')
<style>
    /* Ken Burns Effect */
    @keyframes kenBurns {
        0% { transform: scale(1) translate(0, 0); }
        100% { transform: scale(1.15) translate(20px, -10px); }
    }

    .ken-burns {
        animation: kenBurns 30s ease-in-out infinite alternate;
    }

    /* Floating Animation */
    @keyframes floatUp {
        0%, 100% { transform: translateY(0) rotate(0deg); opacity: 1; }
        50% { transform: translateY(-20px) rotate(5deg); opacity: 0.8; }
    }

    .float-animation {
        animation: floatUp 3s ease-in-out infinite;
    }

    /* Shimmer Effect */
    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }

    .shimmer {
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }

    /* Counter Animation */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .count-up {
        animation: countUp 0.8s ease-out;
    }

    /* Progress Bar */
    .reading-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 4px;
        background: linear-gradient(90deg, #0056b3 0%, #218838 100%);
        transition: width 0.2s ease-out;
        z-index: 9999;
    }

    /* Sticky TOC */
    .sticky-toc {
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }

    /* Lightbox */
    .lightbox-overlay {
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: blur(10px);
    }

    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }

    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }
        .print-show {
            display: block !important;
        }
        body {
            background: white !important;
        }
    }

    /* Pulse Glow */
    @keyframes pulseGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.4); }
        50% { box-shadow: 0 0 0 10px rgba(0, 86, 179, 0); }
    }

    .pulse-glow {
        animation: pulseGlow 2s ease-in-out infinite;
    }

    /* Tooltip */
    [data-tooltip] {
        position: relative;
    }

    [data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 8px 12px;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        margin-bottom: 5px;
    }

    /* Prose Content Styling */
    .article-content {
        font-size: 18px;
        line-height: 1.8;
        color: #374151;
    }

    .dark .article-content {
        color: #D1D5DB;
    }

    .article-content h2 {
        font-size: 28px;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
        color: #111827;
    }

    .dark .article-content h2 {
        color: #F9FAFB;
    }

    .article-content h3 {
        font-size: 22px;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #1F2937;
    }

    .dark .article-content h3 {
        color: #E5E7EB;
    }

    .article-content p {
        margin-bottom: 1.25rem;
    }

    .article-content ul, .article-content ol {
        margin-bottom: 1.25rem;
        padding-right: 1.5rem;
    }

    .article-content li {
        margin-bottom: 0.5rem;
    }

    .article-content blockquote {
        border-right: 4px solid #0056b3;
        padding: 1rem 1.5rem;
        margin: 1.5rem 0;
        background: rgba(0, 86, 179, 0.05);
        border-radius: 8px;
        font-style: italic;
    }

    .dark .article-content blockquote {
        background: rgba(0, 86, 179, 0.15);
        border-right-color: #64CCC5;
    }

    .article-content img {
        border-radius: 12px;
        margin: 1.5rem 0;
        max-width: 100%;
        height: auto;
    }

    .article-content a {
        color: #0056b3;
        text-decoration: underline;
        transition: color 0.2s;
    }

    .dark .article-content a {
        color: #64CCC5;
    }

    .article-content code {
        background: #F3F4F6;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Courier New', monospace;
    }

    .dark .article-content code {
        background: #1F2937;
        color: #9CA3AF;
    }
</style>
@endpush

@section('content')
<div x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true',
    activeTab: 'ar',
    showLightbox: false,
    lightboxImage: '',
    lightboxCaption: '',
    copiedLink: false,

    init() {
        this.updateReadingProgress();
        window.addEventListener('scroll', () => this.updateReadingProgress());
        this.animateCounters();
    },

    updateReadingProgress() {
        const winScroll = document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        const progressBar = document.querySelector('.reading-progress');
        if (progressBar) {
            progressBar.style.width = scrolled + '%';
        }
    },

    animateCounters() {
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString('ar-SA');
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString('ar-SA');
                }
            };

            updateCounter();
        });
    },

    openLightbox(src, caption = '') {
        this.lightboxImage = src;
        this.lightboxCaption = caption;
        this.showLightbox = true;
        document.body.style.overflow = 'hidden';
    },

    closeLightbox() {
        this.showLightbox = false;
        document.body.style.overflow = 'auto';
    },

    copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            this.copiedLink = true;
            setTimeout(() => this.copiedLink = false, 2000);
        });
    },

    shareToSocial(platform) {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent('{{ $article->locales->firstWhere('locale', 'ar')->title ?? '' }}');

        const urls = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
            twitter: `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
            linkedin: `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`,
            whatsapp: `https://wa.me/?text=${title}%20${url}`,
            telegram: `https://t.me/share/url?url=${url}&text=${title}`
        };

        if (urls[platform]) {
            window.open(urls[platform], '_blank', 'width=600,height=400');
        }
    },

    printArticle() {
        window.print();
    },

    downloadPDF() {
        // This would typically call a backend endpoint
        alert('PDF download functionality would be implemented here');
    },

    toggleFeature(type) {
        // This would typically make an AJAX call to update the article
        console.log('Toggle ' + type);
    }
}">

    <!-- Reading Progress Bar -->
    <div class="reading-progress no-print"></div>

    <!-- Hero Section with Featured Image -->
    <div class="relative rounded-3xl overflow-hidden mb-8 no-print" style="height: 500px;">
        @if($article->thumb)
        <img src="{{ asset('storage/' . $article->thumb) }}"
             alt="{{ $article->locales->firstWhere('locale', 'ar')->title ?? '' }}"
             class="absolute inset-0 w-full h-full object-cover ken-burns">
        @else
        <div class="absolute inset-0 bg-gradient-to-br from-[#0056b3] to-[#218838]"></div>
        @endif

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>

        <!-- Content -->
        <div class="absolute inset-0 flex flex-col justify-end p-8 md:p-12">
            <!-- Breadcrumbs -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 space-x-reverse text-sm">
                    <li><a href="{{ route('admin.articles.index') }}" class="text-white/70 hover:text-white transition-colors">المقالات</a></li>
                    <li><i data-lucide="chevron-left" class="w-4 h-4 text-white/50"></i></li>
                    <li><span class="text-white font-semibold">{{ $article->category?->name['ar'] ?? $article->category?->name['en'] ?? 'بدون تصنيف' }}</span></li>
                </ol>
            </nav>

            <!-- Title -->
            <h1 class="text-4xl md:text-6xl font-black text-white mb-4 drop-shadow-2xl">
                {{ $article->locales->firstWhere('locale', 'ar')->title ?? 'بدون عنوان' }}
            </h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-4 text-white/90 mb-6">
                <div class="flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>{{ $article->writer?->name ?? 'غير محدد' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>{{ $article->created_at->format('Y-m-d') }}</span>
                </div>
                @if($article->hijri_date)
                <div class="flex items-center gap-2">
                    <i data-lucide="moon" class="w-4 h-4"></i>
                    <span>{{ $article->hijri_date }}</span>
                </div>
                @endif
                <div class="flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>{{ $article->read_time }} دقيقة</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.articles.edit', $article) }}"
                   class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl font-semibold hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                    <i data-lucide="edit" class="w-4 h-4"></i>
                    <span>تعديل المقالة</span>
                </a>

                <button @click="copyLink()"
                        class="px-6 py-3 glass text-white rounded-xl font-semibold hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                    <i data-lucide="link" class="w-4 h-4"></i>
                    <span x-text="copiedLink ? 'تم النسخ!' : 'نسخ الرابط'"></span>
                </button>

                <button @click="printArticle()"
                        class="px-6 py-3 glass text-white rounded-xl font-semibold hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>طباعة</span>
                </button>

                <div class="relative" x-data="{ showShare: false }">
                    <button @click="showShare = !showShare"
                            class="px-6 py-3 glass text-white rounded-xl font-semibold hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                        <i data-lucide="share-2" class="w-4 h-4"></i>
                        <span>مشاركة</span>
                    </button>

                    <!-- Share Dropdown -->
                    <div x-show="showShare"
                         @click.away="showShare = false"
                         x-transition
                         class="absolute top-full left-0 mt-2 glass-dark rounded-xl p-3 shadow-2xl z-50 min-w-[200px]">
                        <button @click="shareToSocial('facebook')" class="w-full flex items-center gap-3 px-4 py-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                            <i data-lucide="facebook" class="w-4 h-4"></i>
                            <span>Facebook</span>
                        </button>
                        <button @click="shareToSocial('twitter')" class="w-full flex items-center gap-3 px-4 py-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                            <i data-lucide="twitter" class="w-4 h-4"></i>
                            <span>Twitter</span>
                        </button>
                        <button @click="shareToSocial('linkedin')" class="w-full flex items-center gap-3 px-4 py-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                            <i data-lucide="linkedin" class="w-4 h-4"></i>
                            <span>LinkedIn</span>
                        </button>
                        <button @click="shareToSocial('whatsapp')" class="w-full flex items-center gap-3 px-4 py-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            <span>WhatsApp</span>
                        </button>
                        <button @click="shareToSocial('telegram')" class="w-full flex items-center gap-3 px-4 py-2 text-white hover:bg-white/10 rounded-lg transition-colors">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Telegram</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Dashboard -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8 no-print">
        <!-- Views -->
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="إجمالي المشاهدات">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center pulse-glow">
                    <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                </div>
                <div class="text-green-400 text-sm font-semibold flex items-center gap-1">
                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                    <span>+12%</span>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">المشاهدات</h3>
            <p class="text-2xl font-black text-white counter-value count-up" data-target="{{ $article->views }}">0</p>
        </div>

        <!-- Shares -->
        @php
            $statistics = $article->statistics ?? [];
            $shares = $statistics['shares'] ?? 0;
            $likes = $statistics['likes'] ?? 0;
            $bookmarks = $statistics['bookmarks'] ?? 0;
            $engagement_rate = $statistics['engagement_rate'] ?? 0;
        @endphp
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="عدد المشاركات">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="share-2" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">المشاركات</h3>
            <p class="text-2xl font-black text-white counter-value count-up" data-target="{{ $shares }}">0</p>
        </div>

        <!-- Likes -->
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="عدد الإعجابات">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                    <i data-lucide="heart" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">الإعجابات</h3>
            <p class="text-2xl font-black text-white counter-value count-up" data-target="{{ $likes }}">0</p>
        </div>

        <!-- Bookmarks -->
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="عدد الحفظات">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                    <i data-lucide="bookmark" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">الحفظات</h3>
            <p class="text-2xl font-black text-white counter-value count-up" data-target="{{ $bookmarks }}">0</p>
        </div>

        <!-- Read Time -->
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="وقت القراءة المتوقع">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">وقت القراءة</h3>
            <p class="text-2xl font-black text-white">{{ $article->read_time }} <span class="text-sm">دقيقة</span></p>
        </div>

        <!-- Languages -->
        <div class="rounded-2xl p-6 glass-dark hover:scale-105 transition-transform cursor-pointer" data-tooltip="عدد اللغات المتوفرة">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                    <i data-lucide="languages" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-sm text-gray-400 mb-1">اللغات</h3>
            <p class="text-2xl font-black text-white">{{ $article->locales->where('is_ready', true)->count() }}/{{ $article->locales->count() }}</p>
        </div>
    </div>

    <!-- Admin Quick Actions -->
    <div class="glass-dark rounded-2xl p-6 mb-8 no-print">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="settings" class="w-6 h-6"></i>
            إجراءات سريعة
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button @click="toggleFeature('featured')"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl transition-all {{ $article->is_featured ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : 'bg-white/5 hover:bg-white/10' }}">
                <i data-lucide="star" class="w-6 h-6 text-white"></i>
                <span class="text-sm text-white font-semibold">{{ $article->is_featured ? 'مميزة' : 'جعلها مميزة' }}</span>
            </button>

            <button @click="toggleFeature('pinned')"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl transition-all {{ $article->is_pinned ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : 'bg-white/5 hover:bg-white/10' }}">
                <i data-lucide="pin" class="w-6 h-6 text-white"></i>
                <span class="text-sm text-white font-semibold">{{ $article->is_pinned ? 'مثبتة' : 'تثبيت' }}</span>
            </button>

            <button class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-all">
                <i data-lucide="archive" class="w-6 h-6 text-white"></i>
                <span class="text-sm text-white font-semibold">أرشفة</span>
            </button>

            <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذه المقالة؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 rounded-xl bg-red-500/20 hover:bg-red-500/30 transition-all">
                    <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
                    <span class="text-sm text-red-400 font-semibold">حذف</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Multi-Language Content Viewer -->
    <div class="glass-dark rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="languages" class="w-6 h-6"></i>
            المحتوى متعدد اللغات
        </h2>

        <!-- Language Tabs -->
        <div class="flex flex-wrap gap-2 mb-6 border-b border-white/10 pb-4">
            @foreach([
                'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'dir' => 'rtl'],
                'en' => ['name' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
                'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
                'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'dir' => 'ltr'],
                'ur' => ['name' => 'اردو', 'flag' => '🇵🇰', 'dir' => 'rtl']
            ] as $code => $lang)
                @php
                    $locale = $article->locales->firstWhere('locale', $code);
                    $isReady = $locale && $locale->is_ready;
                @endphp
                <button @click="activeTab = '{{ $code }}'"
                        :class="activeTab === '{{ $code }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        class="px-6 py-3 rounded-full font-semibold transition-all duration-300 flex items-center gap-2 relative">
                    <span class="text-xl">{{ $lang['flag'] }}</span>
                    <span>{{ $lang['name'] }}</span>
                    @if($isReady)
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-900"></span>
                    @else
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-gray-900"></span>
                    @endif
                </button>
            @endforeach
        </div>

        <!-- Content for Each Language -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $langCode)
            @php
                $locale = $article->locales->firstWhere('locale', $langCode);
                $isRTL = in_array($langCode, ['ar', 'ur']);
            @endphp
            <div x-show="activeTab === '{{ $langCode }}'"
                 x-transition
                 dir="{{ $isRTL ? 'rtl' : 'ltr' }}">
                @if($locale)
                    <div class="space-y-6">
                        <!-- Status Badge -->
                        <div class="flex items-center gap-3">
                            @if($locale->is_ready)
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-sm font-bold">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    جاهز للنشر
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-sm font-bold">
                                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                    قيد الإعداد
                                </span>
                            @endif
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-400 mb-2">العنوان</label>
                            <h3 class="text-3xl font-black text-white">{{ $locale->title }}</h3>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-400 mb-2">الرابط الثابت (Slug)</label>
                            <p class="text-white font-mono px-4 py-3 bg-white/5 rounded-lg">{{ $locale->slug }}</p>
                        </div>

                        <!-- Description -->
                        @if($locale->description)
                        <div>
                            <label class="block text-sm font-semibold text-gray-400 mb-2">الوصف</label>
                            <p class="text-gray-300 px-4 py-3 bg-white/5 rounded-lg">{{ $locale->description }}</p>
                        </div>
                        @endif

                        <!-- Excerpt -->
                        @if($locale->excerpt)
                        <div>
                            <label class="block text-sm font-semibold text-gray-400 mb-2">الملخص</label>
                            <div class="text-gray-300 px-6 py-4 bg-white/5 rounded-xl border-r-4 border-[#64CCC5]">
                                {{ $locale->excerpt }}
                            </div>
                        </div>
                        @endif

                        <!-- Content -->
                        @if($locale->content)
                            @php
                                $body = is_array($locale->content) ? ($locale->content['body'] ?? null) : $locale->content;
                                $toc = is_array($locale->content) ? ($locale->content['toc'] ?? null) : null;
                            @endphp

                            @if($body)
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <!-- Table of Contents (Sticky) -->
                                @if($toc)
                                <div class="lg:col-span-3">
                                    <div class="sticky-toc glass-dark rounded-xl p-4">
                                        <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                            <i data-lucide="list" class="w-5 h-5"></i>
                                            جدول المحتويات
                                        </h4>
                                        <nav class="space-y-2">
                                            @foreach($toc as $index => $item)
                                                <a href="#section-{{ $index }}"
                                                   class="block text-sm text-gray-300 hover:text-[#64CCC5] transition-colors py-2 px-3 rounded-lg hover:bg-white/5">
                                                    {{ $item['title'] ?? $item }}
                                                </a>
                                            @endforeach
                                        </nav>
                                    </div>
                                </div>
                                @endif

                                <!-- Main Content -->
                                <div class="{{ $toc ? 'lg:col-span-9' : 'lg:col-span-12' }}">
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-xl">
                                        <article class="article-content prose prose-lg max-w-none" :class="darkMode ? 'prose-invert' : ''">
                                            {!! $body !!}
                                        </article>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endif

                        <!-- SEO Metadata -->
                        @if($locale->seo_metadata)
                        <div class="mt-8">
                            <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                <i data-lucide="search" class="w-5 h-5"></i>
                                معلومات SEO
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($locale->seo_metadata as $key => $value)
                                    <div class="bg-white/5 rounded-lg p-4">
                                        <label class="block text-xs font-semibold text-gray-400 mb-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                                        <p class="text-white text-sm">{{ is_array($value) ? json_encode($value) : $value }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-12">
                        <i data-lucide="file-x" class="w-16 h-16 text-gray-500 mx-auto mb-4"></i>
                        <p class="text-gray-400 text-lg">المحتوى غير متوفر بهذه اللغة</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Media Gallery -->
    @if($article->gallery && $article->gallery->count() > 0)
    <div class="glass-dark rounded-2xl p-6 mb-8 no-print">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="images" class="w-6 h-6"></i>
            معرض الصور ({{ $article->gallery->count() }})
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($article->gallery as $image)
                @php
                    $caption = is_array($image->caption)
                        ? ($image->caption['ar'] ?? $image->caption['en'] ?? '')
                        : ($image->caption ?? '');
                @endphp
                <div class="relative group cursor-pointer rounded-xl overflow-hidden aspect-video"
                     @click="openLightbox('{{ asset('storage/' . $image->image) }}', '{{ addslashes($caption) }}')">
                    <img src="{{ asset('storage/' . $image->image) }}"
                         alt="{{ $caption ?: 'Gallery image' }}"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <i data-lucide="zoom-in" class="w-8 h-8 text-white"></i>
                    </div>

                    @if($caption)
                    <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
                        <p class="text-white text-sm">{{ $caption }}</p>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- References -->
    @if($article->references && count($article->references) > 0)
    <div class="glass-dark rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="book-open" class="w-6 h-6"></i>
            المراجع والمصادر ({{ count($article->references) }})
        </h2>

        <div class="space-y-3">
            @foreach($article->references as $index => $reference)
                <div class="bg-white/5 rounded-xl p-4 hover:bg-white/10 transition-colors" x-data="{ expanded: false }">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <h4 class="text-white font-semibold">{{ $reference['title'] ?? 'مرجع' }}</h4>
                                @if(isset($reference['type']))
                                    <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded text-xs">{{ $reference['type'] }}</span>
                                @endif
                            </div>

                            @if(isset($reference['url']))
                            <a href="{{ $reference['url'] }}" target="_blank" class="text-[#64CCC5] hover:underline text-sm flex items-center gap-2">
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                                {{ $reference['url'] }}
                            </a>
                            @endif

                            @if(isset($reference['description']) && $reference['description'])
                            <div x-show="expanded" x-transition class="mt-3 text-gray-300 text-sm">
                                {{ $reference['description'] }}
                            </div>
                            @endif
                        </div>

                        @if(isset($reference['description']) && $reference['description'])
                        <button @click="expanded = !expanded" class="text-gray-400 hover:text-white transition-colors">
                            <i data-lucide="chevron-down" class="w-5 h-5" x-show="!expanded"></i>
                            <i data-lucide="chevron-up" class="w-5 h-5" x-show="expanded" style="display: none;"></i>
                        </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Article Metadata Panel -->
    <div class="glass-dark rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i data-lucide="info" class="w-6 h-6"></i>
            معلومات المقالة
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">الكاتب</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-white/5 rounded-lg">
                    @if($article->writer)
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold">
                            {{ substr($article->writer->name, 0, 1) }}
                        </div>
                        <span class="text-white font-semibold">{{ $article->writer->name }}</span>
                    @else
                        <span class="text-gray-400">غير محدد</span>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">التصنيف الرئيسي</label>
                <div class="px-4 py-3 bg-white/5 rounded-lg">
                    @if($article->category)
                        <span class="text-white font-semibold">{{ $article->category->name['ar'] ?? $article->category->name['en'] ?? 'غير محدد' }}</span>
                    @else
                        <span class="text-gray-400">غير محدد</span>
                    @endif
                </div>
            </div>

            @if($article->categories && $article->categories->count() > 0)
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">التصنيفات الفرعية</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($article->categories as $cat)
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm font-semibold">
                            {{ $cat->name['ar'] ?? $cat->name['en'] ?? 'تصنيف' }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">تاريخ الإنشاء</label>
                <div class="px-4 py-3 bg-white/5 rounded-lg">
                    <span class="text-white font-semibold">{{ $article->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">آخر تحديث</label>
                <div class="px-4 py-3 bg-white/5 rounded-lg">
                    <span class="text-white font-semibold">{{ $article->updated_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">معرّف المقالة</label>
                <div class="px-4 py-3 bg-white/5 rounded-lg font-mono">
                    <span class="text-white font-semibold">#{{ $article->id }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div x-show="showLightbox"
         x-transition
         @keydown.escape.window="closeLightbox()"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 no-print"
         style="display: none;">
        <div class="absolute inset-0 lightbox-overlay" @click="closeLightbox()"></div>

        <div class="relative z-10 max-w-6xl w-full">
            <button @click="closeLightbox()"
                    class="absolute top-4 right-4 w-12 h-12 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>

            <img :src="lightboxImage" :alt="lightboxCaption" class="w-full h-auto rounded-xl shadow-2xl">

            <div x-show="lightboxCaption" class="mt-4 text-center">
                <p class="text-white text-lg" x-text="lightboxCaption"></p>
            </div>
        </div>
    </div>

    <!-- Print-only Header -->
    <div class="print-show hidden">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black mb-4">{{ $article->locales->firstWhere('locale', 'ar')->title ?? 'المقالة' }}</h1>
            <p class="text-gray-600">{{ $article->writer?->name ?? 'كاتب غير محدد' }}</p>
            <p class="text-gray-500 text-sm">{{ $article->created_at->format('Y-m-d') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Smooth scroll for TOC links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Re-initialize icons after Alpine updates
    document.addEventListener('alpine:initialized', () => {
        lucide.createIcons();
    });
</script>
@endpush
