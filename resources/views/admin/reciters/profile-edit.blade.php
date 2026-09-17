@extends('layouts.admin')

@php
    $t = fn (string $key, array $replace = []) => __('quran.profiles_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $name = $profile->exists ? $profile->displayName($locale) : $t('create');
    $cardTheme = "darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'";
    $iconBtn = 'p-2 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-accent';
    $iconTheme = "darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'";
    $chevron = in_array($locale, ['ar', 'ur'], true) ? 'chevron-left' : 'chevron-right';
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <nav aria-label="{{ $t('breadcrumb') }}" class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.reciter-profiles.index') }}" class="hover:text-accent transition-colors">{{ __('quran.recordings.profiles') }}</a>
            <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
            <span aria-current="page">{{ $name }}</span>
        </nav>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="{{ $profile->exists ? 'user-pen' : 'user-plus' }}" class="w-7 h-7 text-white" aria-hidden="true"></i>
            </div>
            {{ $profile->exists ? $t('edit').': '.$name : $t('create') }}
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @if($profile->exists && $profile->is_active)
            <a href="{{ route('public.quran.reciter', $profile->slug) }}" target="_blank" rel="noopener"
               class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
               :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                <i data-lucide="external-link" class="w-5 h-5" aria-hidden="true"></i><span>{{ $t('view_public') }}</span>
            </a>
        @endif
        <a href="{{ route('admin.reciter-profiles.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i><span>{{ $t('cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
@include('admin.reciters.partials.profile-form', ['profile' => $profile])

@if($profile->exists)
<section class="mt-6 rounded-2xl border p-6" :class="{{ $cardTheme }}" aria-labelledby="recordings-heading">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h2 id="recordings-heading" class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="disc-3" class="w-5 h-5 text-accent" aria-hidden="true"></i>
            {{ __('quran.recordings.recordings') }}
            <span class="px-2 py-0.5 text-sm rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">{{ $profile->recordings->count() }}</span>
        </h2>
        <a href="{{ route('admin.reciters.create', ['profile_id' => $profile->id]) }}"
           class="self-start px-4 py-2 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>{{ __('quran.recordings.add') }}
        </a>
    </div>

    @if($profile->recordings->isEmpty())
        <p class="text-center py-8 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('no_recordings') }}</p>
    @else
        <ul class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
            @foreach($profile->recordings as $recording)
                @php
                    $isDefault = $recording->id === $profile->default_recording_id;
                    $label = $recording->recordingLabel($locale);
                @endphp
                <li class="py-4 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="relative w-14 h-14 shrink-0 rounded-xl overflow-hidden bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="mic" class="w-6 h-6 text-white/70" aria-hidden="true"></i>
                        @if($recording->profile_image_url ?? $profile->profile_image_url)
                            <img src="{{ $recording->profile_image_url ?? $profile->profile_image_url }}" alt="{{ $recording->getDisplayName($locale) }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.reciters.show', $recording->id) }}" class="font-bold hover:text-accent transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $label }}</a>
                            @if($isDefault)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                    <i data-lucide="check" class="w-3 h-3" aria-hidden="true"></i>{{ $t('default_badge') }}
                                </span>
                            @endif
                            @unless($recording->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700">
                                    <i data-lucide="circle-off" class="w-3 h-3" aria-hidden="true"></i>{{ $t('inactive') }}
                                </span>
                            @endunless
                        </div>
                        <p class="mt-1 text-sm truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $recording->getDisplayName($locale) }} · <span dir="ltr">{{ $recording->slug }}</span>
                        </p>
                        <p class="mt-1 text-xs flex flex-wrap gap-3" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            <span class="inline-flex items-center gap-1"><i data-lucide="book-open" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $t('surahs_with_audio', ['count' => (int) $recording->surahs_with_audio]) }}</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="server" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $t('sources_count', ['count' => $recording->sources_count]) }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1 sm:justify-end">
                        @if(! $isDefault && $recording->is_active && $recording->kind === 'recitation')
                            <form method="POST" action="{{ route('admin.reciter-profiles.recordings.default', [$profile->id, $recording->id]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-semibold border transition-all hover:scale-105 focus:outline-none focus:ring-2 focus:ring-accent"
                                        :class="darkMode ? 'border-gray-700 text-gray-200 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">{{ $t('set_default') }}</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.reciters.show', $recording->id) }}" class="{{ $iconBtn }}" :class="{{ $iconTheme }}" aria-label="{{ $t('view') }}: {{ $label }}" title="{{ $t('view') }}">
                            <i data-lucide="eye" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route('admin.reciters.edit', $recording->id) }}" class="{{ $iconBtn }}" :class="{{ $iconTheme }}" aria-label="{{ $t('edit_recording') }}: {{ $label }}" title="{{ $t('edit_recording') }}">
                            <i data-lucide="edit-2" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route('admin.reciters.sources.index', $recording->id) }}" class="{{ $iconBtn }}" :class="{{ $iconTheme }}" aria-label="{{ __('quran.sources.title') }}: {{ $label }}" title="{{ __('quran.sources.title') }}">
                            <i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        @unless($isDefault)
                            <form method="POST" action="{{ route('admin.reciter-profiles.recordings.detach', [$profile->id, $recording->id]) }}"
                                  @submit.prevent="$dispatch('show-confirm', { title: @js($t('detach_title')), message: @js($t('detach_message', ['label' => $label])), type: 'warning', onConfirm: () => $el.submit() })">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="{{ $iconBtn }} text-red-500 hover:text-red-700" :class="darkMode ? 'hover:bg-red-900/20' : 'hover:bg-red-50'"
                                        aria-label="{{ $t('detach') }}: {{ $label }}" title="{{ $t('detach') }}">
                                    <i data-lucide="unlink" class="w-4 h-4" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <!-- Attach an unassigned recording -->
    <div class="mt-6 pt-6 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'"
         x-data="{ q: '', options: @js($unassigned->map(fn ($r) => ['id' => $r->id, 'text' => $r->getDisplayName($locale).' — '.$r->recordingLabel($locale).' ('.$r->slug.')'])->values()), get filtered() { const q = this.q.toLowerCase(); return q ? this.options.filter(o => o.text.toLowerCase().includes(q)) : this.options; } }">
        <h3 class="font-bold mb-3 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="link-2" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ $t('attach') }}
        </h3>
        @if($unassigned->isEmpty())
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('attach_none') }}</p>
        @else
            <form method="POST" action="{{ route('admin.reciter-profiles.recordings.attach', $profile->id) }}" class="grid grid-cols-1 md:grid-cols-[1fr_2fr_auto] gap-3 items-end">
                @csrf
                <div>
                    <label for="attach_filter" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t('attach_filter') }}</label>
                    <input type="search" id="attach_filter" x-model="q" class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
                <div>
                    <label for="recording_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t('attach_label') }}</label>
                    <select id="recording_id" name="recording_id" required class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <template x-for="option in filtered" :key="option.id">
                            <option :value="option.id" x-text="option.text"></option>
                        </template>
                    </select>
                    @error('recording_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" :disabled="filtered.length === 0"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-accent">
                    <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>{{ $t('attach_submit') }}
                </button>
            </form>
        @endif
    </div>
</section>

{{-- Merge: another reciter's recordings become this reciter's --}}
<section class="mt-6 rounded-2xl border p-6" :class="{{ $cardTheme }}" aria-labelledby="merge-heading"
         x-data="{ q: '', picked: '', options: @js($mergeCandidates->map(fn ($p) => ['id' => $p->id, 'text' => $p->displayName($locale).' ('.$p->slug.') — '.$t('recordings_count', ['count' => $p->recordings_count])])->values()), get filtered() { const q = this.q.toLowerCase(); return q ? this.options.filter(o => o.text.toLowerCase().includes(q)) : this.options; } }">
    <h2 id="merge-heading" class="text-xl font-bold mb-2 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
        <i data-lucide="merge" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ $t('merge') }}
    </h2>
    <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t('merge_hint') }}</p>
    @if($mergeCandidates->isEmpty())
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('merge_none') }}</p>
    @else
        <form method="POST" action="{{ route('admin.reciter-profiles.merge', $profile->id) }}" class="grid grid-cols-1 md:grid-cols-[1fr_2fr_auto] gap-3 items-end"
              @submit.prevent="$dispatch('show-confirm', { title: @js($t('merge_title')), message: @js($t('merge_message', ['name' => $name])), type: 'danger', onConfirm: () => $el.submit() })">
            @csrf
            <div>
                <label for="merge_filter" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t('attach_filter') }}</label>
                <input type="search" id="merge_filter" x-model="q" class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div>
                <label for="source_profile_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t('merge_label') }}</label>
                <select id="source_profile_id" name="source_profile_id" x-model="picked" required class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">—</option>
                    <template x-for="option in filtered" :key="option.id">
                        <option :value="option.id" x-text="option.text"></option>
                    </template>
                </select>
                @error('source_profile_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" :disabled="!picked"
                    class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-accent">
                <i data-lucide="merge" class="w-4 h-4" aria-hidden="true"></i>{{ $t('merge_submit') }}
            </button>
        </form>
    @endif
</section>
@endif
@endsection
