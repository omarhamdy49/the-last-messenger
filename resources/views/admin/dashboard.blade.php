@extends('layouts.admin')

@section('title', 'لوحة المعلومات المتقدمة')
@section('page-title', 'لوحة المعلومات المتقدمة')

@push('styles')
<style>
    /* Advanced Animations */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes meshMove {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    @keyframes kenBurns {
        0%, 100% { transform: scale(1) translateX(0) translateY(0); }
        50% { transform: scale(1.1) translateX(-20px) translateY(-10px); }
    }

    @keyframes particleFloat {
        0%, 100% { opacity: 0; }
        10% { opacity: 0.3; }
        50% { transform: translateY(-100vh) translateX(50px) scale(0); opacity: 0; }
    }

    @keyframes shimmerSweep {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes pulseRing {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 86, 179, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 86, 179, 0); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(30px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Hero Mesh Background */
    .hero-mesh-bg {
        background:
            radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 50% 50%, rgba(100, 204, 197, 0.1) 0%, transparent 50%);
        background-size: 200% 200%;
        animation: meshMove 20s ease-in-out infinite;
    }

    /* Floating Orbs */
    .floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.6;
        animation: float 15s ease-in-out infinite;
    }

    .floating-orb-1 {
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(0, 86, 179, 0.3) 0%, transparent 70%);
        top: -100px;
        right: -100px;
        animation-delay: 0s;
    }

    .floating-orb-2 {
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(33, 136, 56, 0.3) 0%, transparent 70%);
        bottom: -80px;
        left: -80px;
        animation-delay: 5s;
    }

    .floating-orb-3 {
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(100, 204, 197, 0.2) 0%, transparent 70%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        animation-delay: 10s;
    }

    /* Premium Card */
    .premium-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .premium-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s ease-in-out;
    }

    .premium-card:hover::before {
        left: 100%;
    }

    .premium-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 60px rgba(0, 86, 179, 0.2);
    }

    /* Stat Number Animation */
    .stat-number {
        animation: countUp 0.8s ease-out forwards;
    }

    /* Glass effect */
    .glass-premium {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .glass-premium-light {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    /* Sparkline mini chart */
    .sparkline-canvas {
        width: 100%;
        height: 40px;
    }

    /* Custom scrollbar for activity feed */
    .activity-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .activity-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .activity-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        border-radius: 10px;
    }

    .activity-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
    }

    /* Stagger animation delays */
    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }
    .stagger-5 { animation-delay: 0.5s; }
    .stagger-6 { animation-delay: 0.6s; }

    /* Skeleton loader */
    .skeleton {
        background: linear-gradient(90deg,
            rgba(255, 255, 255, 0.05) 25%,
            rgba(255, 255, 255, 0.1) 50%,
            rgba(255, 255, 255, 0.05) 75%
        );
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }

    /* Light mode skeleton */
    [data-light-mode="true"] .skeleton {
        background: linear-gradient(90deg,
            rgba(0, 0, 0, 0.05) 25%,
            rgba(0, 0, 0, 0.08) 50%,
            rgba(0, 0, 0, 0.05) 75%
        );
        background-size: 200% 100%;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Progress ring */
    .progress-ring {
        transform: rotate(-90deg);
    }

    .progress-ring-circle {
        transition: stroke-dashoffset 0.35s;
        transform-origin: 50% 50%;
    }

    /* Calendar heatmap */
    .heatmap-cell {
        transition: all 0.2s ease;
    }

    .heatmap-cell:hover {
        transform: scale(1.2);
        cursor: pointer;
    }

    /* Magnetic button effect */
    .magnetic-btn {
        position: relative;
        transition: all 0.3s ease;
    }

    .magnetic-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0, 86, 179, 0.3);
    }

    /* Scroll indicator */
    .scroll-indicator {
        animation: float 2s ease-in-out infinite;
    }

    /* Chart tooltip custom style */
    .chartjs-tooltip {
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 8px;
        padding: 12px;
        font-family: 'Cairo', sans-serif;
    }

    /* Activity pulse */
    .activity-pulse {
        animation: pulseRing 2s ease-in-out infinite;
    }

    /* Fade in animations */
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .slide-in-left {
        animation: slideInLeft 0.6s ease-out forwards;
        opacity: 0;
    }

    .scale-in {
        animation: scaleIn 0.6s ease-out forwards;
        opacity: 0;
    }
</style>
@endpush

