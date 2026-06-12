@extends('layouts.public')

@php
    $localeNames = [
        'ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو',
    ];
    $translationCodes = array_keys($hadith['translations']);
    $defaultLang = in_array('ar', $translationCodes, true) ? 'ar' : ($translationCodes[0] ?? 'ar');

    // Build JS-safe translations object from real controller data.
    $jsTranslations = [];
    foreach ($hadith['translations'] as $code => $entry) {
        $jsTranslations[$code] = [
            'text' => $entry['text'] ?? ($entry['translation'] ?? ''),
            'title' => $localeNames[$code] ?? strtoupper($code),
        ];
    }

    $metaTitle = trim(($hadith['collectionName'] ?? '') . ' - ' . 'حديث رقم ' . $hadith['number']);
@endphp

@section('title', $metaTitle)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($hadith['arabicText'] ?? $metaTitle), 155))

@push('head')
<style>
  [x-cloak] { display: none !important; }

  /* Custom Scrollbar */
  .custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #0056b3, #218838);
    border-radius: 10px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #003d82, #196429);
  }

  /* Glassmorphism */
  .glass {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .fade-in-up {
    animation: fadeInUp 0.6s ease-out;
  }

  .stagger-1 { animation-delay: 0.1s; opacity: 0; animation-fill-mode: forwards; }
  .stagger-2 { animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards; }
  .stagger-3 { animation-delay: 0.3s; opacity: 0; animation-fill-mode: forwards; }
  .stagger-4 { animation-delay: 0.4s; opacity: 0; animation-fill-mode: forwards; }

  /* Arabic Text with proper font */
  .arabic-text {
    font-family: 'Amiri', serif;
    line-height: 2.5;
    font-size: 1.5rem;
  }

  /* Narrator Chain Timeline */
  .narrator-line {
    position: absolute;
    top: 2.5rem;
    bottom: 2.5rem;
    right: 1.25rem;
    width: 2px;
    background: linear-gradient(to bottom, #0056b3, #218838);
  }

  /* Authenticity Badge Pulse */
  @keyframes pulse-glow {
    0%, 100% {
      box-shadow: 0 0 20px rgba(0, 86, 179, 0.3);
    }
    50% {
      box-shadow: 0 0 30px rgba(0, 86, 179, 0.5);
    }
  }

  .pulse-glow {
    animation: pulse-glow 2s infinite;
  }

  /* Audio Player */
  .audio-progress {
    background: linear-gradient(to right, #0056b3, #218838);
  }

  /* Share Options Hover */
  .share-btn:hover {
    transform: scale(1.1) translateY(-2px);
    transition: all 0.3s ease;
  }

  /* Print Styles */
  @media print {
    header, footer, aside, .no-print {
      display: none !important;
    }
  }
</style>
@endpush

@section('content')

<div
  x-data="{
    darkMode: localStorage.getItem('darkMode') !== 'false',
    activeLanguage: '{{ $defaultLang }}',
    showNarratorDetails: false,
    selectedNarrator: null,
    audioPlaying: false,
    audioProgress: 0,
    bookmarked: false,
    showShareModal: false,
    fontSize: 100,

    // Multi-language translations (real data)
    translations: @js($jsTranslations),

    // Narrators data (real data — empty when isnad not available)
    narrators: @js($hadith['narrators'] ?? []),

    // Initialize
    init() {
      this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
    },

    // Dark Mode Toggle
    toggleDarkMode() {
      this.darkMode = !this.darkMode;
      localStorage.setItem('darkMode', this.darkMode);
      this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
    },

    // Font Size Controls
    increaseFontSize() {
      if (this.fontSize < 150) {
        this.fontSize += 10;
      }
    },

    decreaseFontSize() {
      if (this.fontSize > 80) {
        this.fontSize -= 10;
      }
    },

    // Language Toggle
    setLanguage(lang) {
      this.activeLanguage = lang;
    },

    // Audio Controls
    toggleAudio() {
      this.audioPlaying = !this.audioPlaying;
      if (this.audioPlaying) {
        this.simulateAudioProgress();
      }
    },

    simulateAudioProgress() {
      if (!this.audioPlaying) return;
      const interval = setInterval(() => {
        if (this.audioProgress >= 100 || !this.audioPlaying) {
          clearInterval(interval);
          this.audioPlaying = false;
          this.audioProgress = 0;
        } else {
          this.audioProgress += 1;
        }
      }, 100);
    },

    // Bookmark
    toggleBookmark() {
      this.bookmarked = !this.bookmarked;
    },

    // Share Functions
    shareOnTwitter() {
      const text = (this.translations['{{ $defaultLang }}']?.text || '').substring(0, 100) + '...';
      window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text + ' - خاتَمُ المُرْسَلِين'), '_blank');
    },
    shareOnFacebook() {
      window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank');
    },
    shareOnWhatsApp() {
      const text = this.translations['{{ $defaultLang }}']?.text || '';
      window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
    },
    copyHadithText() {
      navigator.clipboard.writeText(this.translations[this.activeLanguage]?.text || '');
      alert('تم نسخ نص الحديث!');
    },

    // Narrator Details
    showNarratorInfo(narrator) {
      this.selectedNarrator = narrator;
      this.showNarratorDetails = true;
    }
  }"
  x-init="init()"
