@extends('layouts.admin')

{{--
    Person profile — create.

    Deliberately the same shape as edit: the form is the same five-plus sections,
    so it gets the same section rail and the same sticky save bar. The previous
    version had neither, which meant creating a person involved scrolling the
    whole form to find a button that the edit page keeps permanently in reach.

    What it does NOT carry over is delete (nothing to delete yet) and the
    visibility report (nothing saved yet to report on).
--}}

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $backArrow = $isRtl ? 'right' : 'left';

    // No roles are assigned yet, so only the unrestricted profile groups render
    // — the rail is built from the same call the form uses so it can never list
    // a section that is not on the page.
    $rail = collect([
        ['id' => 'sec-status', 'label' => __('sahaba.section_status'), 'icon' => 'toggle-right'],
        ['id' => 'sec-identity', 'label' => __('sahaba.section_identity'), 'icon' => 'user'],
        ['id' => 'sec-dates', 'label' => __('sahaba.section_dates'), 'icon' => 'calendar'],
        ['id' => 'sec-roles', 'label' => __('sahaba.section_roles'), 'icon' => 'shield-check'],
        ['id' => 'sec-lineage', 'label' => __('sahaba.section_lineage'), 'icon' => 'git-fork'],
    ]);

    foreach (\App\Domain\People\ValueObjects\MuslimProfile::GROUPS as $group) {
        if (\App\Domain\People\ValueObjects\MuslimProfile::fieldsInGroup($group, []) === []) {
            continue;
        }

        $rail->push([
            'id' => 'sec-profile-'.$group,
            'label' => __('sahaba.profile_groups.'.$group),
            'icon' => ['identity' => 'id-card', 'birth' => 'baby', 'death' => 'moon', 'mission' => 'sparkles'][$group] ?? 'info',
        ]);
    }
@endphp

@push('styles')
<style>
    @media (prefers-reduced-motion: reduce) {
        .edit-animate { animation: none !important; }
        html { scroll-behavior: auto !important; }
    }
</style>
@endpush

@section('page-header')
<div class="rounded-2xl border shadow-sm px-5 py-4 edit-animate"
     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <span class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg shrink-0">
                <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold gradient-text truncate">{{ __('sahaba.create_title') }}</h1>
                <p class="mt-0.5 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('sahaba.admin_all_subtitle') }}
                </p>
            </div>
        </div>

        <a href="{{ route('admin.sahaba.index') }}"
           class="shrink-0 px-5 py-2.5 rounded-xl font-semibold transition-colors flex items-center gap-2 border-2
                  focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="arrow-{{ $backArrow }}" class="w-5 h-5"></i>
            <span>{{ __('sahaba.back_to_list') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="muslimEditor">
    <div class="grid gap-6 lg:grid-cols-12 lg:items-start">

        {{-- ==================== SECTION RAIL ==================== --}}
        <nav class="hidden lg:block lg:col-span-3 lg:sticky lg:top-4"
             aria-label="{{ __('sahaba.section_overview') }}">
            <div class="rounded-2xl border shadow-sm p-2"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <ul class="space-y-0.5">
                    @foreach($rail as $item)
                        <li>
                            <a href="#{{ $item['id'] }}"
                               @click="active = '{{ $item['id'] }}'"
                               class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                                      focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                               :class="active === '{{ $item['id'] }}'
                                   ? 'bg-gradient-primary text-white shadow'
                                   : (darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0"></i>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- The roles section is the one an editor skips and then cannot
                 find the person afterwards: no `sahabi` role means no entry in
                 the public directory, whatever else is filled in. --}}
            <p class="mt-3 px-4 py-3 rounded-xl text-xs leading-relaxed flex items-start gap-2"
               :class="darkMode ? 'bg-amber-900/20 text-amber-300' : 'bg-amber-50 text-amber-800'">
                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <span>{{ __('sahaba.visibility.needs_sahabi_role') }}</span>
            </p>
        </nav>

        {{-- ==================== FORM ==================== --}}
        <div class="lg:col-span-9">
            <form id="muslim-form" action="{{ route('admin.sahaba.store') }}" method="POST"
                  @submit="submitting = true">
                @csrf

                @include('admin.sahaba._form', ['muslim' => null, 'roles' => $roles])

                {{-- Same sticky save bar as edit. On create everything is
                     unsaved, so the status line says so from the start rather
                     than claiming "no changes". --}}
                <div class="sticky bottom-4 mt-6 z-20">
                    <div class="rounded-2xl border shadow-xl px-4 py-3 glass flex flex-wrap items-center gap-3"
                         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">

                        <p class="text-sm flex items-center gap-2 me-auto" aria-live="polite">
                            <span class="flex items-center gap-2 font-semibold"
                                  :class="darkMode ? 'text-amber-400' : 'text-amber-600'">
                                <span class="w-2 h-2 rounded-full bg-current" aria-hidden="true"></span>
                                {{ __('sahaba.unsaved_changes') }}
                            </span>
                        </p>

                        <a href="{{ route('admin.sahaba.index') }}"
                           class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                           :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'">
                            {{ __('admin.cancel') }}
                        </a>

                        <button type="submit" :disabled="submitting"
                                class="px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg flex items-center gap-2
                                       bg-gradient-primary text-white
                                       disabled:opacity-60 disabled:cursor-not-allowed
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-accent
                                       transition-opacity">
                            {{-- The x-show sits on the span, not the <i>: Lucide replaces
                                 the <i> with a generated <svg>, and a binding copied onto
                                 that new node evaluates outside this scope and throws. --}}
                            <span x-show="submitting" style="display:none" class="inline-flex">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            </span>
                            <span x-show="! submitting" class="inline-flex">
                                <i data-lucide="save" class="w-4 h-4"></i>
                            </span>
                            <span x-text="submitting ? '{{ __('sahaba.saving') }}' : '{{ __('sahaba.save') }}'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Outside the form: a picker inside it would submit its own search input. --}}
<x-admin.muslim-picker-modal :roles="$roles" />
@endsection

@include('admin.sahaba._editor-script', ['rail' => $rail])
