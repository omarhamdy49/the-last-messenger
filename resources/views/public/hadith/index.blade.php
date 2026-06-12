@extends('layouts.public')

@section('title', __('hadith.public.page_title'))
@section('meta_description', __('hadith.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('hadith.public.page_title') }}",
    "description": "{{ __('hadith.public.page_description') }}",
    "url": "{{ route('public.hadith') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<style>
    .font-amiri {
      font-family: 'Amiri', serif;
    }

    .font-tajawal {
      font-family: 'Tajawal', sans-serif;
    }

    /* Islamic Pattern Background */
    .islamic-pattern {
      background-image:
        radial-gradient(circle at 25% 25%, rgba(0, 86, 179, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(33, 136, 56, 0.03) 0%, transparent 50%);
      background-size: 100px 100px;
    }

    .pattern-overlay {
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    /* Premium Book Card - 3D Perspective */
    .book-3d {
      transform-style: preserve-3d;
      perspective: 2000px;
      position: relative;
    }

    .book-container {
      position: relative;
      transform-style: preserve-3d;
      transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .book-container:hover {
      transform: translateY(-30px) rotateY(-8deg) rotateX(5deg) scale(1.08);
    }

    /* Book Cover with Pages Effect */
    .book-cover-3d {
      position: relative;
      background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
      border-radius: 0 8px 8px 0;
      box-shadow:
        -10px 0 20px rgba(0, 0, 0, 0.3),
        -15px 0 30px rgba(0, 0, 0, 0.2),
        inset -5px 0 10px rgba(0, 0, 0, 0.5);
      overflow: hidden;
    }

    /* Pages Effect */
    .book-pages {
      position: absolute;
      right: -8px;
      top: 8px;
      bottom: 8px;
      width: 20px;
      background: linear-gradient(90deg,
        #f5f5f5 0%,
        #fff 20%,
        #f5f5f5 40%,
        #fff 60%,
        #f5f5f5 80%,
        #fff 100%
      );
      box-shadow:
        2px 0 4px rgba(0, 0, 0, 0.1),
        4px 0 8px rgba(0, 0, 0, 0.05);
      border-radius: 0 4px 4px 0;
    }

    .book-pages::before {
      content: '';
      position: absolute;
      inset: 0;
      background: repeating-linear-gradient(
        to bottom,
        transparent,
        transparent 2px,
        rgba(0, 0, 0, 0.03) 2px,
        rgba(0, 0, 0, 0.03) 4px
      );
    }

    /* Book Spine */
    .book-spine {
      position: absolute;
      right: 0;
      top: 0;
      bottom: 0;
      width: 50px;
      background: linear-gradient(to left,
        rgba(0, 0, 0, 0.6) 0%,
        rgba(0, 0, 0, 0.3) 50%,
        transparent 100%
      );
      border-radius: 0 8px 8px 0;
    }

    /* Blue-Green Accent */
    .accent-gradient {
      background: linear-gradient(135deg, #0056b3 0%, #218838 50%, #64CCC5 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .accent-border {
      border: 2px solid #0056b3;
      box-shadow:
        0 0 20px rgba(0, 86, 179, 0.3),
        inset 0 0 20px rgba(0, 86, 179, 0.1);
    }

    /* Leather Texture Overlay */
    .leather-texture {
      position: relative;
    }

    .leather-texture::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(0, 0, 0, 0.1) 0%, transparent 50%);
      background-size: 30px 30px;
      pointer-events: none;
      opacity: 0.3;
    }

    /* Shimmer on Hover */
    .shimmer-accent {
      position: absolute;
      top: 0;
      right: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg,
        transparent 0%,
        rgba(100, 204, 197, 0.3) 50%,
        transparent 100%
      );
      transition: right 0.8s ease;
    }

    .book-container:hover .shimmer-accent {
      right: 100%;
    }

    /* Ornamental Border */
    .ornamental-border {
      position: relative;
      padding: 2px;
      background: linear-gradient(135deg, #0056b3, #218838, #64CCC5);
      border-radius: 8px;
    }

    .ornamental-border::before,
    .ornamental-border::after {
      content: '◆';
      position: absolute;
      color: #64CCC5;
      font-size: 12px;
      opacity: 0.6;
    }

    .ornamental-border::before {
      top: 8px;
      right: 8px;
    }

    .ornamental-border::after {
      bottom: 8px;
      left: 8px;
    }

    /* Featured Book Spotlight */
    .spotlight {
      position: relative;
      background: radial-gradient(ellipse at center, rgba(100, 204, 197, 0.1) 0%, transparent 70%);
    }

    /* Carousel */
    .carousel-container {
      position: relative;
      overflow: hidden;
    }

    .carousel-track {
      display: flex;
      transition: transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    /* Animations */
    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(2deg); }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(60px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes glow {
      0%, 100% {
        box-shadow: 0 0 30px rgba(100, 204, 197, 0.3),
                    0 0 60px rgba(100, 204, 197, 0.1);
      }
      50% {
        box-shadow: 0 0 40px rgba(100, 204, 197, 0.5),
                    0 0 80px rgba(100, 204, 197, 0.2);
      }
    }

    .animate-float { animation: float 6s ease-in-out infinite; }
    .animate-fadeInUp { animation: fadeInUp 0.8s ease-out forwards; }
    .animate-scaleIn { animation: scaleIn 0.6s ease-out forwards; }
    .animate-glow { animation: glow 3s ease-in-out infinite; }

    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }
    .stagger-5 { animation-delay: 0.5s; }
    .stagger-6 { animation-delay: 0.6s; }
    .stagger-7 { animation-delay: 0.7s; }
    .stagger-8 { animation-delay: 0.8s; }

    /* Glass Morphism */
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .glass-dark {
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Premium Badge */
    .premium-badge {
      position: absolute;
      top: -10px;
      right: -10px;
      width: 80px;
      height: 80px;
      background: radial-gradient(circle, #0056b3, #218838);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: 900;
      color: white;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow:
        0 5px 15px rgba(0, 86, 179, 0.5),
        inset 0 -2px 10px rgba(0, 0, 0, 0.3);
      z-index: 20;
      transform: rotate(15deg);
      animation: glow 3s ease-in-out infinite;
    }

    /* Book Info Overlay */
    .book-info-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 50%, transparent 100%);
      opacity: 0;
      transition: opacity 0.5s ease;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 2rem;
      border-radius: 0 8px 8px 0;
    }

    .book-container:hover .book-info-overlay {
      opacity: 1;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
      height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: linear-gradient(90deg, #0056b3, #218838, #64CCC5);
      border-radius: 10px;
    }

    /* Section Divider */
    .section-divider {
      position: relative;
      text-align: center;
      margin: 4rem 0;
    }

    .section-divider::before,
    .section-divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 40%;
      height: 1px;
      background: linear-gradient(to right, transparent, #64CCC5, transparent);
    }

    .section-divider::before {
      right: 55%;
    }

    .section-divider::after {
      left: 55%;
    }

    /* Book Category Badge */
    .category-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 1rem;
      background: rgba(100, 204, 197, 0.1);
      border: 1px solid rgba(100, 204, 197, 0.3);
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      color: #64CCC5;
      backdrop-filter: blur(10px);
    }
</style>
@endpush

@section('content')

@php
    $bookBaseUrl = url('/hadith/book');

    // Deterministic cover gradients + accent colours keyed by browsing category so the
    // book covers stay visually distinct (mirrors the prototype's per-book palette).
    $gradientFor = [
        'sahih'   => 'from-emerald-900 via-emerald-800 to-emerald-700',
        'sunan'   => 'from-blue-900 via-blue-800 to-blue-700',
        'masanid' => 'from-cyan-900 via-cyan-800 to-cyan-700',
        'arbaeen' => 'from-amber-900 via-amber-800 to-amber-700',
        'other'   => 'from-indigo-900 via-indigo-800 to-indigo-700',
    ];
    $accentFor = [
        'sahih'   => '#10b981',
        'sunan'   => '#3b82f6',
        'masanid' => '#06b6d4',
        'arbaeen' => '#f59e0b',
        'other'   => '#6366f1',
    ];

    // Map the real collections into the prototype's `book` shape. The first few
    // (ranks 1–2) earn the "أصح" premium badge + carousel feature slot.
    $alpineBooks = [];
    foreach (array_values($books) as $i => $book) {
        $cat = $book['category'] ?? 'other';
        $rank = $i + 1;
        $alpineBooks[] = [
            'id' => $book['id'],
            'slug' => $book['slug'],
            'title' => $book['nameAr'] ?: $book['name'],
            'subtitle' => $book['nameEn'] ?? '',
            'author' => $book['author'] ?? '',
            'authorShort' => $book['author'] ?? '',
            'description' => $book['description'] ?? '',
            'category' => $cat,
            'hadithCount' => (int) ($book['hadithCount'] ?? 0),
            'chapterCount' => (int) ($book['bookCount'] ?? 0),
            'featured' => $i < 4,
            'coverGradient' => $gradientFor[$cat] ?? $gradientFor['other'],
            'accentColor' => $accentFor[$cat] ?? $accentFor['other'],
            'rank' => $rank,
        ];
    }

    // Reshape controller categories ({id,label,icon,count}) to the prototype's {id,name,icon,count}.
    $alpineCategories = array_map(static fn ($c) => [
        'id' => $c['id'],
        'name' => $c['label'],
        'icon' => $c['icon'],
        'count' => $c['count'],
    ], array_values($categories));

    $booksJson = json_encode($alpineBooks, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $categoriesJson = json_encode($alpineCategories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
@endphp

<div x-data="{
  get darkMode() { return $store.theme.dark; },
  searchQuery: '',
  selectedCategory: 'all',
  currentSlide: 0,
  autoPlayInterval: null,

  bookBase: '{{ $bookBaseUrl }}',
  books: {{ $booksJson }},
  categories: {{ $categoriesJson }},

  bookUrl(slug) { return this.bookBase + '/' + encodeURIComponent(slug); },

  get filteredBooks() {
    let filtered = this.books;

    if (this.searchQuery) {
      const query = this.searchQuery.toLowerCase();
      filtered = filtered.filter(book =>
        book.title.toLowerCase().includes(query) ||
        book.author.toLowerCase().includes(query)
      );
    }

    if (this.selectedCategory !== 'all') {
      filtered = filtered.filter(book => book.category === this.selectedCategory);
    }

    return filtered;
  },

  get featuredBooks() {
    return this.books.filter(book => book.featured);
  },

  get totalHadiths() {
    return this.books.reduce((sum, book) => sum + book.hadithCount, 0);
  },

  nextSlide() {
    this.currentSlide = (this.currentSlide + 1) % this.featuredBooks.length;
  },

  prevSlide() {
    this.currentSlide = this.currentSlide === 0 ? this.featuredBooks.length - 1 : this.currentSlide - 1;
  },

  startAutoPlay() {
    if (this.featuredBooks.length <= 1) return;
    this.autoPlayInterval = setInterval(() => this.nextSlide(), 5000);
  },

  stopAutoPlay() {
    if (this.autoPlayInterval) {
      clearInterval(this.autoPlayInterval);
      this.autoPlayInterval = null;
    }
  },

  init() {
    this.startAutoPlay();
    this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
    this.$watch('filteredBooks', () => {
      this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
    });
  }
}" :class="darkMode ? 'text-white' : 'text-gray-900'">

  <!-- Hero Featured Books Carousel -->
  <section class="py-16 overflow-hidden relative spotlight">
    <div class="pattern-overlay absolute inset-0 opacity-30"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

      <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full category-badge mb-6 animate-scaleIn">
          <i data-lucide="sparkles" class="w-4 h-4"></i>
          <span>الكتب المميزة</span>
        </div>

        <h1 class="text-5xl md:text-7xl font-bold mb-6 font-amiri animate-fadeInUp">
          مكتبة الحديث الشريف
        </h1>

        <p class="text-xl md:text-2xl opacity-80 max-w-3xl mx-auto animate-fadeInUp stagger-1">
          رحلة إلى كنوز السنة النبوية المطهرة
        </p>
      </div>

      <!-- Carousel -->
      <div class="carousel-container max-w-6xl mx-auto relative" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">
        <div class="carousel-track"
             :style="`transform: translateX(${currentSlide * 100}%)`">

          <template x-for="(book, index) in featuredBooks" :key="book.id">
            <div class="w-full flex-shrink-0 px-4">
              <div class="grid md:grid-cols-2 gap-12 items-center">

                <!-- Book 3D -->
                <div class="book-3d order-2 md:order-1 animate-scaleIn stagger-2">
                  <div class="book-container relative">

                    <!-- Premium Badge -->
                    <div class="premium-badge" x-show="book.rank <= 2">
                      <span class="transform -rotate-15">أصح</span>
                    </div>

                    <!-- Book Cover -->
                    <div class="book-cover-3d leather-texture aspect-[3/4.5] rounded-r-lg bg-gradient-to-br relative overflow-hidden"
                         :class="book.coverGradient">

                      <!-- Book Pages -->
                      <div class="book-pages"></div>

                      <!-- Spine -->
                      <div class="book-spine"></div>

                      <!-- Shimmer -->
                      <div class="shimmer-accent"></div>

                      <!-- Cover Content -->
                      <div class="relative z-10 h-full flex flex-col justify-between p-8 text-white">

                        <!-- Top Ornament -->
                        <div class="flex justify-center">
                          <div class="w-16 h-16 rounded-full border-2 border-[#64CCC5] flex items-center justify-center backdrop-blur-sm bg-white/5">
                            <i data-lucide="book-open" class="w-8 h-8 text-[#64CCC5]"></i>
                          </div>
                        </div>

                        <!-- Title -->
                        <div class="text-center">
                          <div class="inline-block px-6 py-2 rounded-full accent-border mb-6 text-sm">
                            <span class="accent-gradient font-bold" x-text="'المرتبة ' + book.rank"></span>
                          </div>

                          <h2 class="text-4xl md:text-5xl font-bold mb-4 font-amiri leading-tight" x-text="book.title"></h2>
                          <p class="text-lg opacity-90 font-amiri" x-text="book.subtitle"></p>
                        </div>

                        <!-- Bottom Info -->
                        <div class="text-center">
                          <p class="text-sm mb-4 opacity-80" x-text="book.authorShort"></p>
                          <div class="flex items-center justify-center gap-4 text-sm">
                            <div class="flex items-center gap-1">
                              <i data-lucide="file-text" class="w-4 h-4 text-[#64CCC5]"></i>
                              <span x-text="book.hadithCount.toLocaleString()"></span>
                            </div>
                            <div class="w-px h-4 bg-white/30"></div>
                            <div class="flex items-center gap-1">
                              <i data-lucide="book" class="w-4 h-4 text-[#64CCC5]"></i>
                              <span x-text="book.chapterCount"></span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Hover Overlay -->
                      <div class="book-info-overlay">
                        <p class="text-white text-lg leading-relaxed mb-6 font-amiri" x-text="book.description"></p>
                        <a :href="bookUrl(book.slug)"
                           class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold hover:shadow-2xl transition-all transform hover:scale-105">
                          <span>تصفح الكتاب</span>
                          <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Book Details -->
                <div class="order-1 md:order-2 text-center md:text-right animate-fadeInUp stagger-3">
                  <div class="space-y-6">

                    <div>
                      <h3 class="text-4xl md:text-5xl font-bold mb-3 font-amiri" x-text="book.title"></h3>
                      <p class="text-xl opacity-70 font-amiri" x-text="book.subtitle"></p>
                    </div>

                    <div class="w-20 h-1 bg-gradient-to-r from-[#64CCC5] to-transparent mx-auto md:mr-0 md:ml-auto"></div>

                    <div>
                      <p class="text-lg mb-2 opacity-70">المؤلف</p>
                      <p class="text-2xl font-bold font-amiri" x-text="book.author"></p>
                    </div>

                    <p class="text-lg leading-relaxed opacity-80 font-amiri max-w-lg mx-auto md:mr-0 md:ml-auto" x-text="book.description"></p>

                    <div class="grid grid-cols-3 gap-4 max-w-md mx-auto md:mr-0 md:ml-auto">
                      <div class="p-4 rounded-xl" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                        <div class="text-3xl font-bold mb-1 accent-gradient" x-text="book.hadithCount.toLocaleString()"></div>
                        <div class="text-sm opacity-70">حديث</div>
                      </div>
                      <div class="p-4 rounded-xl" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                        <div class="text-3xl font-bold mb-1 accent-gradient" x-text="book.chapterCount"></div>
                        <div class="text-sm opacity-70">كتاب</div>
                      </div>
                      <div class="p-4 rounded-xl" :class="darkMode ? 'bg-white/5' : 'bg-black/5'">
                        <div class="text-3xl font-bold mb-1 accent-gradient" x-text="book.hadithCount.toLocaleString()"></div>
                        <div class="text-sm opacity-70">رواية</div>
                      </div>
                    </div>

                    <a :href="bookUrl(book.slug)"
                       class="inline-flex items-center gap-3 px-10 py-5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-lg hover:shadow-2xl transition-all transform hover:scale-105 animate-glow">
                      <span>استكشف الكتاب</span>
                      <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </a>
                  </div>
                </div>

              </div>
            </div>
          </template>
        </div>

        <!-- Navigation Arrows -->
        <button @click="prevSlide"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center hover:shadow-2xl transition-all transform hover:scale-110 z-20">
          <i data-lucide="chevron-right" class="w-6 h-6"></i>
        </button>

        <button @click="nextSlide"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center hover:shadow-2xl transition-all transform hover:scale-110 z-20">
          <i data-lucide="chevron-left" class="w-6 h-6"></i>
        </button>

        <!-- Dots -->
        <div class="flex items-center justify-center gap-3 mt-12">
          <template x-for="(book, index) in featuredBooks" :key="index">
            <button @click="currentSlide = index"
                    :class="currentSlide === index ? 'w-12 bg-[#64CCC5]' : 'w-3 bg-white/30'"
                    class="h-3 rounded-full transition-all"></button>
          </template>
        </div>
      </div>

    </div>
  </section>

  <!-- Divider -->
  <div class="section-divider">
    <div class="inline-flex items-center gap-2 px-6 py-3 rounded-full category-badge">
      <i data-lucide="library" class="w-5 h-5"></i>
      <span class="text-lg font-bold">المكتبة الكاملة</span>
    </div>
  </div>

  <!-- Search & Filter -->
  <section class="py-8">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">

        <!-- Search -->
        <div class="relative mb-8">
          <input type="search" x-model="searchQuery"
                 placeholder="ابحث عن كتاب، مؤلف، أو موضوع..."
                 class="w-full px-8 py-6 pr-16 rounded-2xl text-lg border-2 border-[#64CCC5]/20 focus:border-[#64CCC5] transition-all font-tajawal"
                 :class="darkMode ? 'bg-white/5 text-white' : 'bg-white text-gray-900'">
          <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 w-6 h-6 text-[#64CCC5]"></i>
        </div>

        <!-- Categories -->
        <div class="flex flex-wrap items-center justify-center gap-3">
          <template x-for="category in categories" :key="category.id">
            <button @click="selectedCategory = category.id"
                    :class="selectedCategory === category.id ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-xl scale-105' : (darkMode ? 'bg-white/5 hover:bg-white/10' : 'bg-black/5 hover:bg-black/10')"
                    class="flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all">
              <i :data-lucide="category.icon" class="w-5 h-5"></i>
              <span x-text="category.name"></span>
              <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                    :class="selectedCategory === category.id ? 'bg-white/20' : (darkMode ? 'bg-white/10' : 'bg-black/10')"
                    x-text="category.count"></span>
            </button>
          </template>
        </div>
      </div>
    </div>
  </section>

  <!-- Books Library -->
  <section class="py-16 islamic-pattern">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-12">
        <template x-for="(book, index) in filteredBooks" :key="book.id">

          <a :href="bookUrl(book.slug)"
             class="book-3d group block animate-scaleIn"
             :class="'stagger-' + ((index % 8) + 1)">

            <div class="book-container">

              <!-- Book Cover -->
              <div class="ornamental-border">
                <div class="book-cover-3d leather-texture aspect-[2.5/3.5] rounded-r-lg bg-gradient-to-br relative overflow-hidden"
                     :class="book.coverGradient">

                  <div class="book-pages"></div>
                  <div class="book-spine"></div>
                  <div class="shimmer-accent"></div>

                  <div class="relative z-10 h-full flex flex-col justify-between p-6 text-white">

                    <div class="flex justify-center">
                      <div class="w-12 h-12 rounded-full border border-[#64CCC5] flex items-center justify-center backdrop-blur-sm bg-white/5">
                        <i data-lucide="book-open" class="w-6 h-6 text-[#64CCC5]"></i>
                      </div>
                    </div>

                    <div class="text-center">
                      <h3 class="text-2xl md:text-3xl font-bold mb-2 font-amiri leading-tight" x-text="book.title"></h3>
                      <p class="text-sm opacity-80" x-text="book.authorShort"></p>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                      <div class="flex items-center gap-1">
                        <i data-lucide="file-text" class="w-3 h-3 text-[#64CCC5]"></i>
                        <span x-text="book.hadithCount.toLocaleString()"></span>
                      </div>
                      <div class="flex items-center gap-1">
                        <i data-lucide="book" class="w-3 h-3 text-[#64CCC5]"></i>
                        <span x-text="book.chapterCount"></span>
                      </div>
                    </div>
                  </div>

                  <div class="book-info-overlay">
                    <p class="text-white text-sm leading-relaxed mb-4 font-amiri" x-text="book.description"></p>
                    <div class="flex items-center gap-2 text-white text-sm">
                      <span class="font-bold">استكشف</span>
                      <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Book Info -->
              <div class="mt-4 text-center">
                <h4 class="font-bold text-lg mb-1 group-hover:text-[#64CCC5] transition-colors font-amiri" x-text="book.title"></h4>
                <p class="text-sm opacity-70" x-text="book.author"></p>
              </div>
            </div>
          </a>

        </template>
      </div>

      <!-- No Results -->
      <div x-show="filteredBooks.length === 0" class="text-center py-20">
        <i data-lucide="search-x" class="w-20 h-20 mx-auto mb-6 opacity-20"></i>
        <p class="text-2xl opacity-70">لا توجد نتائج</p>
      </div>

    </div>
  </section>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
