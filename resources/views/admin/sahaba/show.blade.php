@extends('layouts.admin')

{{--
    Person profile — read view.

    Deliberately NOT using @section('page-header'): the hero band below carries
    the name, status and actions, so a second title strip above it would compete
    with the one thing this page is about.

    Design notes, since this page is the module's visual reference:
      - Palette is the admin's own brand gradient. Gold appears only for the
        `prophet` role, matching the treatment the public home page already uses
        for the same subject — an accent with a meaning, not decoration.
      - Long-form text is capped at a reading measure, not the column width, so
        a biography stays readable on a wide screen.
      - Numbers use `tabular-nums` so year columns do not shift between rows.
      - Every badge pairs its colour with a word; none rely on hue alone.
--}}

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $backArrow = $isRtl ? 'right' : 'left';

    $nameAr = $muslim->getTranslation('name', 'ar');
    $nameEn = $muslim->getTranslation('name', 'en');
    $primaryName = $isRtl ? ($nameAr ?: $nameEn) : ($nameEn ?: $nameAr);
    $secondaryName = $isRtl ? $nameEn : $nameAr;
    $kunya = $muslim->getTranslation('kunya', $locale);

    $profile = $muslim->profile;
    $roleSlugs = $muslim->roles->pluck('slug')->all();
    $isProphet = in_array('prophet', $roleSlugs, true);

    $roleIcons = [
        'prophet' => 'sparkles',
        'sahabi' => 'star',
        'narrator' => 'quote',
        'scholar' => 'graduation-cap',
        'grader' => 'gavel',
        'compiler' => 'library',
    ];

    // Signed Hijri years. A negative year is pre-Hijra, and rendering it as a
    // minus sign reads as mangled in RTL ("هـ 11 – 53-"), so it is spelled out
    // as ق.هـ / BH instead — which is also how the era is actually written.
    $hijriYear = function (?int $year): ?string {
        if ($year === null) {
            return null;
        }

        return $year < 0
            ? abs($year).' '.__('sahaba.before_hijra_short')
            : $year.' '.__('sahaba.hijri');
    };

    $birth = $hijriYear($muslim->birth_year_hijri);
    $death = $hijriYear($muslim->death_year_hijri);
    $lifespan = ($birth !== null || $death !== null)
        ? ($birth ?? '—').' — '.($death ?? '—')
        : null;

    // The strip under the hero. Only facts that exist — an empty slot says
    // nothing and costs a column.
    $specs = collect([
        ['icon' => 'user', 'label' => __('sahaba.gender'), 'value' => $muslim->gender?->label($locale)],
        ['icon' => 'layers', 'label' => __('sahaba.generation'), 'value' => \App\Domain\People\Enums\Generation::labelFor($muslim->generation, $locale)],
        ['icon' => 'calendar-range', 'label' => __('sahaba.section_dates'), 'value' => $lifespan, 'nums' => true],
        ['icon' => 'map-pin', 'label' => __('sahaba.birth_place'), 'value' => $muslim->getTranslation('birth_place', $locale)],
        ['icon' => 'users', 'label' => __('sahaba.tribe'), 'value' => $profile->localized('tribe', $locale)],
    ])->filter(fn (array $s) => filled($s['value']))->values();

    $bio = $muslim->getTranslation('bio', $locale);
    $content = $muslim->getTranslation('content', $locale);

    // Jump links, built from what the page will actually render so the nav can
    // never point at a section that is not there.
    $sections = collect([
        ['id' => 'biography', 'label' => __('sahaba.section_story'), 'show' => filled($bio) || filled($content)],
        ['id' => 'detail', 'label' => __('sahaba.profile_groups.identity'), 'show' => ! $profile->isEmpty()],
        ['id' => 'lineage', 'label' => __('sahaba.section_lineage'), 'show' => true],
    ])->where('show', true)->values();
@endphp

