@extends('layouts.admin')

{{--
    Person profile — edit.

    The form is long (five to nine sections depending on the person's roles), so
    the two things that matter most are: knowing where you are, and being able to
    save without scrolling to find the button.

      - A section rail on the left tracks scroll position and jumps.
      - The save bar is sticky at the bottom and only asserts itself once
        something has actually changed, so it is a status line rather than a
        permanently shouting toolbar.
      - The destructive action lives apart from save/cancel and behind a confirm.
--}}

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $backArrow = $isRtl ? 'right' : 'left';

    $roleSlugs = $muslim->roles->pluck('slug')->all();
    $isProphet = in_array('prophet', $roleSlugs, true);

    // The rail mirrors the sections _form.blade.php renders, including the
    // role-restricted profile groups, so it can never list one that is absent.
    $rail = collect([
        // Status leads the form now, so it leads the rail too.
        ['id' => 'sec-status', 'label' => __('sahaba.section_status'), 'icon' => 'toggle-right'],
        ['id' => 'sec-identity', 'label' => __('sahaba.section_identity'), 'icon' => 'user'],
        ['id' => 'sec-dates', 'label' => __('sahaba.section_dates'), 'icon' => 'calendar'],
        ['id' => 'sec-roles', 'label' => __('sahaba.section_roles'), 'icon' => 'shield-check'],
        ['id' => 'sec-lineage', 'label' => __('sahaba.section_lineage'), 'icon' => 'git-fork'],
    ]);

    foreach (\App\Domain\People\ValueObjects\MuslimProfile::GROUPS as $group) {
        if (\App\Domain\People\ValueObjects\MuslimProfile::fieldsInGroup($group, $roleSlugs) === []) {
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
        <div class="flex items-center gap-4 min-w-0">
            <span class="w-12 h-12 shrink-0 rounded-xl grid place-items-center shadow-lg
                         {{ $isProphet ? 'bg-gradient-to-br from-yellow-400 to-amber-500' : 'bg-gradient-primary' }}">
                <i data-lucide="user-pen" class="w-6 h-6 text-white"></i>
            </span>

            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em]"
                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('sahaba.edit') }}</p>
                <h1 class="text-xl sm:text-2xl font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $muslim->name }}
                </h1>

                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    <code class="px-2 py-0.5 rounded text-[11px] font-mono" dir="ltr"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">{{ $muslim->slug }}</code>

                    @foreach ($muslim->roles as $role)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                              :class="darkMode ? 'bg-accent/15 text-accent' : 'bg-[#0056b3]/10 text-[#0056b3]'">{{ $role->name() }}</span>
                    @endforeach

                    @if ($muslim->is_published)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                              :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-700'">
                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                            {{ __('sahaba.published') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                              :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                            <i data-lucide="circle-slash" class="w-3 h-3"></i>
                            {{ __('sahaba.unpublished') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('admin.sahaba.show', $muslim) }}"
               class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 border
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                <i data-lucide="eye" class="w-4 h-4"></i>
                <span>{{ __('admin.view') }}</span>
            </a>

            {{-- Only offered when there is a public page to reach: an
                 unpublished profile 404s on the site. --}}
            @if ($muslim->is_published)
                <a href="{{ route('public.sahaba.show', $muslim->slug) }}" target="_blank" rel="noopener"
                   aria-label="{{ __('sahaba.view_public') }}"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 border
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('sahaba.view_public') }}</span>
                </a>
            @endif

            <a href="{{ route('admin.sahaba.index') }}"
               aria-label="{{ __('sahaba.back_to_list') }}"
               class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 border
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                <i data-lucide="arrow-{{ $backArrow }}" class="w-4 h-4"></i>
                <span class="hidden sm:inline">{{ __('sahaba.back_to_list') }}</span>
            </a>
        </div>
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

            {{-- Destructive action, deliberately far from Save and behind a
                 confirm. Placed at the end of the rail, not in the save bar. --}}
            <button type="button"
                    class="mt-3 w-full px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                    :class="darkMode ? 'bg-red-900/25 text-red-400 hover:bg-red-900/40' : 'bg-red-50 text-red-600 hover:bg-red-100'"
                    @click="$dispatch('show-confirm', {
                        title: '{{ __('admin.confirm_delete') }}',
                        message: '{{ __('admin.delete_warning') }}',
                        type: 'danger',
                        onConfirm: () => document.getElementById('delete-muslim-form').submit(),
                    })">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('admin.delete') }}</span>
            </button>

            {{-- Reads the SAVED record, so it answers "where is this person
                 right now", not "where would these switches put them". That is
                 the question an editor opening the page is actually asking. --}}
            <div class="mt-3">
                @include('admin.sahaba._visibility', ['muslim' => $muslim])
            </div>
        </nav>

        {{-- ==================== FORM ==================== --}}
        <div class="lg:col-span-9">
            <form id="muslim-form" action="{{ route('admin.sahaba.update', $muslim) }}" method="POST"
                  @submit="submitting = true">
                @csrf
                @method('PUT')

                @include('admin.sahaba._form', ['muslim' => $muslim, 'roles' => $roles])

                {{-- Sticky save bar. Announces unsaved changes rather than
                     leaving the editor to remember. --}}
                <div class="sticky bottom-4 mt-6 z-20">
                    <div class="rounded-2xl border shadow-xl px-4 py-3 glass flex flex-wrap items-center gap-3"
                         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">

                        <p class="text-sm flex items-center gap-2 me-auto" aria-live="polite">
                            <template x-if="dirty">
                                <span class="flex items-center gap-2 font-semibold"
                                      :class="darkMode ? 'text-amber-400' : 'text-amber-600'">
                                    <span class="w-2 h-2 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ __('sahaba.unsaved_changes') }}
                                </span>
                            </template>
                            <template x-if="! dirty">
                                <span :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                    {{ __('sahaba.no_changes') }}
                                </span>
                            </template>
                        </p>

                        <a href="{{ route('admin.sahaba.show', $muslim) }}"
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

            {{-- Mobile delete, since the rail is desktop-only. --}}
            <button type="button"
                    class="lg:hidden mt-4 w-full px-4 py-3 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                    :class="darkMode ? 'bg-red-900/25 text-red-400 hover:bg-red-900/40' : 'bg-red-50 text-red-600 hover:bg-red-100'"
                    @click="$dispatch('show-confirm', {
                        title: '{{ __('admin.confirm_delete') }}',
                        message: '{{ __('admin.delete_warning') }}',
                        type: 'danger',
                        onConfirm: () => document.getElementById('delete-muslim-form').submit(),
                    })">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('admin.delete') }}</span>
            </button>
        </div>
    </div>
</div>

<form id="delete-muslim-form" action="{{ route('admin.sahaba.destroy', $muslim) }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Outside the form: a picker inside it would submit its own search input. --}}
<x-admin.muslim-picker-modal :roles="$roles" />
@endsection

@include('admin.sahaba._editor-script', ['rail' => $rail])