@section('content')
<div x-data="enhancedDashboard()" x-init="init()" class="space-y-8">

    {{-- SECTION 1: HERO SECTION WITH LIVE METRICS --}}
    <section class="relative overflow-hidden rounded-3xl p-8 md:p-12 hero-mesh-bg"
             :class="darkMode ? 'bg-gradient-to-br from-gray-900 to-gray-800 shadow-2xl' : 'bg-gradient-to-br from-blue-50 to-emerald-50 shadow-xl border border-blue-100'">

        {{-- Floating Orbs Background --}}
        <div class="floating-orb floating-orb-1"></div>
        <div class="floating-orb floating-orb-2"></div>
        <div class="floating-orb floating-orb-3"></div>

        <div class="relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                {{-- Left: Welcome & Central Metric --}}
                <div class="space-y-6">
                    {{-- Welcome Message --}}
                    <div class="fade-in-up stagger-1">
                        <h1 class="text-4xl md:text-5xl font-black mb-2 gradient-text">
                            مرحباً بك، {{ auth()->user()->name ?? 'Admin' }}
                        </h1>
                        <p class="text-lg" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                            <i data-lucide="calendar" class="w-5 h-5 inline-block ml-2"></i>
                            <span x-text="getCurrentDate()"></span>
                        </p>
                        <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            <i data-lucide="clock" class="w-4 h-4 inline-block ml-1"></i>
                            <span x-text="getCurrentTime()"></span>
                        </p>
                    </div>

                    {{-- Central Metric Display --}}
                    <div class="rounded-2xl p-8 fade-in-up stagger-2"
                         :class="darkMode ? 'glass-premium' : 'glass-light-soft'">
                        <div class="text-center">
                            <p class="text-sm font-bold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-700'">
                                إجمالي المحتوى
                            </p>
                            <h2 class="text-6xl md:text-7xl font-black gradient-text mb-4">
                                <span x-text="animatedTotalContent">0</span>
                            </h2>
                            <div class="flex items-center justify-center gap-2 text-emerald-500">
                                <i data-lucide="trending-up" class="w-5 h-5"></i>
                                <span class="font-bold">+<span x-text="stats.growth">0</span>% هذا الأسبوع</span>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Action Floating Buttons --}}
                    <div class="flex flex-wrap gap-3 fade-in-up stagger-3">
                        <a href="{{ route('admin.articles.create') }}"
                           class="magnetic-btn px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                            <i data-lucide="file-plus" class="w-5 h-5"></i>
                            <span>مقال جديد</span>
                        </a>
                        <a href="{{ route('admin.hadith.create') }}"
                           class="magnetic-btn px-6 py-3 rounded-xl glass-premium text-white font-bold border border-white/20 hover:border-white/40 transition-all flex items-center gap-2">
                            <i data-lucide="scroll-text" class="w-5 h-5"></i>
                            <span>حديث جديد</span>
                        </a>
                        <a href="{{ route('admin.simple-media.index') }}"
                           class="magnetic-btn px-6 py-3 rounded-xl glass-premium text-white font-bold border border-white/20 hover:border-white/40 transition-all flex items-center gap-2">
                            <i data-lucide="image" class="w-5 h-5"></i>
                            <span>مكتبة الوسائط</span>
                        </a>
                    </div>
                </div>

                {{-- Right: Real-Time Activity Stream --}}
                <div class="rounded-2xl p-6 fade-in-up stagger-4 h-96"
                     :class="darkMode ? 'glass-premium' : 'glass-light-soft'">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <div class="w-2 h-2 bg-emerald-500 rounded-full activity-pulse"></div>
                            النشاطات المباشرة
                        </h3>
                        <button @click="loadActivity()" class="text-sm hover:scale-110 transition-transform">
                            <i data-lucide="refresh-cw" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                        </button>
                    </div>

                    <div class="space-y-2 overflow-y-auto activity-scrollbar h-80" x-show="!loadingActivity">
                        <template x-for="(activity, index) in recentActivity" :key="activity.id">
                            <div class="flex items-start gap-3 p-3 rounded-lg transition-all hover:scale-105"
                                 :class="darkMode ? 'hover:bg-white/5' : 'hover:bg-gray-100/50'">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-md"
                                         :class="getActivityColor(activity.type)">
                                        <i :data-lucide="getActivityIcon(activity.type)" class="w-5 h-5 text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="activity.title"></p>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="activity.status || activity.type"></p>
                                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'" x-text="formatTimeAgo(activity.created_at)"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="loadingActivity" class="space-y-3">
                        <div x-for="i in 5" class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full skeleton"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-4 skeleton rounded w-3/4"></div>
                                <div class="h-3 skeleton rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll Indicator --}}
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 scroll-indicator">
            <i data-lucide="chevron-down" class="w-6 h-6" :class="darkMode ? 'text-white/50' : 'text-gray-400'"></i>
        </div>
    </section>

    {{-- SECTION 2: ADVANCED STATISTICS GRID --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Articles Card --}}
        <div class="premium-card rounded-2xl p-6 fade-in-up stagger-1"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-blue-100/50 shadow-sm'">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/50">
                    <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
                </div>
                <div class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold"
                     :class="stats.articles.change >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'">
                    <i :data-lucide="stats.articles.change >= 0 ? 'trending-up' : 'trending-down'" class="w-3 h-3"></i>
                    <span x-text="Math.abs(stats.articles.change) + '%'"></span>
                </div>
            </div>

            <h3 class="text-4xl font-black mb-2 stat-number">
                <span class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-400 dark:to-blue-200 bg-clip-text text-transparent"
                      x-text="stats.articles.total">0</span>
            </h3>
            <p class="font-bold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المقالات</p>

            {{-- Mini Stats --}}
            <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                <div class="px-2 py-1 rounded-lg" :class="darkMode ? 'bg-emerald-500/10' : 'bg-emerald-50'">
                    <span :class="darkMode ? 'text-emerald-400' : 'text-emerald-700'" x-text="stats.articles.published + ' منشور'"></span>
                </div>
                <div class="px-2 py-1 rounded-lg" :class="darkMode ? 'bg-yellow-500/10' : 'bg-yellow-50'">
                    <span :class="darkMode ? 'text-yellow-400' : 'text-yellow-700'" x-text="stats.articles.draft + ' مسودة'"></span>
                </div>
            </div>

            {{-- Sparkline Chart --}}
            <div class="mt-4">
                <canvas :id="'sparkline-articles'" class="sparkline-canvas"></canvas>
            </div>
        </div>

        {{-- Hadith Card --}}
        <div class="premium-card rounded-2xl p-6 fade-in-up stagger-2"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-emerald-100/50 shadow-sm'">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/50">
                    <i data-lucide="scroll-text" class="w-7 h-7 text-white"></i>
                </div>
                <div class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold"
                     :class="stats.hadith.change >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'">
                    <i :data-lucide="stats.hadith.change >= 0 ? 'trending-up' : 'trending-down'" class="w-3 h-3"></i>
                    <span x-text="Math.abs(stats.hadith.change) + '%'"></span>
                </div>
            </div>

            <h3 class="text-4xl font-black mb-2 stat-number">
                <span class="bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-emerald-400 dark:to-emerald-200 bg-clip-text text-transparent"
                      x-text="stats.hadith.total">0</span>
            </h3>
            <p class="font-bold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأحاديث</p>

            {{-- Sahih Percentage --}}
            <div class="mb-3">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الأحاديث الصحيحة</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.hadith.percentage + '%'"></span>
                </div>
                <div class="w-full h-2 rounded-full overflow-hidden" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 transition-all duration-1000"
                         :style="`width: ${stats.hadith.percentage}%`"></div>
                </div>
            </div>

            {{-- Sparkline Chart --}}
            <div class="mt-4">
                <canvas :id="'sparkline-hadith'" class="sparkline-canvas"></canvas>
            </div>
        </div>

        {{-- Media Programs Card --}}
        <div class="premium-card rounded-2xl p-6 fade-in-up stagger-3"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-purple-100/50 shadow-sm'">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/50">
                    <i data-lucide="video" class="w-7 h-7 text-white"></i>
                </div>
                <div class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold"
                     :class="stats.media.change >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'">
                    <i :data-lucide="stats.media.change >= 0 ? 'trending-up' : 'trending-down'" class="w-3 h-3"></i>
                    <span x-text="Math.abs(stats.media.change) + '%'"></span>
                </div>
            </div>

            <h3 class="text-4xl font-black mb-2 stat-number">
                <span class="bg-gradient-to-r from-purple-600 to-purple-800 dark:from-purple-400 dark:to-purple-200 bg-clip-text text-transparent"
                      x-text="stats.media.total">0</span>
            </h3>
            <p class="font-bold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">البرامج الإعلامية</p>

            {{-- Total Views --}}
            <div class="flex items-center gap-2 mb-3 text-sm">
                <i data-lucide="eye" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span class="font-bold" x-text="formatNumber(stats.media.views)"></span> مشاهدة
                </span>
            </div>

            {{-- Sparkline Chart --}}
            <div class="mt-4">
                <canvas :id="'sparkline-media'" class="sparkline-canvas"></canvas>
            </div>
        </div>

        {{-- Active Users Card --}}
        <div class="premium-card rounded-2xl p-6 fade-in-up stagger-4"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-cyan-100/50 shadow-sm'">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#64CCC5] to-[#4BA5A3] flex items-center justify-center shadow-lg shadow-[#64CCC5]/50">
                    <i data-lucide="users" class="w-7 h-7 text-white"></i>
                </div>
                <div class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold"
                     :class="stats.users.change >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'">
                    <i :data-lucide="stats.users.change >= 0 ? 'trending-up' : 'trending-down'" class="w-3 h-3"></i>
                    <span x-text="Math.abs(stats.users.change) + '%'"></span>
                </div>
            </div>

            <h3 class="text-4xl font-black mb-2 stat-number">
                <span class="bg-gradient-to-r from-[#64CCC5] to-[#4BA5A3] dark:from-[#64CCC5] dark:to-[#8BE5E0] bg-clip-text text-transparent"
                      x-text="stats.users.total">0</span>
            </h3>
            <p class="font-bold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المستخدمون</p>

            {{-- New Users This Week --}}
            <div class="flex items-center gap-2 mb-3 text-sm">
                <i data-lucide="user-plus" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span class="font-bold text-emerald-500" x-text="stats.users.new"></span> مستخدم جديد
                </span>
            </div>

            {{-- Sparkline Chart --}}
            <div class="mt-4">
                <canvas :id="'sparkline-users'" class="sparkline-canvas"></canvas>
            </div>
        </div>
    </section>

    {{-- SECTION 3: INTERACTIVE CHARTS --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Content Growth Chart --}}
        <div class="lg:col-span-2 rounded-2xl p-6 scale-in stagger-1"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-gray-100/50 shadow-lg'">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">نمو المحتوى</h3>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر 30 يوماً</p>
                </div>
                <div class="flex gap-2">
                    <button @click="chartPeriod = 'week'"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                            :class="chartPeriod === 'week' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        أسبوعي
                    </button>
                    <button @click="chartPeriod = 'month'"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                            :class="chartPeriod === 'month' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        شهري
                    </button>
                </div>
            </div>

            <div class="relative h-80">
                <canvas id="contentGrowthChart"></canvas>
            </div>
        </div>

        {{-- Module Distribution Chart --}}
        <div class="rounded-2xl p-6 scale-in stagger-2"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-gray-100/50 shadow-lg'">
            <div class="mb-6">
                <h3 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">توزيع المحتوى</h3>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">حسب النوع</p>
            </div>

            <div class="relative h-72 flex items-center justify-center">
                <canvas id="moduleDistributionChart"></canvas>
            </div>
        </div>
    </section>

    {{-- SECTION 4: QUICK ANALYTICS CARDS --}}
    <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Top Article --}}
        <div @click="navigateToTopArticle()"
             class="premium-card rounded-xl p-4 cursor-pointer fade-in-up stagger-1"
             :class="darkMode ? 'bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-blue-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="trophy" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-2xl font-black mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.topArticle.views">0</p>
            <p class="text-xs font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الأكثر مشاهدة</p>
        </div>

        {{-- Most Active Day --}}
        <div class="premium-card rounded-xl p-4 fade-in-up stagger-2"
             :class="darkMode ? 'bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border border-emerald-500/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-emerald-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="calendar-check" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-2xl font-black mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.mostActiveDay.count">0</p>
            <p class="text-xs font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أنشط يوم</p>
        </div>

        {{-- Popular Category --}}
        <div class="premium-card rounded-xl p-4 fade-in-up stagger-3"
             :class="darkMode ? 'bg-gradient-to-br from-purple-500/10 to-purple-600/5 border border-purple-500/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-purple-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="folder-open" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-2xl font-black mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.popularCategory.count">0</p>
            <p class="text-xs font-bold truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="quickAnalytics.popularCategory.name">التصنيف الأشهر</p>
        </div>

        {{-- Top Narrator --}}
        <div class="premium-card rounded-xl p-4 fade-in-up stagger-4"
             :class="darkMode ? 'bg-gradient-to-br from-[#64CCC5]/10 to-[#4BA5A3]/5 border border-[#64CCC5]/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-cyan-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#64CCC5] to-[#4BA5A3] flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="user-check" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-2xl font-black mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.topNarrator.count">0</p>
            <p class="text-xs font-bold truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أكثر راوٍ</p>
        </div>

        {{-- Latest Upload --}}
        <div class="premium-card rounded-xl p-4 fade-in-up stagger-5"
             :class="darkMode ? 'bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border border-yellow-500/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-yellow-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="upload" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-xs font-black mb-1 truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.latestUpload.time">الآن</p>
            <p class="text-xs font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر إضافة</p>
        </div>

        {{-- Pending Reviews --}}
        <div class="premium-card rounded-xl p-4 fade-in-up stagger-6"
             :class="darkMode ? 'bg-gradient-to-br from-red-500/10 to-red-600/5 border border-red-500/20' : 'bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border border-red-200/60 shadow-sm'">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
            </div>
            <p class="text-2xl font-black mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="quickAnalytics.pendingReviews">0</p>
            <p class="text-xs font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">بانتظار المراجعة</p>
        </div>
    </section>

    {{-- SECTION 5 & 6: MODULE HEALTH STATUS & RECENT CONTENT PREVIEW --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Module Health Status --}}
        <div class="rounded-2xl p-6 slide-in-left stagger-1"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-gray-100/50 shadow-lg'">
            <div class="mb-6">
                <h3 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">صحة الوحدات</h3>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">حالة الأنظمة</p>
            </div>

            <div class="space-y-3">
                <template x-for="module in moduleHealth" :key="module.name">
                    <div @click="navigateToModule(module.route)"
                         class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all hover:scale-105"
                         :class="darkMode ? 'hover:bg-white/5' : 'hover:bg-gray-50'">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                 :class="module.status === 'healthy' ? 'bg-emerald-500' : module.status === 'warning' ? 'bg-yellow-500' : 'bg-red-500'">
                                <i :data-lucide="module.icon" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="module.name"></p>
                                <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="module.count + ' عنصر'"></p>
                            </div>
                        </div>
                        <div>
                            <i :data-lucide="module.status === 'healthy' ? 'check-circle' : module.status === 'warning' ? 'alert-triangle' : 'x-circle'"
                               class="w-5 h-5"
                               :class="module.status === 'healthy' ? 'text-emerald-500' : module.status === 'warning' ? 'text-yellow-500' : 'text-red-500'"></i>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Recent Content Preview (Carousel) --}}
        <div class="lg:col-span-2 rounded-2xl p-6 slide-in-left stagger-2"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-gray-100/50 shadow-lg'">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">المحتوى الأخير</h3>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر الإضافات</p>
                </div>
                <div class="flex gap-2">
                    <button @click="contentTab = 'articles'"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                            :class="contentTab === 'articles' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        مقالات
                    </button>
                    <button @click="contentTab = 'hadith'"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                            :class="contentTab === 'hadith' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        أحاديث
                    </button>
                    <button @click="contentTab = 'media'"
                            class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                            :class="contentTab === 'media' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        برامج
                    </button>
                </div>
            </div>

            <div class="relative">
                {{-- Articles --}}
                <div x-show="contentTab === 'articles'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <template x-for="article in recentArticles.slice(0, 6)" :key="article.id">
                        <div class="premium-card rounded-lg overflow-hidden cursor-pointer"
                             :class="darkMode ? 'bg-gray-900/50' : 'glass-light-soft border border-gray-100/50'">
                            <div class="aspect-video bg-gradient-to-br from-blue-500/20 to-blue-600/10 flex items-center justify-center">
                                <i data-lucide="file-text" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="article.title"></p>
                                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatTimeAgo(article.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Hadith --}}
                <div x-show="contentTab === 'hadith'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <template x-for="hadith in recentHadith.slice(0, 6)" :key="hadith.id">
                        <div class="premium-card rounded-lg overflow-hidden cursor-pointer"
                             :class="darkMode ? 'bg-gray-900/50' : 'glass-light-soft border border-gray-100/50'">
                            <div class="aspect-video bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center">
                                <i data-lucide="scroll-text" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="hadith.title"></p>
                                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatTimeAgo(hadith.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Media --}}
                <div x-show="contentTab === 'media'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <template x-for="program in recentMedia.slice(0, 6)" :key="program.id">
                        <div class="premium-card rounded-lg overflow-hidden cursor-pointer"
                             :class="darkMode ? 'bg-gray-900/50' : 'glass-light-soft border border-gray-100/50'">
                            <div class="aspect-video bg-gradient-to-br from-purple-500/20 to-purple-600/10 flex items-center justify-center relative">
                                <i data-lucide="video" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity bg-black/50">
                                    <div class="w-12 h-12 rounded-full bg-white/90 flex items-center justify-center">
                                        <i data-lucide="play" class="w-6 h-6 text-gray-900"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="program.title"></p>
                                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatTimeAgo(program.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 7: PERFORMANCE METRICS --}}
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- API Response Time --}}
        <div class="rounded-xl p-6 fade-in-up stagger-1"
             :class="darkMode ? 'bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700/50' : 'glass-light-soft border border-blue-100/50 shadow-lg'">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="zap" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="performance.apiResponseTime">0</span>ms
                    </p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">زمن الاستجابة</p>
                </div>
            </div>
            <div class="w-full h-1 rounded-full overflow-hidden" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'">
                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-1000"
                     :style="`width: ${Math.min(performance.apiResponseTime / 10, 100)}%`"></div>
            </div>
        </div>

        {{-- Database Size --}}
        <div class="rounded-xl p-6 fade-in-up stagger-2"
             :class="darkMode ? 'bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700/50' : 'glass-light-soft border border-emerald-100/50 shadow-lg'">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                    <i data-lucide="database" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="performance.databaseSize">0</span>MB
                    </p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">حجم قاعدة البيانات</p>
                </div>
            </div>
            <div class="flex items-center gap-1 text-xs">
                <i data-lucide="trending-up" class="w-3 h-3 text-emerald-500"></i>
                <span class="text-emerald-500 font-bold">+2.3%</span>
                <span :class="darkMode ? 'text-gray-500' : 'text-gray-600'">هذا الشهر</span>
            </div>
        </div>

        {{-- Cache Hit Rate --}}
        <div class="rounded-xl p-6 fade-in-up stagger-3"
             :class="darkMode ? 'bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700/50' : 'glass-light-soft border border-purple-100/50 shadow-lg'">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="gauge" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="performance.cacheHitRate">0</span>%
                    </p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">معدل الذاكرة المؤقتة</p>
                </div>
            </div>
            <div class="relative w-16 h-16 mx-auto">
                <svg class="transform -rotate-90 w-16 h-16">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" :class="darkMode ? 'text-gray-700' : 'text-gray-200'" stroke-width="4" fill="none" />
                    <circle cx="32" cy="32" r="28" stroke="url(#purpleGradient)" stroke-width="4" fill="none"
                            :stroke-dasharray="`${2 * Math.PI * 28}`"
                            :stroke-dashoffset="`${2 * Math.PI * 28 * (1 - performance.cacheHitRate / 100)}`"
                            class="transition-all duration-1000" />
                    <defs>
                        <linearGradient id="purpleGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#8B5CF6" />
                            <stop offset="100%" stop-color="#A78BFA" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
        </div>

        {{-- Active Sessions --}}
        <div class="rounded-xl p-6 fade-in-up stagger-4"
             :class="darkMode ? 'bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700/50' : 'glass-light-soft border border-cyan-100/50 shadow-lg'">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#64CCC5] to-[#4BA5A3] flex items-center justify-center">
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <span x-text="performance.activeSessions">0</span>
                    </p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جلسات نشطة</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <template x-for="i in 10" :key="i">
                    <div class="flex-1 h-8 rounded"
                         :class="i <= Math.floor(performance.activeSessions / 10) ? 'bg-gradient-to-t from-[#64CCC5] to-[#4BA5A3]' : (darkMode ? 'bg-gray-700' : 'bg-gray-200')">
                    </div>
                </template>
            </div>
        </div>
    </section>

    {{-- SECTION 8: CALENDAR WIDGET --}}
    <section class="rounded-2xl p-6 scale-in"
             :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border border-gray-100/50 shadow-lg'">
        <div class="mb-6">
            <h3 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="calendar" class="w-5 h-5 inline-block ml-2"></i>
                تقويم النشر
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <span x-text="calendarCurrentMonth"></span> - اضغط على اليوم لعرض المحتوى المنشور
            </p>
        </div>

        <div class="grid grid-cols-7 gap-2 mb-2">
            <template x-for="day in ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']" :key="day">
                <div class="text-center text-xs font-bold py-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span x-text="day"></span>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <template x-for="day in calendarDays" :key="day.date">
                <div @click="selectCalendarDay(day)"
                     class="aspect-square rounded-lg flex flex-col items-center justify-center cursor-pointer transition-all hover:scale-110 heatmap-cell border"
                     :class="[
                        day.isToday ? 'ring-2 ring-[#64CCC5] ring-offset-2' : '',
                        day.hasContent ? (darkMode ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] border-blue-500/30' : 'bg-gradient-to-br from-[#0056b3]/10 to-[#218838]/10 border border-blue-200/60') : (darkMode ? 'bg-gray-700/50 border-gray-600/30' : 'glass-light-soft border-gray-100/50'),
                        day.isOtherMonth ? 'opacity-30' : ''
                     ]">
                    <span class="text-sm font-bold" :class="day.hasContent ? 'text-white' : (darkMode ? 'text-gray-300' : 'text-gray-900')" x-text="day.day"></span>
                    <template x-if="day.hasContent">
                        <span class="text-xs mt-1" :class="darkMode ? 'text-white/70' : 'text-gray-700'" x-text="day.contentCount"></span>
                    </template>
                </div>
            </template>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
