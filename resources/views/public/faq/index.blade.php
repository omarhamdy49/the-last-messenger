@extends('layouts.public')

@section('title', __('site.faq_title') . ' — ' . __('site.site_name'))
@section('meta_description', __('site.faq_subtitle'))

@section('content')
<section class="relative py-16 sm:py-20 overflow-hidden">
    {{-- Decorative brand backdrop (matches auth pages) --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-10 end-10 w-72 h-72 bg-gradient-to-br from-[#0056b3]/10 to-[#218838]/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 start-10 w-96 h-96 bg-gradient-to-br from-[#218838]/10 to-[#64CCC5]/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            {{-- Header --}}
            <header class="text-center mb-10 animate-fade-in-up motion-reduce:animate-none">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-2xl flex items-center justify-center shadow-lg">
                    <i data-lucide="circle-help" class="w-8 h-8 text-white"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-black bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                    {{ __('site.faq_title') }}
                </h1>
                <p class="mt-3 text-gray-600 dark:text-gray-300">{{ __('site.faq_subtitle') }}</p>
            </header>

            @forelse ($faqGroups as $category => $faqs)
                @php
                    $categoryKey = 'site.faq_category_'.$category;
                    $categoryLabel = Lang::has($categoryKey) ? __($categoryKey) : $category;
                @endphp
                <section class="mb-10 animate-fade-in-up motion-reduce:animate-none">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-[#0056b3] to-[#218838]"></span>
                        {{ $categoryLabel }}
                    </h2>

                    <div class="space-y-3" x-data="{ open: null }">
                        @foreach ($faqs as $faq)
                            <div class="bg-white dark:bg-[#16213e] rounded-xl shadow-md border border-gray-100 dark:border-gray-800 overflow-hidden">
                                <button type="button"
                                        @click="open = open === {{ $faq['id'] }} ? null : {{ $faq['id'] }}"
                                        :aria-expanded="open === {{ $faq['id'] }} ? 'true' : 'false'"
                                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-start font-semibold hover:bg-[#F8F9FA] dark:hover:bg-[#0f3460]/40 transition">
                                    <span class="break-words">{{ $faq['question'] }}</span>
                                    <i data-lucide="chevron-down"
                                       class="w-5 h-5 shrink-0 text-gray-400 transition-transform"
                                       :class="open === {{ $faq['id'] }} ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open === {{ $faq['id'] }}"
                                     x-collapse
                                     x-cloak
                                     class="px-5 pb-5 text-sm leading-relaxed text-gray-600 dark:text-gray-300 break-words">
                                    {{ $faq['answer'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                {{-- Empty state --}}
                <div class="text-center py-16 bg-white dark:bg-[#16213e] rounded-2xl shadow-xl">
                    <i data-lucide="circle-help" class="w-10 h-10 mx-auto mb-4 text-gray-400"></i>
                    <p class="text-gray-600 dark:text-gray-300">{{ __('site.faq_empty') }}</p>
                    <a href="{{ Route::has('public.contact') ? route('public.contact') : route('public.home') }}"
                       class="mt-6 inline-flex items-center gap-2 text-[#0056b3] dark:text-[#4a9eff] font-semibold hover:underline">
                        {{ __('site.contact') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
