@extends('layouts.public')

@section('title', ($profile['name'] ?? __('sahaba.page_title')) . ' | ' . __('site.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($profile['biography'] ?? __('sahaba.page_description')), 155))
@section('og_type', 'profile')

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Person",
    "name": "{{ $profile['name'] }}",
    "description": "{{ \Illuminate\Support\Str::limit(strip_tags($profile['biography'] ?? ''), 200) }}",
    "url": "{{ route('public.sahaba.show', $sahabi->slug) }}"
}
</script>
@endpush

@push('head')
<style>
    /* Premium Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
    }

    /* Hero Parallax Background */
    .hero-parallax {
      background:
        radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.08) 0%, transparent 50%),
        linear-gradient(135deg, rgba(0, 86, 179, 0.03) 0%, rgba(33, 136, 56, 0.03) 100%);
      animation: meshMove 20s ease-in-out infinite;
    }

    @keyframes meshMove {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    /* Glassmorphism */
    .glass {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .glass-dark {
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Floating Animation */
    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-15px) rotate(2deg); }
    }

    .float-animation {
      animation: float 6s ease-in-out infinite;
    }

    /* Pulse Glow for Badge */
    @keyframes pulseGlow {
      0%, 100% {
        box-shadow: 0 0 20px rgba(0, 86, 179, 0.4);
      }
      50% {
        box-shadow: 0 0 40px rgba(0, 86, 179, 0.8), 0 0 60px rgba(33, 136, 56, 0.6);
      }
    }

    .pulse-glow {
      animation: pulseGlow 3s ease-in-out infinite;
    }

    /* Fade In Animations */
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

    @keyframes fadeInScale {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }

    .fade-in-scale {
      animation: fadeInScale 0.6s ease-out forwards;
    }

    /* Stagger delays */
    .stagger-1 { animation-delay: 0.1s; opacity: 0; }
    .stagger-2 { animation-delay: 0.2s; opacity: 0; }
    .stagger-3 { animation-delay: 0.3s; opacity: 0; }
    .stagger-4 { animation-delay: 0.4s; opacity: 0; }
    .stagger-5 { animation-delay: 0.5s; opacity: 0; }

    /* Premium Card Hover */
    .premium-card {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 86, 179, 0.2);
    }

    /* Timeline Connector Animation */
    @keyframes drawLine {
      from {
        height: 0;
      }
      to {
        height: 100%;
      }
    }

    .timeline-line {
      animation: drawLine 1.5s ease-out forwards;
    }

    /* Quote Styling */
    .quote-mark {
      font-size: 4rem;
      line-height: 0;
      opacity: 0.15;
      font-family: Georgia, serif;
    }

    /* Progress Bar */
    .reading-progress {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(to right, #0056b3, #218838);
      transform-origin: left;
      z-index: 9999;
    }
</style>
@endpush

@section('content')

@php
    $locale = app()->getLocale();
    $isRtl  = in_array($locale, ['ar', 'ur'], true);
    $avatarIcon = $profile['isFemale'] ? 'circle-user-round' : 'shield';

    // Info-card rows — only render the ones with data (mirrors prototype's بطاقة تعريفية list).
    $infoRows = array_values(array_filter([
        $profile['kunya']       ? ['icon' => 'user-round',     'color' => 'text-[#0056b3]', 'label' => __('sahaba.kunya'),         'value' => $profile['kunya']] : null,
        $profile['tribe']       ? ['icon' => 'users',          'color' => 'text-[#218838]', 'label' => __('sahaba.tribe'),         'value' => $profile['clan'] ? $profile['tribe'].' — '.$profile['clan'] : $profile['tribe']] : null,
        $profile['birthPlace']  ? ['icon' => 'map-pin',        'color' => 'text-[#64CCC5]', 'label' => __('sahaba.birth_place'),   'value' => $profile['birthPlace']] : null,
        $profile['conversionDateHijri'] ? ['icon' => 'calendar-check', 'color' => 'text-[#218838]', 'label' => __('sahaba.conversion'), 'value' => $profile['conversionDateHijri'].' '.__('sahaba.year_hijri')] : null,
        (! $profile['conversionDateHijri'] && $profile['conversionOrder']) ? ['icon' => 'calendar-check', 'color' => 'text-[#218838]', 'label' => __('sahaba.conversion_order'), 'value' => $profile['conversionOrder'].' '.__('sahaba.order_unit')] : null,
        ! empty($profile['battles']) ? ['icon' => 'swords',    'color' => 'text-[#64CCC5]', 'label' => __('sahaba.battles'),       'value' => implode('، ', array_slice($profile['battles'], 0, 4))] : null,
        ($profile['deathDateHijri'] || $profile['deathPlace']) ? ['icon' => 'heart', 'color' => 'text-red-600', 'label' => __('sahaba.death_place'), 'value' => trim(($profile['deathPlace'] ?? '').' '.($profile['deathDateHijri'] ? $profile['deathDateHijri'].' '.__('sahaba.year_hijri') : ''))] : null,
    ]));

    // Hero quick-stats — only meaningful numbers.
    $quickStats = array_values(array_filter([
        $profile['ageAtDeath']   ? ['value' => $profile['ageAtDeath'].'+', 'label' => __('sahaba.years_unit')] : null,
        $profile['battlesCount'] ? ['value' => $profile['battlesCount'], 'label' => __('sahaba.battles')] : null,
        $profile['hadithCount']  ? ['value' => number_format($profile['hadithCount']), 'label' => __('sahaba.hadith_narrated')] : null,
    ]));

    // Scroll-spy sections, only for content that actually exists.
    $sections = [];
    if (! empty($profile['biography']))        { $sections['story']     = ['label' => __('sahaba.section_story'),     'icon' => 'book-open']; }
    if (! empty($profile['virtues']))          { $sections['virtues']   = ['label' => __('sahaba.section_virtues'),   'icon' => 'sword']; }
    if (! empty($profile['martyrdom']))        { $sections['martyrdom'] = ['label' => __('sahaba.section_martyrdom'), 'icon' => 'heart']; }
    if (! empty($profile['timeline']))         { $sections['timeline']  = ['label' => __('sahaba.section_timeline'),  'icon' => 'milestone']; }
    $firstSection = array_key_first($sections) ?? 'story';

    $timelineColors = [
        'sky'     => '#0056b3', 'blue' => '#0056b3',
        'emerald' => '#218838', 'green' => '#218838',
        'teal'    => '#64CCC5', 'cyan' => '#64CCC5',
        'red'     => '#dc2626', 'rose' => '#dc2626',
        'amber'   => '#f59e0b', 'purple' => '#6f42c1',
    ];
@endphp

<div x-data="{
  darkMode: $store.theme.dark,
  searchOpen: false,
  scrollProgress: 0,
  activeSection: '{{ $firstSection }}',
  bookmarked: false,
  sections: {{ \Illuminate\Support\Js::from(array_keys($sections)) }},

  init() {
    this.$watch('$store.theme.dark', v => this.darkMode = v);
    try { this.bookmarked = (JSON.parse(localStorage.getItem('bookmarkedSahaba') || '[]')).includes('{{ $sahabi->slug }}'); } catch (e) {}
    window.addEventListener('scroll', () => {
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.scrollHeight - windowHeight;
      const scrolled = window.scrollY;
      this.scrollProgress = documentHeight > 0 ? (scrolled / documentHeight) * 100 : 0;
      this.updateActiveSection();
    });
  },

  updateActiveSection() {
    for (let i = this.sections.length - 1; i >= 0; i--) {
      const el = document.getElementById(this.sections[i]);
      if (el && el.getBoundingClientRect().top <= 140) { this.activeSection = this.sections[i]; return; }
    }
  },

  scrollToSection(section) {
    this.activeSection = section;
    const el = document.getElementById(section);
    if (el) { el.scrollIntoView({ behavior: 'smooth' }); }
  },

  toggleBookmark() {
    let list = [];
    try { list = JSON.parse(localStorage.getItem('bookmarkedSahaba') || '[]'); } catch (e) {}
    const idx = list.indexOf('{{ $sahabi->slug }}');
    if (idx > -1) { list.splice(idx, 1); this.bookmarked = false; }
    else { list.push('{{ $sahabi->slug }}'); this.bookmarked = true; }
    localStorage.setItem('bookmarkedSahaba', JSON.stringify(list));
  },

  async share() {
    const data = { title: '{{ addslashes($profile['name']) }}', url: window.location.href };
    if (navigator.share) { try { await navigator.share(data); } catch (e) {} }
    else { try { await navigator.clipboard.writeText(window.location.href); $store.toast.push('{{ __('sahaba.link_copied') }}'); } catch (e) {} }
  }
}">

  <!-- Reading Progress Bar -->
  <div class="reading-progress" :style="`transform: scaleX(${scrollProgress / 100})`"></div>

  <!-- Premium Hero Section -->
  <section class="relative hero-parallax overflow-hidden pt-24 pb-16" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumb -->
      <nav class="flex mb-8 text-sm fade-in-up stagger-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <ol class="inline-flex items-center space-s-2">
          <li>
            <a href="{{ url('/') }}" class="hover:text-[#0056b3] transition-colors">{{ __('site.nav_home') }}</a>
          </li>
          <li><i class="w-3 h-3" data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}"></i></li>
          <li>
            <a href="{{ route('public.sahaba') }}" class="hover:text-[#0056b3] transition-colors">{{ __('sahaba.page_title') }}</a>
          </li>
          <li><i class="w-3 h-3" data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}"></i></li>
          <li class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
            {{ $profile['name'] }}
          </li>
        </ol>
      </nav>

      <!-- Hero Content -->
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">

        <!-- Profile Card - Left Side (Desktop) / Top (Mobile) -->
        <div class="lg:col-span-4 xl:col-span-3">
          <div class="sticky top-24 space-y-6">

            <!-- Main Profile Card -->
            <div class="glass dark:glass-dark rounded-3xl p-8 text-center shadow-2xl fade-in-scale stagger-2"
                 :class="darkMode ? 'text-white' : 'text-gray-900'">

              <!-- Animated Avatar with Gradient Border -->
              <div class="relative inline-block mb-6 float-animation">
                <div class="absolute inset-0 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full blur-xl opacity-50 pulse-glow"></div>
                <div class="relative w-32 h-32 sm:w-40 sm:h-40 mx-auto rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-2xl">
                  <i class="w-20 h-20 sm:w-24 sm:h-24 text-white" data-lucide="{{ $avatarIcon }}"></i>
                </div>
              </div>

              <!-- Name & Title -->
              <h1 class="text-3xl sm:text-4xl font-black mb-2 break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $profile['name'] }}
              </h1>
              @if ($profile['laqab'] || $profile['statusLabel'])
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-sm shadow-lg">
                  <i class="w-4 h-4 me-2" data-lucide="award"></i>
                  {{ $profile['laqab'] ?: $profile['statusLabel'] }}
                </div>
              @endif

              <p class="mt-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ $profile['isFemale'] ? __('site.radia_allah_anha') : __('site.radi_allah_anhu') }}
              </p>

              @if (! empty($quickStats))
                <!-- Quick Stats -->
                <div class="grid grid-cols-{{ count($quickStats) }} gap-4 mt-8 pt-6 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                  @foreach ($quickStats as $stat)
                    <div class="text-center">
                      <div class="text-2xl font-black bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">{{ $stat['value'] }}</div>
                      <div class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $stat['label'] }}</div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>

            @if (! empty($infoRows))
              <!-- Info Card with Icons -->
              <div class="glass dark:glass-dark rounded-3xl p-6 shadow-xl fade-in-up stagger-3"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                  <i class="w-5 h-5 me-3 text-[#64CCC5]" data-lucide="info"></i>
                  {{ __('sahaba.profile_card') }}
                </h3>

                <ul class="space-y-5">
                  @foreach ($infoRows as $row)
                    <li class="flex items-start group">
                      <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-[#0056b3]/10 to-[#218838]/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="w-5 h-5 {{ $row['color'] }}" data-lucide="{{ $row['icon'] }}"></i>
                      </div>
                      <div class="ms-4 min-w-0">
                        <div class="font-bold text-sm{{ $row['color'] === 'text-red-600' ? ' text-red-600' : '' }}" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $row['label'] }}</div>
                        <div class="text-sm mt-1 break-words" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $row['value'] }}</div>
                      </div>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif

            <!-- Action Buttons -->
            <div class="glass dark:glass-dark rounded-3xl p-6 shadow-xl space-y-3 fade-in-up stagger-4"
                 :class="darkMode ? 'text-white' : 'text-gray-900'">
              <button type="button" @click="toggleBookmark()" class="w-full flex items-center justify-center px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold hover:shadow-xl hover:scale-105 transition-all">
                <i class="w-5 h-5 me-2" data-lucide="bookmark"></i>
                <span x-text="bookmarked ? '{{ __('sahaba.bookmarked') }}' : '{{ __('sahaba.bookmark') }}'"></span>
              </button>
              <button type="button" @click="share()" class="w-full flex items-center justify-center px-6 py-3 rounded-xl border-2 hover:bg-gray-50 dark:hover:bg-gray-800 font-bold transition-all"
                      :class="darkMode ? 'border-gray-700 text-white' : 'border-gray-300 text-gray-900'">
                <i class="w-5 h-5 me-2" data-lucide="share-2"></i>
                {{ __('sahaba.share') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Main Content - Right Side -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-8">

          <!-- Section Navigation Pills -->
          <div class="glass dark:glass-dark rounded-2xl p-2 shadow-xl fade-in-up stagger-2 overflow-x-auto custom-scrollbar">
            <div class="flex space-s-2 min-w-max">
              @foreach ($sections as $key => $section)
                <button @click="scrollToSection('{{ $key }}')"
                        class="px-6 py-3 rounded-xl font-bold text-sm transition-all whitespace-nowrap"
                        :class="activeSection === '{{ $key }}' ?
                          'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                          (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')">
                  <i class="w-4 h-4 me-2 inline-block" data-lucide="{{ $section['icon'] }}"></i>
                  {{ $section['label'] }}
                </button>
              @endforeach
            </div>
          </div>

          @if (! empty($profile['biography']))
            <!-- Story of Conversion Section -->
            <div id="story" class="glass dark:glass-dark rounded-3xl p-8 sm:p-10 shadow-2xl fade-in-up stagger-3"
                 :class="darkMode ? 'text-white' : 'text-gray-900'"
                 x-data="{ expanded: false }">
              <div class="flex items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                  <i class="w-6 h-6 text-white" data-lucide="book-open"></i>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black ms-4">{{ __('sahaba.section_story') }}</h2>
              </div>

              @if ($profile['fullName'] && $profile['fullName'] !== $profile['name'])
                <!-- Quote Box -->
                <div class="relative mb-8 p-8 rounded-2xl bg-gradient-to-br from-[#0056b3]/5 to-[#218838]/5 border-r-4 border-[#0056b3]">
                  <div class="quote-mark absolute top-4 right-4 text-[#0056b3]">"</div>
                  <p class="text-lg leading-relaxed relative z-10 break-words" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ $profile['fullName'] }}
                  </p>
                </div>
              @endif

              <div class="prose prose-lg max-w-none space-y-6" :class="darkMode ? 'prose-invert' : ''">
                <div class="text-lg leading-relaxed whitespace-pre-line break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  <div :class="!expanded ? 'line-clamp-[12]' : ''">{{ $profile['biography'] }}</div>
                </div>

                @if (mb_strlen(strip_tags($profile['biography'])) > 600)
                  <button type="button" @click="expanded = !expanded"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-[#0056b3] border border-[#0056b3]/30 hover:bg-[#0056b3]/5 transition-colors">
                    <span x-text="expanded ? '{{ __('sahaba.show_less') }}' : '{{ __('sahaba.show_more') }}'"></span>
                    <i class="w-4 h-4" :data-lucide="expanded ? 'chevron-up' : 'chevron-down'"></i>
                  </button>
                @endif

                @if (! empty($profile['roles']))
                  <div class="flex flex-wrap gap-2 pt-2">
                    @foreach ($profile['roles'] as $role)
                      <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#0056b3]/10 text-[#0056b3]">{{ $role }}</span>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          @endif

          @if (! empty($profile['virtues']))
            <!-- Heroism / Virtues Section -->
            <div id="virtues" class="glass dark:glass-dark rounded-3xl p-8 sm:p-10 shadow-2xl fade-in-up stagger-4"
                 :class="darkMode ? 'text-white' : 'text-gray-900'">
              <div class="flex items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#218838] to-[#64CCC5] flex items-center justify-center">
                  <i class="w-6 h-6 text-white" data-lucide="sword"></i>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black ms-4">{{ __('sahaba.section_virtues') }}</h2>
              </div>

              <div class="prose prose-lg max-w-none space-y-6" :class="darkMode ? 'prose-invert' : ''">
                <div class="text-lg leading-relaxed whitespace-pre-line break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                  {{ $profile['virtues'] }}
                </div>

                @if (! empty($profile['famousNarrations']))
                  <!-- Prophet's Grief Card -->
                  <div class="mt-8 p-8 rounded-2xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <div class="flex items-start">
                      <i class="w-8 h-8 text-[#64CCC5] flex-shrink-0" data-lucide="quote"></i>
                      <div class="ms-4 min-w-0">
                        <h4 class="text-xl font-bold mb-2">{{ __('sahaba.section_narrations') }}</h4>
                        <div class="text-base leading-relaxed whitespace-pre-line break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $profile['famousNarrations'] }}</div>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          @endif

          @if (! empty($profile['martyrdom']))
            <!-- Martyrdom Section -->
            <div id="martyrdom" class="glass dark:glass-dark rounded-3xl p-8 sm:p-10 shadow-2xl fade-in-up stagger-5"
                 :class="darkMode ? 'text-white' : 'text-gray-900'">
              <div class="flex items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                  <i class="w-6 h-6 text-white" data-lucide="heart"></i>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black ms-4">{{ __('sahaba.section_martyrdom') }}</h2>
              </div>

              <div class="prose prose-lg max-w-none space-y-6" :class="darkMode ? 'prose-invert' : ''">
                <!-- Tragedy Highlight -->
                <div class="relative my-8 p-8 rounded-2xl bg-gradient-to-br from-red-500/10 to-red-600/10 border-r-4 border-red-600">
                  <div class="text-lg leading-relaxed whitespace-pre-line break-words" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ $profile['martyrdom'] }}
                  </div>
                </div>
              </div>
            </div>
          @endif

          @if (! empty($profile['timeline']))
            <!-- Interactive Timeline -->
            <div id="timeline" class="glass dark:glass-dark rounded-3xl p-8 sm:p-10 shadow-2xl fade-in-up stagger-5"
                 :class="darkMode ? 'text-white' : 'text-gray-900'">
              <div class="flex items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#64CCC5] to-[#0056b3] flex items-center justify-center">
                  <i class="w-6 h-6 text-white" data-lucide="milestone"></i>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black ms-4">{{ __('sahaba.section_timeline') }}</h2>
              </div>

              <!-- Timeline Items -->
              <div class="relative pe-8">
                <!-- Timeline Line -->
                <div class="absolute right-0 top-0 w-1 bg-gradient-to-b from-[#0056b3] via-[#218838] to-[#64CCC5] timeline-line" style="height: 100%;"></div>

                <!-- Timeline Events -->
                <div class="space-y-8">
                  @foreach ($profile['timeline'] as $item)
                    @php $dotColor = $timelineColors[$item['color']] ?? '#0056b3'; @endphp
                    <div class="relative">
                      <div class="absolute -right-3 top-0 w-7 h-7 rounded-full border-4" style="background-color: {{ $dotColor }}" :class="darkMode ? 'border-black' : 'border-white'"></div>
                      <div class="premium-card p-6 rounded-2xl me-12" :class="darkMode ? 'bg-gray-800/50' : 'bg-white'">
                        <div class="flex items-start justify-between gap-3 mb-2 flex-wrap">
                          <h3 class="text-xl font-bold break-words">{{ $item['title'] }}</h3>
                          @if ($item['hijriDate'] || $item['hijriYear'])
                            <span class="px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap" style="background-color: {{ $dotColor }}1a; color: {{ $dotColor }}">{{ $item['hijriDate'] ?: ($item['hijriYear'].' '.__('sahaba.year_hijri')) }}</span>
                          @endif
                        </div>
                        @if ($item['description'])
                          <p class="text-sm break-words" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $item['description'] }}
                          </p>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endif

          <!-- Related Content -->
          <div class="grid sm:grid-cols-2 gap-6">
            <!-- Related Articles -->
            <div class="glass dark:glass-dark rounded-3xl p-6 shadow-xl @if(empty($profile['article'])) sm:col-span-1 @endif" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <h3 class="text-xl font-bold mb-6 flex items-center">
                <i class="w-5 h-5 me-3 text-[#0056b3]" data-lucide="book-open"></i>
                {{ __('sahaba.related_article') }}
              </h3>
              <div class="space-y-3">
                @if (! empty($profile['article']))
                  @php
                    $articleUrl = Route::has('public.articles.show')
                        ? route('public.articles.show', $profile['article']['slug'])
                        : '#';
                  @endphp
                  <a href="{{ $articleUrl }}" class="premium-card block p-4 rounded-xl border-2 border-transparent hover:border-[#0056b3] transition-all"
                     :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <div class="font-bold mb-1 break-words">{{ $profile['article']['title'] }}</div>
                  </a>
                @else
                  <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('sahaba.empty_message') }}</p>
                @endif
              </div>
            </div>

            <!-- Related Companions -->
            <div class="glass dark:glass-dark rounded-3xl p-6 shadow-xl" :class="darkMode ? 'text-white' : 'text-gray-900'">
              <h3 class="text-xl font-bold mb-6 flex items-center">
                <i class="w-5 h-5 me-3 text-[#218838]" data-lucide="users"></i>
                {{ __('sahaba.related_companions') }}
              </h3>
              <div class="space-y-3">
                @forelse ($related as $rel)
                  <a href="{{ route('public.sahaba.show', $rel['slug']) }}" class="premium-card block p-4 rounded-xl border-2 border-transparent hover:border-[#218838] transition-all"
                     :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <div class="font-bold mb-1 break-words">{{ $rel['name'] }}</div>
                    @if ($rel['laqab'])
                      <div class="text-xs break-words" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $rel['laqab'] }}</div>
                    @endif
                  </a>
                @empty
                  <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('sahaba.empty_message') }}</p>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.renderIcons === 'function') {
            window.renderIcons();
        } else if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endpush

@endsection
