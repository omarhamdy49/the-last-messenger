@extends('layouts.public')

@section('title', 'مستكشف الحديث الشريف - خاتَمُ المُرْسَلِين')
@section('meta_description', 'ابحث وتصفح في مجموعة واسعة من الأحاديث النبوية الموثوقة من الكتب الستة وغيرها')

@push('head')
{{-- Plain CSS (was type="text/tailwindcss", which this Tailwind v3 build never parsed). --}}
<style>
    @layer utilities {
      .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .glass-light {
        background: rgba(0, 0, 0, 0.02);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.05);
      }

      .premium-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      }

      .premium-card:hover {
        transform: translateY(-4px);
      }

      .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }

      .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
      }

      .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 86, 179, 0.3);
        border-radius: 10px;
      }

      .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 86, 179, 0.5);
      }

      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes shimmer {
        0% {
          background-position: -1000px 0;
        }
        100% {
          background-position: 1000px 0;
        }
      }

      .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
      }

      .stagger-1 { animation-delay: 0.1s; }
      .stagger-2 { animation-delay: 0.2s; }
      .stagger-3 { animation-delay: 0.3s; }
      .stagger-4 { animation-delay: 0.4s; }

      .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
      }
    }
</style>
@endpush

@section('content')

  <!-- Main Content -->
  <main class="py-8 sm:py-12"
        x-data="hadithExplorer()">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Hero Section -->
      <div class="text-center mb-10 animate-fade-in-up">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] mb-6 shadow-xl">
          <i class="w-10 h-10 text-white" data-lucide="book-open"></i>
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold mb-4 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
          مستكشف الحديث الشريف
        </h1>
        <p class="text-lg max-w-2xl mx-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
          ابحث وتصفح في مجموعة واسعة من الأحاديث النبوية الموثوقة من الكتب الستة وغيرها
        </p>
      </div>

      <!-- Quick Filter Pills (Sticky) -->
      <section class="sticky top-16 z-40 py-4 mb-6 transition-colors duration-300 animate-fade-in-up stagger-1"
               :class="darkMode ? 'bg-gray-950/95 backdrop-blur-lg' : 'bg-gray-50/95 backdrop-blur-lg'">
        <div class="flex overflow-x-auto custom-scrollbar gap-3 pb-2">
          <a href="{{ route('public.hadith.explorer') }}"
                  class="whitespace-nowrap px-5 py-2.5 rounded-full font-semibold transition-all duration-300 flex items-center gap-2"
                  :class="{{ (int) $filters['collection'] === 0 ? 'true' : 'false' }} ?
                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                    (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
            <i class="w-4 h-4" data-lucide="layers"></i>
            الكل
            <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                  :class="{{ (int) $filters['collection'] === 0 ? 'true' : 'false' }} ? 'bg-white/20' : (darkMode ? 'bg-gray-700' : 'bg-gray-200')">
              {{ number_format($total) }}
            </span>
          </a>

          @foreach ($collections as $collection)
          <a href="{{ route('public.hadith.explorer', array_merge(request()->query(), ['collection' => $collection['id'], 'page' => 1])) }}"
                  class="whitespace-nowrap px-5 py-2.5 rounded-full font-semibold transition-all duration-300 flex items-center gap-2"
                  :class="{{ (int) $filters['collection'] === (int) $collection['id'] ? 'true' : 'false' }} ?
                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' :
                    (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-md')">
            <i class="w-4 h-4" data-lucide="book"></i>
            {{ $collection['name'] }}
          </a>
          @endforeach
        </div>
      </section>

      <!-- Main Grid -->
      <div class="lg:grid lg:grid-cols-12 lg:gap-8">

        <!-- Advanced Filters Sidebar -->
        <aside class="lg:col-span-3 mb-8 lg:mb-0 animate-fade-in-up stagger-2">
          <div class="sticky top-36 rounded-2xl shadow-xl p-6 transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold flex items-center gap-3"
                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                  <i class="w-5 h-5 text-white" data-lucide="sliders-horizontal"></i>
                </div>
                تصفية النتائج
              </h3>
              <a href="{{ route('public.hadith.explorer') }}"
                      class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                      :class="darkMode ? 'text-gray-400 hover:bg-gray-800 hover:text-white' : 'text-gray-600 hover:bg-gray-100'">
                إعادة تعيين
              </a>
            </div>

            <!-- Filters Form -->
            <form method="GET" action="{{ route('public.hadith.explorer') }}" class="space-y-6">

              <!-- Search Input -->
              <div>
                <label class="block text-sm font-semibold mb-3 flex items-center gap-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <i class="w-4 h-4" data-lucide="search"></i>
                  البحث في نص الحديث
                </label>
                <div class="relative">
                  <input type="text"
                         name="q"
                         value="{{ $filters['q'] }}"
                         x-model="searchText"
                         placeholder="ابحث في متن الحديث أو الشرح..."
                         class="w-full px-4 py-2.5 ps-10 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                         :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-gray-50 border-gray-200 placeholder-gray-400'" />
                  <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="w-4 h-4" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" data-lucide="search"></i>
                  </div>
                  <!-- Clear Button -->
                  <button type="button"
                          x-show="searchText.length > 0"
                          @click="searchText = ''"
                          class="absolute inset-y-0 end-0 flex items-center pe-3 transition-opacity hover:opacity-70">
                    <i class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" data-lucide="x-circle"></i>
                  </button>
                </div>
              </div>

              <!-- Book Filter -->
              <div>
                <label class="block text-sm font-semibold mb-3 flex items-center gap-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <i class="w-4 h-4" data-lucide="library"></i>
                  الكتاب
                </label>
                <select name="collection"
                        class="w-full px-4 py-2.5 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'">
                  <option value="0">الكل</option>
                  @foreach ($collections as $collection)
                  <option value="{{ $collection['id'] }}" @selected((int) $filters['collection'] === (int) $collection['id'])>{{ $collection['name'] }}</option>
                  @endforeach
                </select>
              </div>

              <!-- Authenticity Filter -->
              <div>
                <label class="block text-sm font-semibold mb-3 flex items-center gap-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <i class="w-4 h-4" data-lucide="shield-check"></i>
                  درجة الصحة
                </label>
                <div class="space-y-2">
                  @foreach ($gradingOptions as $option)
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="grading" value="{{ $option['value'] }}" @checked($filters['grading'] === $option['value'])
                           class="w-4 h-4 rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3]" />
                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $option['label'] }}</span>
                  </label>
                  @endforeach
                </div>
              </div>

              <!-- Sort Filter -->
              <div>
                <label class="block text-sm font-semibold mb-3 flex items-center gap-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <i class="w-4 h-4" data-lucide="arrow-up-down"></i>
                  الترتيب
                </label>
                <select name="sort"
                        class="w-full px-4 py-2.5 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'">
                  <option value="asc" @selected($filters['sort'] === 'asc')>تصاعدي حسب رقم الحديث</option>
                  <option value="desc" @selected($filters['sort'] === 'desc')>تنازلي حسب رقم الحديث</option>
                </select>
              </div>

              <!-- Per Page Filter -->
              <div>
                <label class="block text-sm font-semibold mb-3 flex items-center gap-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <i class="w-4 h-4" data-lucide="list"></i>
                  عدد النتائج
                </label>
                <select name="per_page"
                        class="w-full px-4 py-2.5 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'">
                  @foreach ($perPageOptions as $option)
                  <option value="{{ $option }}" @selected((int) $filters['per_page'] === (int) $option)>{{ $option }}</option>
                  @endforeach
                </select>
              </div>

              <!-- Action Buttons -->
              <div class="pt-4 space-y-3">
                <button type="submit"
                        class="w-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold py-3 px-4 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                  <i class="w-5 h-5" data-lucide="filter"></i>
                  تطبيق الفلاتر
                </button>
                <a href="{{ route('public.hadith.explorer') }}"
                        class="w-full font-semibold py-3 px-4 rounded-xl transition-all duration-300 flex items-center justify-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                  <i class="w-5 h-5" data-lucide="rotate-ccw"></i>
                  إعادة تعيين
                </a>
              </div>
            </form>
          </div>
        </aside>

        <!-- Results Section -->
        <div class="lg:col-span-9 animate-fade-in-up stagger-3">

          <!-- Quick Search Bar -->
          <div class="rounded-2xl shadow-lg p-6 mb-6 transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                <i class="w-5 h-5 text-white" data-lucide="search"></i>
              </div>
              <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                البحث السريع
              </h3>
            </div>

            <form method="GET" action="{{ route('public.hadith.explorer') }}" class="relative">
              <input type="text"
                     name="q"
                     value="{{ $filters['q'] }}"
                     placeholder="اكتب كلمة أو عبارة للبحث في الأحاديث (اضغط Enter للبحث)..."
                     class="w-full px-6 py-4 ps-14 pe-32 rounded-xl border text-lg focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                     :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-gray-50 border-gray-200 placeholder-gray-400'" />
              <div class="absolute inset-y-0 start-0 flex items-center ps-5 pointer-events-none">
                <i class="w-6 h-6" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" data-lucide="search"></i>
              </div>
              <button type="submit"
                      class="absolute inset-y-0 end-2 my-2 px-6 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold rounded-lg hover:shadow-lg transition-all duration-300 flex items-center gap-2">
                <i class="w-4 h-4" data-lucide="search"></i>
                بحث
              </button>
            </form>

            <!-- Popular Search Terms -->
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="text-xs font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                بحث شائع:
              </span>
              @foreach (['النية', 'الجهاد', 'الصدقة', 'الصلاة', 'الزكاة', 'الصيام'] as $term)
              <a href="{{ route('public.hadith.explorer', ['q' => $term]) }}"
                      class="px-3 py-1 rounded-full text-xs font-semibold transition-all duration-300"
                      :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                {{ $term }}
              </a>
              @endforeach
            </div>
          </div>

          @if (! $hasFilters)
          <!-- Prompt State -->
          <div class="rounded-2xl shadow-lg p-12 text-center transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 mb-4">
              <i class="w-8 h-8 text-[#0056b3]" data-lucide="search"></i>
            </div>
            <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              ابدأ البحث في الأحاديث
            </h3>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
              استخدم مربع البحث أو الفلاتر للعثور على الأحاديث النبوية الشريفة
            </p>
          </div>
          @elseif (count($results) === 0)
          <!-- Empty Results -->
          <div class="rounded-2xl shadow-lg p-12 text-center transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 mb-4">
              <i class="w-8 h-8 text-[#0056b3]" data-lucide="search-x"></i>
            </div>
            <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
              لا توجد نتائج
            </h3>
            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
              لم نعثر على أحاديث تطابق بحثك. جرّب كلمات أخرى أو أعد تعيين الفلاتر.
            </p>
            <a href="{{ route('public.hadith.explorer') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
              <i class="w-4 h-4" data-lucide="rotate-ccw"></i>
              إعادة تعيين
            </a>
          </div>
          @else

          <!-- Results Header & Controls -->
          <div class="rounded-2xl shadow-lg p-6 mb-6 transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

            @if ($filters['q'] !== '')
            <!-- Active Search Indicator -->
            <div class="mb-4 p-4 rounded-xl border-2 border-dashed flex items-center justify-between gap-3"
                 :class="darkMode ? 'bg-[#0056b3]/10 border-[#0056b3]/30' : 'bg-[#0056b3]/5 border-[#0056b3]/20'">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                  <i class="w-5 h-5 text-white" data-lucide="search"></i>
                </div>
                <div>
                  <p class="text-xs font-semibold mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    نتائج البحث عن:
                  </p>
                  <p class="font-bold text-lg" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    "{{ $filters['q'] }}"
                  </p>
                </div>
              </div>
              <a href="{{ route('public.hadith.explorer', collect(request()->query())->except('q')->toArray()) }}"
                      class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2"
                      :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i class="w-4 h-4" data-lucide="x"></i>
                إلغاء البحث
              </a>
            </div>
            @endif

            <!-- Results Count & Sort -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 flex items-center justify-center">
                  <i class="w-6 h-6 text-[#0056b3]" data-lucide="check-circle"></i>
                </div>
                <div>
                  <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    تم العثور على
                  </p>
                  <p class="text-2xl font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                    {{ number_format($total) }} حديث
                  </p>
                </div>
              </div>

              <!-- Sort Options -->
              <form method="GET" action="{{ route('public.hadith.explorer') }}" class="flex items-center gap-3">
                @foreach (request()->except(['sort', 'page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <label class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  ترتيب حسب:
                </label>
                <select name="sort" onchange="this.form.submit()"
                        class="px-4 py-2.5 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'">
                  <option value="asc" @selected($filters['sort'] === 'asc')>رقم الحديث تصاعدياً</option>
                  <option value="desc" @selected($filters['sort'] === 'desc')>رقم الحديث تنازلياً</option>
                </select>
              </form>
            </div>
          </div>

          <!-- Hadith Results -->
          <div class="space-y-6">

            @foreach ($results as $result)
            <!-- Hadith Card -->
            <a href="{{ route('public.hadith.show', $result['id']) }}"
                 class="premium-card block rounded-2xl shadow-xl overflow-hidden transition-colors duration-300"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
              <div class="p-6 sm:p-8">

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                  <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                      <span class="text-white font-bold">{{ $result['number'] }}</span>
                    </div>
                    <div>
                      <h4 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $result['collectionName'] }} - حديث رقم {{ $result['number'] }}
                      </h4>
                      @if ($result['bookTitle'])
                      <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ $result['bookTitle'] }}
                      </p>
                      @endif
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    @php
                      $gradeColorClass = match ($result['grading']['variant']) {
                        'daif' => 'bg-red-500/20 text-red-600 border border-red-500/20',
                        'hasan' => 'bg-amber-500/20 text-amber-600 border border-amber-500/20',
                        'sahih' => 'bg-green-500/20 text-green-600 border border-green-500/20',
                        default => 'bg-gray-500/20 text-gray-600 border border-gray-500/20',
                      };
                    @endphp
                    <span class="px-4 py-2 rounded-full text-xs font-bold {{ $gradeColorClass }}">
                      {{ $result['grading']['label'] }}
                    </span>
                  </div>
                </div>

                @if ($result['text'])
                <!-- Hadith Text -->
                <blockquote class="relative rounded-xl p-6 mb-6 transition-colors duration-300"
                            :class="darkMode ? 'bg-gray-800/50 border-r-4 border-[#0056b3]' : 'bg-gradient-to-r from-gray-50 to-white border-r-4 border-[#0056b3]'">
                  <div class="absolute top-4 start-4 opacity-20">
                    <i class="w-8 h-8 text-[#0056b3]" data-lucide="quote"></i>
                  </div>
                  <p class="text-lg leading-relaxed relative z-10 line-clamp-4" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ $result['text'] }}
                  </p>
                </blockquote>
                @endif

                <!-- Metadata -->
                <div class="grid sm:grid-cols-2 gap-4 mb-6 p-4 rounded-xl transition-colors duration-300"
                     :class="darkMode ? 'bg-gray-800/30' : 'bg-gray-50'">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 flex items-center justify-center">
                      <i class="w-5 h-5 text-[#0056b3]" data-lucide="book-open"></i>
                    </div>
                    <div>
                      <p class="text-xs font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المرجع</p>
                      <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $result['collectionName'] }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0056b3]/20 to-[#218838]/20 flex items-center justify-center">
                      <i class="w-5 h-5 text-[#0056b3]" data-lucide="hash"></i>
                    </div>
                    <div>
                      <p class="text-xs font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">رقم الحديث</p>
                      <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $result['number'] }}</p>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3">
                  <span class="flex-1 sm:flex-initial px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="w-5 h-5" data-lucide="book-open"></i>
                    عرض التفاصيل
                  </span>
                </div>
              </div>
            </a>
            @endforeach
          </div>

          <!-- Advanced Pagination -->
          @if ($paginator && $paginator->hasPages())
          <div class="mt-8 rounded-2xl shadow-xl p-6 transition-colors duration-300"
               :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">

            <!-- Page Info -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
              <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                عرض <span class="font-bold text-[#0056b3]">{{ number_format($paginator->firstItem() ?? 0) }}</span> -
                <span class="font-bold text-[#0056b3]">{{ number_format($paginator->lastItem() ?? 0) }}</span>
                من <span class="font-bold text-[#0056b3]">{{ number_format($total) }}</span> نتيجة
              </p>

              <!-- Jump to Page -->
              <div class="flex items-center gap-3"
                   x-data="{
                       page: {{ $paginator->currentPage() }},
                       last: {{ $paginator->lastPage() }},
                       urlTemplate: {{ \Illuminate\Support\Js::from($paginator->url(999999999)) }},
                       go() {
                           const p = Math.min(this.last, Math.max(1, parseInt(this.page, 10) || 1));
                           window.location.href = this.urlTemplate.replace('page=999999999', 'page=' + p);
                       }
                   }">
                <label class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  الانتقال إلى:
                </label>
                <input type="number"
                       x-model.number="page"
                       @keydown.enter.prevent="go"
                       min="1"
                       :max="last"
                       class="w-20 px-3 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] text-center"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'" />
                <button type="button" @click="go"
                        class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold rounded-lg hover:shadow-lg transition-all duration-300">
                  اذهب
                </button>
              </div>
            </div>

            <!-- Pagination Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-2">

              <!-- First Page -->
              <a href="{{ $paginator->url(1) }}"
                 @class([
                    'px-4 py-2 rounded-lg font-semibold transition-all duration-300',
                    'opacity-50 cursor-not-allowed pointer-events-none' => $paginator->onFirstPage(),
                 ])
                 :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i class="w-4 h-4" data-lucide="chevrons-right"></i>
              </a>

              <!-- Previous Page -->
              <a href="{{ $paginator->url(max(1, $paginator->currentPage() - 1)) }}"
                 @class([
                    'px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2',
                    'opacity-50 cursor-not-allowed pointer-events-none' => $paginator->onFirstPage(),
                 ])
                 :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i class="w-4 h-4" data-lucide="chevron-right"></i>
                السابق
              </a>

              <!-- Page Numbers -->
              @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
              <a href="{{ $url }}"
                 @class([
                    'px-4 py-2 rounded-lg font-bold transition-all duration-300 min-w-[44px] text-center',
                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-110' => $page === $paginator->currentPage(),
                 ])
                 @if ($page !== $paginator->currentPage())
                 :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                 @endif>
                {{ $page }}
              </a>
              @endforeach

              <!-- Next Page -->
              <a href="{{ $paginator->url(min($paginator->lastPage(), $paginator->currentPage() + 1)) }}"
                 @class([
                    'px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2',
                    'opacity-50 cursor-not-allowed pointer-events-none' => $paginator->currentPage() === $paginator->lastPage(),
                 ])
                 :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                التالي
                <i class="w-4 h-4" data-lucide="chevron-left"></i>
              </a>

              <!-- Last Page -->
              <a href="{{ $paginator->url($paginator->lastPage()) }}"
                 @class([
                    'px-4 py-2 rounded-lg font-semibold transition-all duration-300',
                    'opacity-50 cursor-not-allowed pointer-events-none' => $paginator->currentPage() === $paginator->lastPage(),
                 ])
                 :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i class="w-4 h-4" data-lucide="chevrons-left"></i>
              </a>
            </div>
          </div>
          @endif

          @endif
        </div>
      </div>
    </div>
  </main>

@endsection

@push('scripts')
<script>
    function hadithExplorer() {
      return {
        get darkMode() {
          return this.$store.theme ? this.$store.theme.dark : (localStorage.getItem('darkMode') === 'true');
        },
        searchText: @json($filters['q']),
      };
    }
</script>
@endpush