>

  <!-- Main Content -->
  <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
      <ol class="inline-flex items-center space-s-2">
        <li><a href="{{ url('/') }}" class="hover:text-[#64CCC5]">الرئيسية</a></li>
        <li><i data-lucide="chevron-left" class="w-4 h-4"></i></li>
        <li><a href="{{ route('public.hadith') }}" class="hover:text-[#64CCC5]">الحديث</a></li>
        <li><i data-lucide="chevron-left" class="w-4 h-4"></i></li>
        <li><a href="{{ route('public.hadith') }}" class="hover:text-[#64CCC5]">{{ $hadith['collectionName'] }}</a></li>
        <li><i data-lucide="chevron-left" class="w-4 h-4"></i></li>
        <li :class="darkMode ? 'text-gray-300' : 'text-gray-900'">حديث رقم {{ $hadith['number'] }}</li>
      </ol>
    </nav>

    <div class="lg:grid lg:grid-cols-12 lg:gap-8">

      <!-- Main Content -->
      <div class="lg:col-span-8 xl:col-span-9">

        <!-- Hadith Card -->
        <article class="rounded-2xl overflow-hidden shadow-2xl fade-in-up mb-8" :class="darkMode ? 'bg-gray-900' : 'bg-white'">

          <!-- Header -->
          <div class="p-6 md:p-10 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">

            <!-- Authenticity Badge -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
              <span class="px-6 py-3 rounded-full text-sm font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg pulse-glow flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                {{ $hadith['grading']['label'] }}
              </span>
              <span class="px-4 py-2 rounded-full text-sm font-medium glass text-white flex items-center gap-2">
                <i data-lucide="book" class="w-4 h-4"></i>
                {{ $hadith['collectionName'] }}
              </span>
              <span class="px-4 py-2 rounded-full text-sm font-medium" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-700'">
                حديث رقم {{ $hadith['number'] }}
              </span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
              {{ $hadith['bookTitle'] ?: $hadith['collectionName'] }}
            </h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
              @if ($hadith['bookTitle'])
                <div class="flex items-center gap-2">
                  <i data-lucide="book-open" class="w-4 h-4 text-[#64CCC5]"></i>
                  <span>{{ $hadith['bookTitle'] }}</span>
                </div>
              @endif
              <div class="flex items-center gap-2">
                <i data-lucide="book-marked" class="w-4 h-4 text-[#64CCC5]"></i>
                <span>{{ $hadith['collectionName'] }}</span>
              </div>
              @if ($hadith['gradingAgreement'])
                <div class="flex items-center gap-2">
                  <i data-lucide="check-check" class="w-4 h-4 text-[#64CCC5]"></i>
                  <span>متفق عليه</span>
                </div>
              @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 mt-6 no-print">
              <!-- Bookmark -->
              <button
                @click="toggleBookmark"
                class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all share-btn"
                :class="bookmarked ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')"
              >
                <i data-lucide="bookmark" class="w-5 h-5" :class="bookmarked ? 'fill-current' : ''"></i>
                <span>حفظ</span>
              </button>

              <!-- Share -->
              <button
                @click="showShareModal = true"
                class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all share-btn"
                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              >
                <i data-lucide="share-2" class="w-5 h-5"></i>
                <span>مشاركة</span>
              </button>

              <!-- Copy Text -->
              <button
                @click="copyHadithText"
                class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all share-btn"
                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              >
                <i data-lucide="copy" class="w-5 h-5"></i>
                <span>نسخ النص</span>
              </button>

              <!-- Print -->
              <button
                onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all share-btn"
                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              >
                <i data-lucide="printer" class="w-5 h-5"></i>
                <span>طباعة</span>
              </button>

              @if (($hadith['parallelsCount'] ?? 0) > 0)
              <!-- Compare parallel narrations -->
              <a
                href="{{ route('public.hadith.narrations', $hadith['id']) }}"
                class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all share-btn bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md"
              >
                <i data-lucide="git-compare" class="w-5 h-5"></i>
                <span>{{ __('hadith.public.narrations_compare_link', ['count' => $hadith['parallelsCount']]) }}</span>
              </a>
              @endif
            </div>
          </div>

          <!-- Hadith Text -->
          <div class="p-6 md:p-10">

            <!-- Language Tabs -->
            <div class="flex flex-wrap gap-2 mb-6 no-print">
              @foreach ($translationCodes as $code)
                <button @click="setLanguage('{{ $code }}')" class="px-4 py-2 rounded-full text-sm font-semibold transition-all" :class="activeLanguage === '{{ $code }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')">
                  {{ $localeNames[$code] ?? strtoupper($code) }}
                </button>
              @endforeach
            </div>

            <!-- Text Display -->
            <div class="p-6 md:p-8 rounded-2xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gradient-to-r from-blue-50 to-green-50'" :style="`font-size: ${fontSize}%`">

              <!-- Main Hadith Text -->
              <blockquote class="arabic-text text-center my-8" :class="darkMode ? 'text-white' : 'text-gray-900'">
                "<span x-text="translations[activeLanguage]?.text"></span>"
              </blockquote>

              <!-- Source Info -->
              <div class="mt-6 pt-6 border-t" :class="darkMode ? 'border-gray-700 text-gray-400' : 'border-gray-200 text-gray-600'">
                <p class="text-sm">
                  <strong :class="darkMode ? 'text-gray-300' : 'text-gray-800'">التخريج:</strong>
                  {{ $hadith['collectionName'] }} ({{ $hadith['number'] }})
                </p>
              </div>
            </div>

            <!-- Audio Player -->
            <div class="mt-6 p-6 rounded-2xl glass" :class="darkMode ? 'bg-gray-800/30' : 'bg-white/50'">
              <div class="flex items-center gap-4">
                <!-- Play/Pause Button -->
                <button
                  @click="toggleAudio"
                  class="w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-lg"
                >
                  <i :data-lucide="audioPlaying ? 'pause' : 'play'" class="w-6 h-6"></i>
                </button>

                <!-- Progress Bar -->
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span>الاستماع للحديث</span>
                    <span x-text="Math.floor(audioProgress / 100 * 45) + ':' + String(Math.floor((audioProgress / 100 * 45 % 1) * 60)).padStart(2, '0')"></span>
                  </div>
                  <div class="h-2 rounded-full" :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'">
                    <div class="h-full rounded-full audio-progress transition-all duration-300" :style="`width: ${audioProgress}%`"></div>
                  </div>
                </div>

                <!-- Volume -->
                <button class="p-3 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  <i data-lucide="volume-2" class="w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </button>
              </div>
            </div>
          </div>
        </article>

        @if (! empty($hadith['significance']) || ! empty($hadith['historicalContext']))
        <!-- Commentary Sections -->
        <div class="space-y-6 stagger-2 fade-in-up" x-data="{ showCommentary: { significance: true, context: false } }">

          @if (! empty($hadith['significance']))
          <!-- Significance Commentary -->
          <div class="rounded-2xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <button
              @click="showCommentary.significance = !showCommentary.significance"
              class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              <div class="flex items-center gap-3">
                <i data-lucide="book-open" class="w-6 h-6 text-[#64CCC5]"></i>
                <div class="text-right">
                  <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">الأهمية والفوائد</h2>
                  <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">شرح الحديث</p>
                </div>
              </div>
              <i :data-lucide="showCommentary.significance ? 'chevron-up' : 'chevron-down'" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
            </button>

            <div x-show="showCommentary.significance" x-collapse>
              <div class="px-6 pb-6 space-y-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <p>{{ $hadith['significance'] }}</p>
              </div>
            </div>
          </div>
          @endif

          @if (! empty($hadith['historicalContext']))
          <!-- Historical Context Commentary -->
          <div class="rounded-2xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <button
              @click="showCommentary.context = !showCommentary.context"
              class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              <div class="flex items-center gap-3">
                <i data-lucide="scroll-text" class="w-6 h-6 text-[#64CCC5]"></i>
                <div class="text-right">
                  <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">السياق التاريخي</h2>
                  <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">خلفية الحديث</p>
                </div>
              </div>
              <i :data-lucide="showCommentary.context ? 'chevron-up' : 'chevron-down'" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
            </button>

            <div x-show="showCommentary.context" x-collapse>
              <div class="px-6 pb-6 space-y-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <p>{{ $hadith['historicalContext'] }}</p>
              </div>
            </div>
          </div>
          @endif
        </div>
        @endif

        @if (count($hadith['commentaryGroups'] ?? []) > 0)
        <!-- Scholarly Commentaries (per-author accordions) -->
        <div class="mt-8 space-y-4 stagger-2 fade-in-up">
          <h2 class="text-2xl font-bold flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="library" class="w-6 h-6 text-[#64CCC5]"></i>
            {{ __('hadith.public.single_commentaries_heading') }}
          </h2>

          @foreach ($hadith['commentaryGroups'] as $group)
          <div class="rounded-2xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'"
               x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
            <button
              @click="open = !open"
              class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
              :aria-expanded="open"
            >
              <div class="flex items-center gap-3 min-w-0">
                <i data-lucide="book-open-text" class="w-6 h-6 text-[#64CCC5] flex-shrink-0"></i>
                <div class="text-start min-w-0">
                  <h3 class="text-lg font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $group['author'] }}
                  </h3>
                  <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('hadith.public.single_commentaries_subtitle') }}
                  </p>
                </div>
              </div>
              <i :data-lucide="open ? 'chevron-up' : 'chevron-down'" class="w-6 h-6 flex-shrink-0" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
            </button>

            <div x-show="open" x-collapse>
              <div class="px-6 pb-6 space-y-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                @foreach ($group['items'] as $commentary)
                  <div class="pt-4 first:pt-0 border-t first:border-t-0" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                    <p class="leading-relaxed break-words" @if (in_array($commentary['languageCode'] ?? '', ['ar', 'ar_notashkeel', 'ur'], true)) dir="rtl" @endif>
                      {{ $commentary['text'] }}
                    </p>
                    @if (! empty($commentary['verified']))
                      <span class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-[#218838]">
                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                        {{ __('hadith.public.single_verified') }}
                      </span>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        @if (count($related) > 0)
        <!-- Related Hadiths -->
        <div class="mt-12 stagger-3 fade-in-up">
          <h2 class="text-2xl font-bold mb-6 flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="link" class="w-6 h-6 text-[#64CCC5]"></i>
            أحاديث ذات صلة
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($related as $item)
            <!-- Related Hadith -->
            <a href="{{ route('public.hadith.show', $item['id']) }}" class="group p-6 rounded-2xl transition-all hover:shadow-2xl hover:-translate-y-2" :class="darkMode ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-50'">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white flex-shrink-0 group-hover:scale-110 transition-transform">
                  <i data-lucide="book" class="w-6 h-6"></i>
                </div>
                <div class="flex-1">
                  <h3 class="font-bold text-lg mb-2 group-hover:text-[#64CCC5] transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    حديث رقم {{ $item['number'] }}
                  </h3>
                  @if ($item['text'])
                  <p class="text-sm mb-3 line-clamp-2 font-['Amiri']" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" dir="rtl">
                    {{ \Illuminate\Support\Str::limit($item['text'], 110) }}
                  </p>
                  @endif
                  <div class="flex items-center gap-2 text-sm text-[#64CCC5]">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>{{ $item['collectionName'] }} - {{ $item['grading']['label'] }}</span>
                  </div>
                </div>
              </div>
            </a>
            @endforeach
          </div>
        </div>
        @endif

      </div>

      <!-- Sidebar -->
      <aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0">
        <div class="sticky top-24 space-y-6">

          <template x-if="narrators.length > 0">
          <!-- Narrator Chain -->
          <div class="rounded-2xl p-6 shadow-xl fade-in-up" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <h3 class="text-lg font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <i data-lucide="users" class="w-5 h-5 text-[#64CCC5]"></i>
              {{ __('hadith.public.single_isnad_heading') }}
            </h3>

            <div class="relative">
              <!-- Timeline Line -->
              <div class="narrator-line"></div>

              <!-- Narrators List -->
              <div class="space-y-6">
                <template x-for="(narrator, index) in narrators" :key="narrator.id">
                  <button
                    @click="showNarratorInfo(narrator)"
                    class="relative w-full flex items-center gap-4 text-right hover:bg-gray-50 dark:hover:bg-gray-800 p-2 rounded-lg transition-all group"
                  >
                    <!-- Icon -->
                    <div
                      class="w-12 h-12 rounded-full flex items-center justify-center text-white flex-shrink-0 ring-4 transition-transform group-hover:scale-110"
                      :class="narrator.type === 'companion' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] ring-white' :
                              narrator.type === 'compiler' ? 'bg-gradient-to-r from-[#218838] to-[#0056b3] ring-white' :
                              'bg-white border-2 border-gray-300 ring-white'"
                    >
                      <i :data-lucide="narrator.type === 'companion' ? 'star' : narrator.type === 'compiler' ? 'shield-check' : 'user'" class="w-6 h-6" :class="narrator.type === 'companion' || narrator.type === 'compiler' ? 'text-white' : 'text-gray-600'"></i>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                      <p class="font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="narrator.name"></p>
                      <div class="flex flex-wrap items-center gap-2 mt-1">
                        <template x-if="narrator.reliability">
                          <span class="text-xs px-2 py-1 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'" x-text="narrator.reliability"></span>
                        </template>
                        <template x-if="narrator.deathYearHijri">
                          <span class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            <span x-text="narrator.deathYearHijri"></span> {{ __('hadith.public.single_hijri_suffix') }}
                          </span>
                        </template>
                      </div>
                    </div>

                    <!-- Chevron -->
                    <i data-lucide="chevron-left" class="w-5 h-5 opacity-0 group-hover:opacity-100 transition-opacity" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                  </button>
                </template>
              </div>
            </div>
          </div>
          </template>

          <!-- Quick Stats -->
          <div class="rounded-2xl p-6 shadow-xl fade-in-up stagger-1" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <i data-lucide="info" class="w-5 h-5 text-[#64CCC5]"></i>
              معلومات الحديث
            </h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الكتاب</span>
                <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith['collectionName'] }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم الحديث</span>
                <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith['number'] }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الدرجة</span>
                <span class="font-semibold text-[#64CCC5]">{{ $hadith['grading']['label'] }}</span>
              </div>
              @if ($hadith['bookTitle'])
              <div class="flex items-center justify-between">
                <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الباب</span>
                <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith['bookTitle'] }}</span>
              </div>
              @endif
              @if (($hadith['parallelsCount'] ?? 0) > 0)
              <a href="{{ route('public.hadith.narrations', $hadith['id']) }}"
                 class="flex items-center justify-center gap-2 mt-2 px-4 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg transition-all">
                <i data-lucide="git-compare" class="w-4 h-4"></i>
                {{ __('hadith.public.narrations_compare_link', ['count' => $hadith['parallelsCount']]) }}
              </a>
              @endif
            </div>
          </div>

          @if (count($hadith['scholarGradings'] ?? []) > 0)
          <!-- Scholar Gradings -->
          <div class="rounded-2xl p-6 shadow-xl fade-in-up stagger-1" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <i data-lucide="scale" class="w-5 h-5 text-[#64CCC5]"></i>
              {{ __('hadith.public.single_gradings_heading') }}
            </h3>
            <ul class="space-y-3">
              @foreach (array_slice($hadith['scholarGradings'], 0, 6) as $grading)
              <li class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-sm truncate max-w-[55%]" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                  {{ $grading['scholar'] ?: __('hadith.public.single_commentary_unknown_author') }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#64CCC5]/15 text-[#0d7d75] dark:text-[#64CCC5]">
                  {{ $grading['grade'] }}
                </span>
              </li>
              @endforeach
            </ul>
          </div>
          @endif

          <!-- Share Widget -->
          <div class="rounded-2xl p-6 shadow-xl fade-in-up stagger-2" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <i data-lucide="share-2" class="w-5 h-5 text-[#64CCC5]"></i>
              مشاركة الحديث
            </h3>
            <div class="grid grid-cols-2 gap-3">
              <button @click="shareOnTwitter" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#1DA1F2] text-white hover:shadow-lg transition-all hover:scale-105">
                <i data-lucide="twitter" class="w-5 h-5"></i>
                <span class="text-sm font-medium">تويتر</span>
              </button>
              <button @click="shareOnFacebook" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#1877F2] text-white hover:shadow-lg transition-all hover:scale-105">
                <i data-lucide="facebook" class="w-5 h-5"></i>
                <span class="text-sm font-medium">فيسبوك</span>
              </button>
              <button @click="shareOnWhatsApp" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#25D366] text-white hover:shadow-lg transition-all hover:scale-105">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span class="text-sm font-medium">واتساب</span>
              </button>
              <button @click="copyHadithText" class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all hover:scale-105" :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                <i data-lucide="copy" class="w-5 h-5"></i>
                <span class="text-sm font-medium">نسخ</span>
              </button>
            </div>
          </div>

        </div>
      </aside>

    </div>
  </main>

  <!-- Share Modal -->
  <div
    x-show="showShareModal"
    x-cloak
    @click.self="showShareModal = false"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
    style="display: none;"
  >
    <div
      class="rounded-2xl p-8 max-w-md w-full shadow-2xl transform transition-all"
      :class="darkMode ? 'bg-gray-900' : 'bg-white'"
      @click.stop
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">مشاركة الحديث</h3>
        <button @click="showShareModal = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
          <i data-lucide="x" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
        </button>
      </div>

      <div class="grid grid-cols-2 gap-4 mb-6">
        <button @click="shareOnTwitter" class="flex flex-col items-center gap-3 p-6 rounded-xl bg-[#1DA1F2] text-white hover:shadow-xl transition-all hover:scale-105">
          <i data-lucide="twitter" class="w-8 h-8"></i>
          <span class="font-medium">تويتر</span>
        </button>
        <button @click="shareOnFacebook" class="flex flex-col items-center gap-3 p-6 rounded-xl bg-[#1877F2] text-white hover:shadow-xl transition-all hover:scale-105">
          <i data-lucide="facebook" class="w-8 h-8"></i>
          <span class="font-medium">فيسبوك</span>
        </button>
        <button @click="shareOnWhatsApp" class="flex flex-col items-center gap-3 p-6 rounded-xl bg-[#25D366] text-white hover:shadow-xl transition-all hover:scale-105">
          <i data-lucide="message-circle" class="w-8 h-8"></i>
          <span class="font-medium">واتساب</span>
        </button>
        <button @click="copyHadithText; showShareModal = false" class="flex flex-col items-center gap-3 p-6 rounded-xl transition-all hover:scale-105" :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
          <i data-lucide="copy" class="w-8 h-8"></i>
          <span class="font-medium">نسخ النص</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Narrator Details Modal -->
  <div
    x-show="showNarratorDetails"
    x-cloak
    @click.self="showNarratorDetails = false"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
    style="display: none;"
  >
    <div
      class="rounded-2xl p-8 max-w-lg w-full shadow-2xl transform transition-all"
      :class="darkMode ? 'bg-gray-900' : 'bg-white'"
      @click.stop
    >
      <template x-if="selectedNarrator">
        <div>
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="selectedNarrator.name"></h3>
            <button @click="showNarratorDetails = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
              <i data-lucide="x" class="w-6 h-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
            </button>
          </div>

          <div class="space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
            <template x-if="selectedNarrator.kunya">
              <div>
                <p class="text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.public.single_narrator_kunya') }}</p>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="selectedNarrator.kunya"></p>
              </div>
            </template>

            <template x-if="selectedNarrator.reliability">
              <div>
                <p class="text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.public.single_narrator_reliability') }}</p>
                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white" x-text="selectedNarrator.reliability"></span>
              </div>
            </template>

            <template x-if="selectedNarrator.deathYearHijri">
              <div>
                <p class="text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.public.single_narrator_death') }}</p>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                  <span x-text="selectedNarrator.deathYearHijri"></span> {{ __('hadith.public.single_hijri_suffix') }}
                </p>
              </div>
            </template>

            <template x-if="selectedNarrator.bio">
              <div>
                <p class="text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.public.single_narrator_bio') }}</p>
                <p class="break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="selectedNarrator.bio"></p>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>

</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
  });
</script>
@endpush

@endsection
