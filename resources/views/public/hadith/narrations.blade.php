@extends('layouts.public')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $allVersions = array_merge([array_merge($base, ['isBase' => true])], array_map(
        static fn (array $version) => array_merge($version, ['isBase' => false]),
        $versions,
    ));
    $metaTitle = __('hadith.public.narrations_title').' - '.trim(($base['collectionName'] ?? '').' '.$base['number']);
@endphp

@section('title', $metaTitle)
@section('meta_description', \Illuminate\Support\Str::limit(__('hadith.public.narrations_subtitle'), 155))

@push('head')
<style>
    [x-cloak] { display: none !important; }

    .narration-arabic {
        font-family: 'Amiri', serif;
        line-height: 2.3;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up { animation: fadeInUp 0.5s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .fade-in-up { animation: none; }
    }
</style>
@endpush

@section('content')

<div x-data="{
        darkMode: ($store.theme && $store.theme.dark) ?? (localStorage.getItem('darkMode') === 'true'),

        init() {
            if (this.$store.theme) { this.$watch('$store.theme.dark', v => this.darkMode = v); }
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        }
    }">

    <main class="py-8 md:py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Breadcrumb -->
            <nav class="flex mb-8 text-sm" aria-label="Breadcrumb">
                <ol class="inline-flex items-center gap-2 flex-wrap">
                    <li>
                        <a href="{{ url('/') }}" class="flex items-center gap-1 transition-colors"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            <i data-lucide="home" class="w-4 h-4"></i>
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li>
                        <a href="{{ route('public.hadith') }}" class="transition-colors"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            {{ __('hadith.public.breadcrumb') }}
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li>
                        <a href="{{ route('public.hadith.show', $base['id']) }}" class="transition-colors"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            {{ __('hadith.public.single_number') }} {{ $base['number'] }}
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">
                        {{ __('hadith.public.narrations_breadcrumb') }}
                    </li>
                </ol>
            </nav>

            <!-- Header -->
            <header class="text-center mb-10 fade-in-up">
                <h1 class="text-3xl md:text-4xl font-bold mb-4 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                    {{ __('hadith.public.narrations_title') }}
                </h1>
                <p class="text-base md:text-lg max-w-3xl mx-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('hadith.public.narrations_subtitle') }}
                </p>
                <a href="{{ route('public.hadith.show', $base['id']) }}"
                   class="inline-flex items-center gap-2 mt-6 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all">
                    <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                    {{ __('hadith.public.narrations_back') }}
                </a>
            </header>

            @if (count($versions) > 0)
                <!-- Comparison columns: stack on mobile, side-by-side from md -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
                    @foreach ($allVersions as $version)
                        <article class="rounded-2xl shadow-xl overflow-hidden fade-in-up flex flex-col"
                                 :class="darkMode ? 'bg-gray-900' : 'bg-white'">

                            <!-- Source header -->
                            <div class="p-5 border-b bg-gradient-to-r from-[#0056b3]/10 to-[#218838]/10"
                                 :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        {{ $version['isBase']
                                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                            : 'bg-[#64CCC5]/20 text-[#0d7d75] dark:text-[#64CCC5]' }}">
                                        {{ $version['isBase'] ? __('hadith.public.narrations_base_badge') : __('hadith.public.narrations_parallel_badge') }}
                                    </span>

                                    @php $variant = $version['grading']['variant']; @endphp
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        {{ $variant === 'sahih' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                            : ($variant === 'hasan' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                                            : ($variant === 'daif' ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')) }}">
                                        {{ $version['grading']['label'] }}
                                    </span>
                                </div>

                                <h2 class="font-bold text-lg break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $version['collectionName'] }}
                                    <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                        ({{ $version['number'] }})
                                    </span>
                                </h2>
                                @if ($version['bookTitle'])
                                    <p class="text-sm truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ $version['bookTitle'] }}
                                    </p>
                                @endif
                            </div>

                            <!-- Full text -->
                            <div class="p-5 md:p-6 flex-1">
                                @if ($version['text'])
                                    <blockquote dir="rtl" class="narration-arabic text-lg break-words"
                                                :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                                        {{ $version['text'] }}
                                    </blockquote>
                                @else
                                    <p class="text-sm italic" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('hadith.public.single_no_translation') }}
                                    </p>
                                @endif
                            </div>

                            <!-- Isnad chain -->
                            @if (count($version['isnad']) > 0)
                                <div class="px-5 md:px-6 pb-5">
                                    <h3 class="text-sm font-bold mb-3 flex items-center gap-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        <i data-lucide="users" class="w-4 h-4 text-[#64CCC5]"></i>
                                        {{ __('hadith.public.narrations_isnad') }}
                                    </h3>
                                    <ol class="space-y-2 border-s-2 border-[#64CCC5]/40 ps-4">
                                        @foreach ($version['isnad'] as $narrator)
                                            <li class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                                <span class="font-semibold">{{ $narrator['name'] }}</span>
                                                @if ($narrator['reliability'])
                                                    <span class="text-xs ms-1 px-2 py-0.5 rounded-full"
                                                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                                        {{ $narrator['reliability'] }}
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            @endif

                            <!-- Scholar gradings -->
                            @if (count($version['scholarGradings']) > 0)
                                <div class="px-5 md:px-6 pb-6">
                                    <h3 class="text-sm font-bold mb-2 flex items-center gap-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-[#64CCC5]"></i>
                                        {{ __('hadith.public.narrations_grading') }}
                                    </h3>
                                    <ul class="space-y-1">
                                        @foreach (array_slice($version['scholarGradings'], 0, 4) as $grading)
                                            <li class="text-sm flex flex-wrap items-center gap-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                                @if ($grading['scholar'])
                                                    <span class="font-semibold">{{ $grading['scholar'] }}:</span>
                                                @endif
                                                <span>{{ $grading['grade'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <!-- Unique narration: no parallels -->
                <div class="max-w-2xl mx-auto rounded-2xl p-12 text-center shadow-lg fade-in-up" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                    <i data-lucide="gem" class="w-12 h-12 mx-auto mb-4 text-[#64CCC5]"></i>
                    <h2 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('hadith.public.narrations_empty_title') }}
                    </h2>
                    <p class="text-sm mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('hadith.public.narrations_empty_message') }}
                    </p>
                    <a href="{{ route('public.hadith.show', $base['id']) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-full font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all">
                        <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4"></i>
                        {{ __('hadith.public.narrations_back') }}
                    </a>
                </div>
            @endif

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