@push('styles')
<style>
    /* Hero texture: two soft radial washes rather than an image — no request,
       no layout shift, and it holds up in both themes. */
    .profile-hero-texture {
        background-image:
            radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.18), transparent 45%),
            radial-gradient(circle at 85% 90%, rgba(255, 255, 255, 0.10), transparent 40%);
    }

    /* Reading measure for long-form fields (65–75 characters). */
    .profile-prose {
        max-width: 68ch;
        line-height: 1.8;
        white-space: pre-line;
    }

    .profile-lift {
        transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 200ms cubic-bezier(0.4, 0, 0.2, 1);
    }
    .profile-lift:hover { transform: translateY(-2px); }

    @media (prefers-reduced-motion: reduce) {
        .profile-animate { animation: none !important; }
        .profile-lift { transition: none !important; }
        .profile-lift:hover { transform: none !important; }
        html { scroll-behavior: auto !important; }
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="{ activeSection: '{{ $sections->first()['id'] ?? '' }}' }">

    {{-- ==================== HERO ==================== --}}
    <section class="relative overflow-hidden rounded-3xl shadow-2xl profile-animate animate-fadeInUp"
             aria-labelledby="profile-name">
        <div class="absolute inset-0 bg-gradient-primary" aria-hidden="true"></div>
        <div class="absolute inset-0 profile-hero-texture" aria-hidden="true"></div>

        {{-- Gold wash, only for the Prophet ﷺ. --}}
        @if($isProphet)
            <div class="absolute inset-0 opacity-40"
                 style="background-image: radial-gradient(circle at 50% 0%, rgba(250, 204, 21, 0.55), transparent 55%);"
                 aria-hidden="true"></div>
        @endif

        {{-- Watermark of the primary role. The identity block sits at the start
             of the band, leaving the far end empty at desktop widths; this fills
             it with something that still means something (which role this person
             is primarily recorded under) rather than an arbitrary flourish. --}}
        @if($muslim->roles->isNotEmpty())
            <i data-lucide="{{ $roleIcons[$muslim->roles->first()->slug] ?? 'user-check' }}"
               class="hidden lg:block absolute end-10 top-1/2 -translate-y-1/2 w-48 h-48 text-white opacity-[0.07] pointer-events-none"
               aria-hidden="true"></i>
        @endif

        <div class="relative p-6 sm:p-8 lg:p-10">

            {{-- Back + actions. Glass buttons so they read as controls on a
                 saturated band without competing with the name. --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
                <a href="{{ route('admin.sahaba.index') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-white/90
                          bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 transition-colors">
                    <i data-lucide="arrow-{{ $backArrow }}" class="w-4 h-4"></i>
                    <span>{{ __('sahaba.back_to_list') }}</span>
                </a>

                <div class="flex flex-wrap items-center gap-2">
                    @if($muslim->is_published)
                        <a href="{{ route('public.sahaba.show', $muslim->slug) }}" target="_blank" rel="noopener"
                           aria-label="{{ __('sahaba.view_public') }}"
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-white/90
                                  bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 transition-colors">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">{{ __('sahaba.view_public') }}</span>
                        </a>
                    @endif

                    <a href="{{ route('admin.genealogy.show', $muslim) }}"
                       aria-label="{{ __('sahaba.genealogy.manage') }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-white/90
                              bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-sm
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 transition-colors">
                        <i data-lucide="git-fork" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">{{ __('sahaba.genealogy.manage') }}</span>
                    </a>

                    {{-- The single primary action on this page. --}}
                    <a href="{{ route('admin.sahaba.edit', $muslim) }}"
                       class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-bold
                              bg-white text-[#0056b3] shadow-lg hover:shadow-xl
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 profile-lift">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        <span>{{ __('sahaba.edit') }}</span>
                    </a>
                </div>
            </div>

            {{-- Identity --}}
            <div class="flex flex-col sm:flex-row sm:items-end gap-6">
                <div class="relative shrink-0 mx-auto sm:mx-0">
                    @if($muslim->hasMedia())
                        <img src="{{ $muslim->getFirstMediaUrl() }}" alt="{{ $primaryName }}"
                             width="112" height="112"
                             class="w-28 h-28 rounded-2xl object-cover shadow-2xl ring-4
                                    {{ $isProphet ? 'ring-yellow-400/70' : 'ring-white/30' }}">
                    @else
                        <div class="w-28 h-28 rounded-2xl grid place-items-center shadow-2xl ring-4 bg-white/15 backdrop-blur-sm
                                    {{ $isProphet ? 'ring-yellow-400/70' : 'ring-white/30' }}"
                             aria-hidden="true">
                            <i data-lucide="{{ $isProphet ? 'sparkles' : 'user' }}" class="w-12 h-12 text-white/90"></i>
                        </div>
                    @endif

                    @if($isProphet)
                        <span class="absolute -top-2 -end-2 w-8 h-8 rounded-full grid place-items-center
                                     bg-yellow-400 text-white shadow-lg ring-2 ring-white/40"
                              title="{{ __('site.seal_of_prophets') }}">
                            <i data-lucide="crown" class="w-4 h-4"></i>
                        </span>
                    @endif
                </div>

                <div class="min-w-0 flex-1 text-center sm:text-start">
                    @if($kunya)
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70 mb-2">{{ $kunya }}</p>
                    @endif

                    <h1 id="profile-name" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight">
                        {{ $primaryName ?: '—' }}
                    </h1>

                    @if($secondaryName)
                        <p class="mt-1.5 text-base text-white/75 font-medium"
                           dir="{{ $isRtl ? 'ltr' : 'rtl' }}">{{ $secondaryName }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        @foreach($muslim->roles as $role)
                            <a href="{{ route('admin.sahaba.index', ['role' => $role->slug]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                                      border backdrop-blur-sm transition-colors
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70
                                      {{ $role->slug === 'prophet'
                                          ? 'bg-yellow-400/25 border-yellow-300/50 text-yellow-50 hover:bg-yellow-400/40'
                                          : 'bg-white/15 border-white/25 text-white hover:bg-white/25' }}">
                                <i data-lucide="{{ $roleIcons[$role->slug] ?? 'user-check' }}" class="w-3.5 h-3.5"></i>
                                {{ $role->name() }}
                                @if($role->pivot->is_featured)
                                    <i data-lucide="star" class="w-3 h-3"></i>
                                @endif
                            </a>
                        @endforeach

                        @if($muslim->is_published)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                                         bg-emerald-400/25 border border-emerald-300/40 text-emerald-50 backdrop-blur-sm">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                {{ __('sahaba.published') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                                         bg-black/30 border border-white/20 text-white/85 backdrop-blur-sm">
                                <i data-lucide="circle-slash" class="w-3.5 h-3.5"></i>
                                {{ __('sahaba.unpublished') }}
                            </span>
                        @endif

                        @if($muslim->hide_from_tree)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                                         bg-amber-400/25 border border-amber-300/40 text-amber-50 backdrop-blur-sm">
                                <i data-lucide="eye-off" class="w-3.5 h-3.5"></i>
                                {{ __('sahaba.hide_from_tree') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Spec strip, seated inside the hero so the key facts read as part of
             the identity rather than as the first of many cards. --}}
        @if($specs->isNotEmpty())
            @php
                // Columns follow the number of facts, so a missing one never
                // leaves an empty cell sitting in the strip.
                $specCols = [
                    1 => 'sm:grid-cols-1',
                    2 => 'sm:grid-cols-2',
                    3 => 'sm:grid-cols-3',
                    4 => 'sm:grid-cols-2 lg:grid-cols-4',
                    5 => 'sm:grid-cols-3 lg:grid-cols-5',
                ][$specs->count()] ?? 'sm:grid-cols-3 lg:grid-cols-5';
            @endphp
            <dl class="relative grid grid-cols-2 {{ $specCols }} gap-px bg-white/15 border-t border-white/20">
                @foreach($specs as $spec)
                    <div class="px-5 py-4 bg-black/20 backdrop-blur-sm">
                        <dt class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-white/70">
                            <i data-lucide="{{ $spec['icon'] }}" class="w-3.5 h-3.5"></i>
                            {{ $spec['label'] }}
                        </dt>
                        <dd class="mt-1 text-sm font-bold text-white {{ ($spec['nums'] ?? false) ? 'tabular-nums' : '' }}"
                            @if($spec['nums'] ?? false) dir="ltr" @endif>{{ $spec['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </section>

    {{-- ==================== SECTION NAV ==================== --}}
    @if($sections->count() > 1)
        <nav class="sticky top-2 z-20 flex overflow-x-auto custom-scrollbar py-1"
             aria-label="{{ __('sahaba.section_overview') }}">
            <div class="inline-flex items-center gap-1 p-1 rounded-2xl glass shadow-lg border"
                 :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">
                @foreach($sections as $section)
                    <a href="#{{ $section['id'] }}"
                       @click="activeSection = '{{ $section['id'] }}'"
                       class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                       :class="activeSection === '{{ $section['id'] }}'
                           ? 'bg-gradient-primary text-white shadow'
                           : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        {{ $section['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    {{-- ==================== BODY ==================== --}}
    <div class="grid gap-6 lg:grid-cols-12 lg:items-start">

        <div class="lg:col-span-8 space-y-6">

            @if(filled($bio) || filled($content))
                <section id="biography" class="scroll-mt-24 rounded-2xl border shadow-sm overflow-hidden"
                         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <header class="px-6 sm:px-8 pt-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em]"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('sahaba.section_story') }}</p>
                    </header>

                    <div class="px-6 sm:px-8 py-6 space-y-6">
                        @if(filled($bio))
                            <p class="profile-prose text-[15px]"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $bio }}</p>
                        @endif

                        @if(filled($content))
                            @if(filled($bio))
                                <hr class="border-dashed" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            @endif
                            <div class="profile-prose text-[15px]"
                                 :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $content }}</div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Extended profile. Rendered from the same registry as the form, so
                 a field added there appears here too. --}}
            @unless($profile->isEmpty())
                <section id="detail" class="scroll-mt-24 rounded-2xl border shadow-sm overflow-hidden"
                         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <header class="px-6 sm:px-8 py-5 border-b flex items-center gap-3"
                            :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <span class="w-9 h-9 rounded-xl grid place-items-center bg-gradient-primary shadow">
                            <i data-lucide="id-card" class="w-4 h-4 text-white"></i>
                        </span>
                        <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('sahaba.profile_groups.identity') }}
                        </h2>
                    </header>

                    <div class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        @foreach(\App\Domain\People\ValueObjects\MuslimProfile::GROUPS as $group)
                            @php
                                $rows = collect(\App\Domain\People\ValueObjects\MuslimProfile::fieldsInGroup($group, $roleSlugs))
                                    ->map(fn ($field) => [
                                        'label' => $field->label(),
                                        'value' => $field->translatable
                                            ? $profile->localized($field->key, $locale)
                                            : $profile->get($field->key),
                                        'nums' => $field->type === \App\Domain\People\Enums\ProfileFieldType::Number,
                                        'long' => $field->type === \App\Domain\People\Enums\ProfileFieldType::LongText,
                                    ])
                                    ->filter(fn (array $row) => filled($row['value']));
                            @endphp

                            @continue($rows->isEmpty())

                            <div class="px-6 sm:px-8 py-6">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] mb-4"
                                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    {{ __('sahaba.profile_groups.'.$group) }}
                                </p>

                                <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                                    @foreach($rows as $row)
                                        <div class="{{ $row['long'] ? 'sm:col-span-2' : '' }}">
                                            <dt class="text-xs font-semibold mb-1"
                                                :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $row['label'] }}</dt>
                                            <dd class="text-[15px] {{ $row['long'] ? 'profile-prose' : 'font-semibold' }} {{ $row['nums'] ? 'tabular-nums' : '' }}"
                                                @if($row['nums']) dir="ltr" @endif
                                                :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $row['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endunless
        </div>

        {{-- ==================== ASIDE ==================== --}}
        <aside class="lg:col-span-4 space-y-6">

            <section id="lineage" class="scroll-mt-24 rounded-2xl border shadow-sm overflow-hidden"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <header class="px-6 py-5 border-b flex items-center justify-between gap-3"
                        :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 shrink-0 rounded-xl grid place-items-center bg-gradient-primary shadow">
                            <i data-lucide="git-fork" class="w-4 h-4 text-white"></i>
                        </span>
                        <h2 class="text-lg font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('sahaba.section_lineage') }}
                        </h2>
                    </div>
                    <a href="{{ route('admin.genealogy.show', $muslim) }}"
                       class="p-2 rounded-lg shrink-0 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                       aria-label="{{ __('sahaba.genealogy.manage') }}">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </a>
                </header>

                <div class="p-6 space-y-5">
                    {{-- Parents on a rail, so the direction of descent is visible
                         rather than implied by two stacked labels. --}}
                    <ol class="space-y-4 ps-5 border-s-2"
                        :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        @foreach(['father' => __('sahaba.father'), 'mother' => __('sahaba.mother')] as $rel => $relLabel)
                            @php $parent = $muslim->{$rel}; @endphp
                            <li class="relative">
                                <span class="absolute -start-[1.43rem] top-2 w-2.5 h-2.5 rounded-full ring-2"
                                      :class="darkMode ? 'bg-gray-700 ring-gray-900' : 'bg-gray-300 ring-white'"
                                      aria-hidden="true"></span>
                                <p class="text-[11px] font-bold uppercase tracking-wider"
                                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $relLabel }}</p>
                                @if($parent)
                                    <a href="{{ route('admin.sahaba.show', $parent) }}"
                                       class="text-sm font-bold hover:text-accent transition-colors rounded
                                              focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $parent->name($locale) }}
                                    </a>
                                @else
                                    <p class="text-sm" :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    @if(count($relations) > 0)
                        <div class="pt-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <p class="text-[11px] font-bold uppercase tracking-wider mb-3"
                               :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('sahaba.relations') }}</p>
                            <ul class="space-y-2">
                                @foreach($relations as $relation)
                                    <li class="flex items-center justify-between gap-3 px-3 py-2 rounded-xl text-sm"
                                        :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                                        <span class="font-semibold truncate"
                                              :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $relation['name'] }}</span>
                                        <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-bold"
                                              :class="darkMode ? 'bg-accent/15 text-accent' : 'bg-[#0056b3]/10 text-[#0056b3]'">{{ $relation['label'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Record meta. Quiet by design: useful, never the headline. --}}
            @php
                $meta = collect([
                    ['label' => __('sahaba.slug'), 'value' => $muslim->slug, 'mono' => true],
                    ['label' => __('sahaba.tree_display_order'), 'value' => $muslim->tree_display_order, 'nums' => true],
                ])->filter(fn ($row) => filled($row['value']));
            @endphp

            @if($meta->isNotEmpty())
                <section class="rounded-2xl border shadow-sm overflow-hidden"
                         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <header class="px-6 py-4 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em]"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('sahaba.record_meta') }}</p>
                    </header>

                    <dl class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        @foreach($meta as $row)
                            <div class="px-6 py-3 flex items-center justify-between gap-4">
                                <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $row['label'] }}</dt>
                                <dd class="text-sm font-semibold text-end {{ ($row['mono'] ?? false) ? 'font-mono text-xs' : '' }} {{ ($row['nums'] ?? false) ? 'tabular-nums' : '' }}"
                                    dir="ltr"
                                    :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $row['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();

        // Keep the jump nav in step with what is on screen, so clicking a link
        // and scrolling by hand cannot disagree.
        //
        // A scroll listener on the real scroll container, not an
        // IntersectionObserver: the admin layout scrolls an inner <main>, and an
        // observer rooted on the viewport did not track it here.
        const root = document.querySelector('[x-data]');
        const sections = [...document.querySelectorAll('section[id]')];

        if (! root || sections.length < 2) return;

        const scroller = document.querySelector('main') ?? window;
        let queued = false;

        const update = () => {
            queued = false;
            if (! window.Alpine) return;

            const marker = window.innerHeight * 0.3;
            let current = sections[0].id;

            for (const section of sections) {
                if (section.getBoundingClientRect().top <= marker) {
                    current = section.id;
                }
            }

            // At the bottom the marker can never reach the final section, so it
            // would never light up. Treat "scrolled to the end" as being on it.
            const atEnd = scroller === window
                ? window.innerHeight + window.scrollY >= document.body.scrollHeight - 4
                : scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4;

            window.Alpine.$data(root).activeSection = atEnd ? sections[sections.length - 1].id : current;
        };

        scroller.addEventListener('scroll', () => {
            if (queued) return;
            queued = true;
            requestAnimationFrame(update);
        }, { passive: true });

        update();
    });
</script>
@endpush
