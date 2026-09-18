@extends('layouts.public')

@section('title', __('site.faq_title') . ' — ' . __('site.site_name'))
@section('meta_description', __('site.faq_subtitle'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-label="__('site.faq_title')" eyebrow-icon="circle-help"
            :title="__('site.faq_title')" :lead="__('site.faq_subtitle')" />

        <div class="max-w-3xl">
            @forelse ($faqGroups as $category => $faqs)
                @php
                    $categoryKey = 'site.faq_category_'.$category;
                    $categoryLabel = Lang::has($categoryKey) ? __($categoryKey) : $category;
                @endphp
                <x-public.editorial.section :title="$categoryLabel" icon="message-circle-question" class="mb-10">
                    <div class="grid gap-3" x-data="{ open: null }">
                        @foreach ($faqs as $faq)
                            <div class="sx-surface overflow-hidden">
                                <button type="button"
                                        @click="open = open === {{ $faq['id'] }} ? null : {{ $faq['id'] }}"
                                        :aria-expanded="open === {{ $faq['id'] }} ? 'true' : 'false'"
                                        aria-controls="faq-a-{{ $faq['id'] }}"
                                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start font-bold text-[color:var(--sx-ink)] transition-colors hover:bg-[color:var(--sx-surface-2)]">
                                    <span class="break-words">{{ $faq['question'] }}</span>
                                    <i data-lucide="chevron-down" aria-hidden="true"
                                       class="w-5 h-5 shrink-0 text-[color:var(--sx-accent)] transition-transform"
                                       :class="open === {{ $faq['id'] }} ? 'rotate-180' : ''"></i>
                                </button>
                                <div id="faq-a-{{ $faq['id'] }}"
                                     x-show="open === {{ $faq['id'] }}"
                                     x-collapse
                                     x-cloak
                                     class="px-5 pb-5 text-sm leading-loose text-[color:var(--sx-ink-2)] break-words">
                                    {{ $faq['answer'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-public.editorial.section>
            @empty
                {{-- Empty state --}}
                <x-site.empty-state class="sx-surface" icon="circle-help"
                                    :title="__('site.faq_empty')" message="">
                    <a href="{{ Route::has('public.contact') ? route('public.contact') : route('public.home') }}" class="sx-btn is-primary mt-2">
                        <i data-lucide="mail" aria-hidden="true"></i>
                        {{ __('site.contact') }}
                    </a>
                </x-site.empty-state>
            @endforelse
        </div>
    </div>
</div>
@endsection