function enhancedDashboard() {
    return {
        // State management
        darkMode: localStorage.getItem('darkMode') === 'true',
        loading: true,
        loadingActivity: true,

        // Statistics
        stats: {
            articles: { total: 0, published: 0, draft: 0, archived: 0, change: 12 },
            hadith: { total: 0, percentage: 0, change: 8 },
            media: { total: 0, views: 0, change: 15 },
            users: { total: 0, new: 0, change: 24 },
            growth: 18
        },

        // Central metric
        animatedTotalContent: 0,
        totalContent: 0,

        // Charts
        contentGrowthChart: null,
        moduleDistributionChart: null,
        chartPeriod: 'month',
        sparklineCharts: {},

        // Activity feed
        recentActivity: [],
        activityPage: 1,

        // Quick analytics
        quickAnalytics: {
            topArticle: { views: 0, title: '' },
            mostActiveDay: { count: 0, date: '' },
            popularCategory: { count: 0, name: '' },
            topNarrator: { count: 0, name: '' },
            latestUpload: { time: 'الآن', title: '' },
            pendingReviews: 0
        },

        // Module health
        moduleHealth: [],

        // Recent content
        recentArticles: [],
        recentHadith: [],
        recentMedia: [],
        contentTab: 'articles',

        // Performance metrics
        performance: {
            apiResponseTime: 0,
            databaseSize: 0,
            cacheHitRate: 0,
            activeSessions: 0
        },

        // Calendar
        calendarDays: [],
        calendarCurrentMonth: '',

        // Initialize
        async init() {
            console.log('Enhanced Dashboard initializing...');

            // Load all data in parallel
            await Promise.all([
                this.loadStatistics(),
                this.loadActivity(),
                this.loadQuickAnalytics(),
                this.loadModuleHealth(),
                this.loadRecentContent(),
                this.loadPerformanceMetrics()
            ]);

            // Initialize charts after data is loaded
            await this.$nextTick();
            this.initCharts();
            this.initSparklines();

            // Initialize calendar
            this.generateCalendar();

            // Animate total content counter
            this.animateTotalContent();

            // Start real-time updates
            this.startActivityPolling();
            this.startTimeUpdates();

            // Initialize Lucide icons
            lucide.createIcons();

            this.loading = false;
        },

        // Load Statistics
        async loadStatistics() {
            try {
                const response = await fetch('/su/portal/v1/admin/dashboard/statistics');
                const data = await response.json();

                if (data.success) {
                    // Map backend data to state
                    this.stats.articles.total = data.data.articles?.total || {{ $stats['articles'] ?? 0 }};
                    this.stats.articles.published = data.data.articles?.published || 0;
                    this.stats.articles.draft = data.data.articles?.draft || 0;
                    this.stats.articles.archived = data.data.articles?.archived || 0;

                    this.stats.hadith.total = data.data.hadith?.total || {{ $stats['hadith'] ?? 0 }};
                    this.stats.hadith.percentage = 92; // Default sahih percentage

                    this.stats.media.total = data.data.media_programs?.total || {{ $stats['media'] ?? 0 }};
                    this.stats.media.views = Math.floor(this.stats.media.total * 1500); // Estimate

                    this.stats.users.total = data.data.users?.total || {{ $stats['users'] ?? 0 }};
                    this.stats.users.new = Math.floor(this.stats.users.total * 0.05); // 5% new users estimate

                    // Calculate total content
                    this.totalContent = this.stats.articles.total + this.stats.hadith.total + this.stats.media.total;
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
                // Use fallback data from Blade
                this.stats.articles.total = {{ $stats['articles'] ?? 0 }};
                this.stats.hadith.total = {{ $stats['hadith'] ?? 0 }};
                this.stats.media.total = {{ $stats['media'] ?? 0 }};
                this.stats.users.total = {{ $stats['users'] ?? 0 }};
                this.totalContent = this.stats.articles.total + this.stats.hadith.total + this.stats.media.total;
            }
        },

        // Load Activity
        async loadActivity() {
            this.loadingActivity = true;
            try {
                const response = await fetch('/su/portal/v1/admin/dashboard/activity?limit=10');
                const data = await response.json();

                if (data.success && data.data) {
                    this.recentActivity = data.data;
                } else {
                    // Use fallback data from Blade
                    this.recentActivity = @json($recentActivity ?? []);
                }
            } catch (error) {
                console.error('Error loading activity:', error);
                this.recentActivity = @json($recentActivity ?? []);
            } finally {
                this.loadingActivity = false;
            }
        },

        // Load Quick Analytics
        async loadQuickAnalytics() {
            try {
                const response = await fetch('/su/portal/v1/admin/dashboard/popular?limit=5');
                const data = await response.json();

                if (data.success && data.data) {
                    // Map popular content data
                    if (data.data.articles && data.data.articles.length > 0) {
                        this.quickAnalytics.topArticle = {
                            views: data.data.articles[0].views || 0,
                            title: data.data.articles[0].title || ''
                        };
                    }
                }

                // Set default values
                this.quickAnalytics.mostActiveDay = { count: 127, date: 'الإثنين' };
                this.quickAnalytics.popularCategory = { count: 89, name: 'السيرة النبوية' };
                this.quickAnalytics.topNarrator = { count: 156, name: 'أبو هريرة' };
                this.quickAnalytics.latestUpload = { time: 'منذ 5 دقائق', title: '' };
                this.quickAnalytics.pendingReviews = this.stats.articles.draft;
            } catch (error) {
                console.error('Error loading quick analytics:', error);
            }
        },

        // Load Module Health
        async loadModuleHealth() {
            this.moduleHealth = [
                {
                    name: 'المقالات',
                    icon: 'file-text',
                    count: this.stats.articles.total,
                    status: this.stats.articles.total > 100 ? 'healthy' : this.stats.articles.total > 50 ? 'warning' : 'critical',
                    route: '{{ route("admin.articles.index") }}'
                },
                {
                    name: 'الأحاديث',
                    icon: 'scroll-text',
                    count: this.stats.hadith.total,
                    status: this.stats.hadith.total > 100 ? 'healthy' : this.stats.hadith.total > 50 ? 'warning' : 'critical',
                    route: '{{ route("admin.hadith.index") }}'
                },
                {
                    name: 'الوسائط',
                    icon: 'image',
                    count: this.stats.media.total,
                    status: this.stats.media.total > 50 ? 'healthy' : this.stats.media.total > 20 ? 'warning' : 'critical',
                    route: '{{ route("admin.simple-media.index") }}'
                },
                {
                    name: 'الصحابة',
                    icon: 'users',
                    count: 85,
                    status: 'healthy',
                    route: '{{ route("admin.sahaba.index") }}'
                },
                {
                    name: 'السيرة',
                    icon: 'clock',
                    count: 124,
                    status: 'healthy',
                    route: '#'
                },
                {
                    name: 'القرآن',
                    icon: 'book-open',
                    count: 6236,
                    status: 'healthy',
                    route: '#'
                }
            ];
        },

        // Load Recent Content
        async loadRecentContent() {
            try {
                // Load recent articles
                const articlesResponse = await fetch('/api/articles?per_page=10&sort=-created_at');
                if (articlesResponse.ok) {
                    const articlesData = await articlesResponse.json();
                    if (articlesData.success && articlesData.data) {
                        this.recentArticles = articlesData.data.data || articlesData.data;
                    }
                }

                // Fallback to activity data
                if (this.recentArticles.length === 0) {
                    this.recentArticles = this.recentActivity.filter(a => a.type === 'article');
                }

                this.recentHadith = this.recentActivity.filter(a => a.type === 'hadith');
                this.recentMedia = this.recentActivity.filter(a => a.type === 'media_program');
            } catch (error) {
                console.error('Error loading recent content:', error);
            }
        },

        // Load Performance Metrics
        async loadPerformanceMetrics() {
            // Simulate performance metrics (in production, fetch from monitoring service)
            this.performance = {
                apiResponseTime: Math.floor(Math.random() * 200) + 50, // 50-250ms
                databaseSize: Math.floor(Math.random() * 500) + 250, // 250-750MB
                cacheHitRate: Math.floor(Math.random() * 20) + 75, // 75-95%
                activeSessions: Math.floor(Math.random() * 80) + 20 // 20-100
            };
        },

        // Animate Total Content Counter
        animateTotalContent() {
            const duration = 2000; // 2 seconds
            const steps = 60;
            const increment = this.totalContent / steps;
            let current = 0;

            const interval = setInterval(() => {
                current += increment;
                if (current >= this.totalContent) {
                    this.animatedTotalContent = this.totalContent;
                    clearInterval(interval);
                } else {
                    this.animatedTotalContent = Math.floor(current);
                }
            }, duration / steps);
        },

        // Initialize Charts
        initCharts() {
            this.initContentGrowthChart();
            this.initModuleDistributionChart();
        },

        // Content Growth Chart
        initContentGrowthChart() {
            const ctx = document.getElementById('contentGrowthChart');
            if (!ctx) return;

            const textColor = this.darkMode ? '#fff' : '#1f2937';
            const gridColor = this.darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const tickColor = this.darkMode ? '#9CA3AF' : '#6B7280';

            // Generate last 30 days data
            const labels = [];
            const articlesData = [];
            const hadithData = [];
            const mediaData = [];

            for (let i = 29; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('ar-EG', { month: 'short', day: 'numeric' }));

                // Simulate growth data
                articlesData.push(Math.floor(Math.random() * 15) + 5);
                hadithData.push(Math.floor(Math.random() * 20) + 8);
                mediaData.push(Math.floor(Math.random() * 8) + 3);
            }

            if (this.contentGrowthChart) {
                this.contentGrowthChart.destroy();
            }

            this.contentGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'المقالات',
                            data: articlesData,
                            borderColor: '#0056b3',
                            backgroundColor: 'rgba(0, 86, 179, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#0056b3',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        },
                        {
                            label: 'الأحاديث',
                            data: hadithData,
                            borderColor: '#218838',
                            backgroundColor: 'rgba(33, 136, 56, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#218838',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        },
                        {
                            label: 'البرامج',
                            data: mediaData,
                            borderColor: '#64CCC5',
                            backgroundColor: 'rgba(100, 204, 197, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#64CCC5',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'start',
                            labels: {
                                color: textColor,
                                font: { family: 'Cairo', size: 12, weight: 'bold' },
                                usePointStyle: true,
                                padding: 15,
                                boxWidth: 8,
                                boxHeight: 8
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
                            bodyFont: { family: 'Cairo', size: 13 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' إضافة';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                font: { family: 'Cairo', weight: 'bold' }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                font: { family: 'Cairo', weight: 'bold' },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        // Module Distribution Chart
        initModuleDistributionChart() {
            const ctx = document.getElementById('moduleDistributionChart');
            if (!ctx) return;

            const textColor = this.darkMode ? '#fff' : '#1f2937';

            if (this.moduleDistributionChart) {
                this.moduleDistributionChart.destroy();
            }

            this.moduleDistributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['المقالات', 'الأحاديث', 'البرامج', 'السيرة', 'الصحابة', 'القرآن'],
                    datasets: [{
                        data: [
                            this.stats.articles.total || 35,
                            this.stats.hadith.total || 25,
                            this.stats.media.total || 15,
                            124,
                            85,
                            6236
                        ],
                        backgroundColor: [
                            '#0056b3',
                            '#218838',
                            '#64CCC5',
                            '#8B5CF6',
                            '#F59E0B',
                            '#EF4444'
                        ],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                font: { family: 'Cairo', size: 12, weight: 'bold' },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
                            bodyFont: { family: 'Cairo', size: 13 },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        // Initialize Sparkline Charts
        initSparklines() {
            const sparklineIds = ['articles', 'hadith', 'media', 'users'];

            sparklineIds.forEach(id => {
                const ctx = document.getElementById(`sparkline-${id}`);
                if (!ctx) return;

                // Generate 7 days of data
                const data = Array.from({ length: 7 }, () => Math.floor(Math.random() * 30) + 10);

                this.sparklineCharts[id] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['', '', '', '', '', '', ''],
                        datasets: [{
                            data: data,
                            borderColor: id === 'articles' ? '#0056b3' : id === 'hadith' ? '#218838' : id === 'media' ? '#8B5CF6' : '#64CCC5',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        },
                        animation: {
                            duration: 1000
                        }
                    }
                });
            });
        },

        // Start Activity Polling
        startActivityPolling() {
            setInterval(() => {
                this.loadActivity();
            }, 30000); // Poll every 30 seconds
        },

        // Start Time Updates
        startTimeUpdates() {
            setInterval(() => {
                this.$nextTick(() => lucide.createIcons());
            }, 60000); // Update time every minute
        },

        // Generate Calendar
        generateCalendar() {
            const today = new Date();
            const year = today.getFullYear();
            const month = today.getMonth();

            // Set current month name
            this.calendarCurrentMonth = today.toLocaleDateString('ar-EG', { year: 'numeric', month: 'long' });

            // Get first day of month
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            // Get day of week for first day (Sunday = 0)
            const startDayOfWeek = firstDay.getDay();

            // Calculate days from previous month to show
            const daysFromPrevMonth = startDayOfWeek;
            const prevMonthLastDay = new Date(year, month, 0).getDate();

            // Generate calendar days
            this.calendarDays = [];

            // Previous month days
            for (let i = daysFromPrevMonth - 1; i >= 0; i--) {
                this.calendarDays.push({
                    day: prevMonthLastDay - i,
                    date: new Date(year, month - 1, prevMonthLastDay - i),
                    isToday: false,
                    hasContent: false,
                    contentCount: 0,
                    isOtherMonth: true
                });
            }

            // Current month days
            const daysInMonth = lastDay.getDate();
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const isToday = date.toDateString() === today.toDateString();
                const hasContent = Math.random() > 0.6; // Random content for demo
                const contentCount = hasContent ? Math.floor(Math.random() * 5) + 1 : 0;

                this.calendarDays.push({
                    day: day,
                    date: date,
                    isToday: isToday,
                    hasContent: hasContent,
                    contentCount: contentCount,
                    isOtherMonth: false
                });
            }

            // Next month days to complete the grid
            const totalCells = Math.ceil(this.calendarDays.length / 7) * 7;
            const daysFromNextMonth = totalCells - this.calendarDays.length;

            for (let day = 1; day <= daysFromNextMonth; day++) {
                this.calendarDays.push({
                    day: day,
                    date: new Date(year, month + 1, day),
                    isToday: false,
                    hasContent: false,
                    contentCount: 0,
                    isOtherMonth: true
                });
            }
        },

        // Select Calendar Day
        selectCalendarDay(day) {
            if (!day.hasContent) return;

            const dateStr = day.date.toLocaleDateString('ar-EG');
            alert(`عرض المحتوى المنشور في ${dateStr}\n\n${day.contentCount} منشور(ات)`);

            // In production, navigate to filtered content view
            // window.location.href = `/admin/content?date=${day.date.toISOString().split('T')[0]}`;
        },

        // Utility Functions
        getCurrentDate() {
            const now = new Date();
            // Hijri date would require a library like moment-hijri
            const gregorian = now.toLocaleDateString('ar-EG', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            return gregorian;
        },

        getCurrentTime() {
            const now = new Date();
            return now.toLocaleTimeString('ar-EG', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatTimeAgo(timestamp) {
            if (!timestamp) return 'منذ لحظات';

            const now = new Date();
            const past = new Date(timestamp);
            const diffInSeconds = Math.floor((now - past) / 1000);

            if (diffInSeconds < 60) return 'منذ لحظات';
            if (diffInSeconds < 3600) return `منذ ${Math.floor(diffInSeconds / 60)} دقيقة`;
            if (diffInSeconds < 86400) return `منذ ${Math.floor(diffInSeconds / 3600)} ساعة`;
            if (diffInSeconds < 604800) return `منذ ${Math.floor(diffInSeconds / 86400)} يوم`;
            return past.toLocaleDateString('ar-EG', { month: 'short', day: 'numeric' });
        },

        formatNumber(num) {
            if (!num) return '0';
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'م';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'ك';
            return num.toString();
        },

        getActivityColor(type) {
            const colors = {
                article: 'bg-gradient-to-br from-blue-500 to-blue-600',
                hadith: 'bg-gradient-to-br from-emerald-500 to-emerald-600',
                media_program: 'bg-gradient-to-br from-purple-500 to-purple-600',
                default: 'bg-gradient-to-br from-gray-500 to-gray-600'
            };
            return colors[type] || colors.default;
        },

        getActivityIcon(type) {
            const icons = {
                article: 'file-text',
                hadith: 'scroll-text',
                media_program: 'video',
                default: 'activity'
            };
            return icons[type] || icons.default;
        },

        navigateToModule(route) {
            if (route && route !== '#') {
                window.location.href = route;
            }
        },

        navigateToTopArticle() {
            // In production, navigate to the top article
            console.log('Navigate to top article');
        }
    };
}
</script>
@endpush
