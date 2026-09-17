@extends('layouts.admin')

@php
    $t = fn (string $key, array $replace = []) => __('quran.profiles_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputTheme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $labelTheme = "darkMode ? 'text-gray-300' : 'text-gray-700'";
    $cardTheme = "darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'";
    $name = $reciter->getDisplayName($locale);
    $label = $reciter->recordingLabel($locale);
    $chevron = in_array($locale, ['ar', 'ur'], true) ? 'chevron-left' : 'chevron-right';
    $providers = array_keys(config('quran-audio.providers'));
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <nav aria-label="{{ $t('breadcrumb') }}" class="flex flex-wrap items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            @if($reciter->profile)
                <a href="{{ route('admin.reciter-profiles.index') }}" class="hover:text-accent transition-colors">{{ __('quran.recordings.profiles') }}</a>
                <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
                <a href="{{ route('admin.reciter-profiles.edit', $reciter->profile->id) }}" class="hover:text-accent transition-colors">{{ $reciter->profile->displayName($locale) }}</a>
            @else
                <a href="{{ route('admin.reciters.index', ['recordings' => 1]) }}" class="hover:text-accent transition-colors">{{ __('quran.recordings.recordings') }}</a>
            @endif
            <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
            <a href="{{ route('admin.reciters.show', $reciter->id) }}" class="hover:text-accent transition-colors">{{ $label }}</a>
            <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
            <span aria-current="page">{{ __('quran.sources.title') }}</span>
        </nav>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="server" class="w-7 h-7 text-white" aria-hidden="true"></i>
            </div>
            {{ __('quran.sources.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $name }} · {{ $label }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.reciters.show', $reciter->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="disc-3" class="w-5 h-5" aria-hidden="true"></i><span>{{ $t('back_to_recording') }}</span>
        </a>
        <button type="button" x-data @click="$dispatch('toggle-add-source')"
                class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white focus:outline-none focus:ring-2 focus:ring-accent">
            <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i><span>{{ __('quran.sources.add') }}</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-4" x-data="{ adding: @js($errors->any() && ! old('_source_id')), open: @js((int) old('_source_id')) }" @toggle-add-source.window="adding = !adding; if (adding) $nextTick(() => document.getElementById('provider_new')?.focus())">

    @if($errors->any())
        <div role="alert" class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5" aria-hidden="true"></i>{{ $errors->first() }}
        </div>
    @endif

    @foreach($sources->concat([null]) as $source)
        @php($uid = $source?->id ?? 'new')
        <section class="rounded-2xl border overflow-hidden" :class="{{ $cardTheme }}"
                 @if(! $source) x-show="adding" x-collapse x-cloak @endif>
            @if($source)
                <div class="flex flex-col md:flex-row md:items-center gap-3 p-5">
                    <button type="button" @click="open = open === {{ $source->id }} ? 0 : {{ $source->id }}"
                            :aria-expanded="(open === {{ $source->id }}).toString()" aria-controls="source-body-{{ $uid }}"
                            class="flex-1 flex items-center gap-3 text-start rounded-xl focus:outline-none focus:ring-2 focus:ring-accent">
                        <span class="p-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow">
                            <i data-lucide="{{ $source->kind === 'ayah' ? 'list-ordered' : 'book-open' }}" class="w-5 h-5 text-white" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <span dir="ltr">{{ $source->provider }}</span> · {{ __('quran.sources.'.$source->kind) }}
                                <span class="text-sm font-normal" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" dir="ltr">#{{ $source->id }} {{ $source->source_key }}</span>
                            </span>
                            <span class="mt-1 flex flex-wrap gap-1.5 text-xs">
                                <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">{{ $t('priority_value', ['value' => $source->priority]) }}</span>
                                <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-blue-900/30 text-blue-300' : 'bg-blue-50 text-blue-700'">{{ $t('recitations_count', ['count' => $source->recitations_count]) }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-semibold {{ $source->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                    <i data-lucide="{{ $source->is_active ? 'circle-check' : 'circle-off' }}" class="w-3 h-3" aria-hidden="true"></i>{{ $source->is_active ? $t('active') : $t('inactive') }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">{{ $t('revision', ['value' => $source->revision]) }}</span>
                            </span>
                        </span>
                        <i data-lucide="chevron-down" class="w-5 h-5 ms-auto transition-transform shrink-0" :class="open === {{ $source->id }} ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>
                    <form method="POST" action="{{ route('admin.reciters.sources.destroy', [$reciter->id, $source->id]) }}" class="self-end md:self-auto"
                          @submit.prevent="{{ $source->recitations_count > 0
                              ? 'window.showToast('.\Illuminate\Support\Js::from($t('source_delete_blocked', ['count' => $source->recitations_count])).', \'error\')'
                              : '$dispatch(\'show-confirm\', { title: '.\Illuminate\Support\Js::from($t('delete_source_title')).', message: '.\Illuminate\Support\Js::from($t('delete_source_message')).', type: \'danger\', onConfirm: () => $el.submit() })' }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-lg transition-all text-red-500 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-accent" :class="darkMode ? 'hover:bg-red-900/20' : 'hover:bg-red-50'"
                                aria-label="{{ $t('delete_source') }} #{{ $source->id }}" title="{{ $t('delete_source') }}">
                            <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @endif

            <div id="source-body-{{ $uid }}" @if($source) x-show="open === {{ $source->id }}" x-collapse x-cloak @endif
                 class="{{ $source ? 'border-t' : '' }}" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <form method="POST" class="p-6"
                      action="{{ $source ? route('admin.reciters.sources.update', [$reciter->id, $source->id]) : route('admin.reciters.sources.store', $reciter->id) }}">
                    @csrf
                    @if($source) @method('PUT') <input type="hidden" name="_source_id" value="{{ $source->id }}"> @endif

                    @unless($source)
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="plus-circle" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('quran.sources.add') }}
                        </h2>
                    @endunless

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="provider_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.provider') }}</label>
                            <select id="provider_{{ $uid }}" name="provider" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                                @foreach($providers as $provider)
                                    <option value="{{ $provider }}" @selected(($source?->provider ?? 'direct') === $provider)>{{ $provider }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="kind_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.kind') }}</label>
                            <select id="kind_{{ $uid }}" name="kind" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                                <option value="surah" @selected($source?->kind !== 'ayah')>{{ __('quran.sources.surah') }}</option>
                                <option value="ayah" @selected($source?->kind === 'ayah')>{{ __('quran.sources.ayah') }}</option>
                            </select>
                        </div>
                        @foreach(['source_key', 'base_url', 'external_reciter_id', 'external_recording_id', 'external_timing_id', 'priority'] as $field)
                            <div>
                                <label for="{{ $field }}_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">
                                    {{ __('quran.sources.'.$field) }} @if(in_array($field, ['source_key', 'priority'], true))<span class="text-red-500" aria-hidden="true">*</span>@endif
                                </label>
                                <input id="{{ $field }}_{{ $uid }}" name="{{ $field }}" dir="ltr" type="{{ $field === 'priority' ? 'number' : 'text' }}"
                                       value="{{ $source?->{$field} ?? ($field === 'priority' ? 0 : '') }}" @required(in_array($field, ['source_key', 'priority'], true))
                                       class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                            </div>
                        @endforeach
                        <div>
                            <label for="is_active_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.active') }}</label>
                            <select id="is_active_{{ $uid }}" name="is_active" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                                <option value="1" @selected($source?->is_active !== false)>{{ __('quran.sources.yes') }}</option>
                                <option value="0" @selected($source?->is_active === false)>{{ __('quran.sources.no') }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="metadata_json_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.metadata') }}</label>
                            <textarea id="metadata_json_{{ $uid }}" name="metadata_json" rows="3" dir="ltr"
                                      class="{{ $inputClass }} font-mono text-sm" :class="{{ $inputTheme }}">{{ json_encode($source?->metadata ?? (object) [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        @unless($source)
                            <button type="button" @click="adding = false" class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
                                    :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">{{ $t('cancel') }}</button>
                        @endunless
                        <button type="submit" class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white focus:outline-none focus:ring-2 focus:ring-accent">
                            <i data-lucide="save" class="w-5 h-5" aria-hidden="true"></i><span>{{ __('quran.sources.save') }}</span>
                        </button>
                    </div>
                </form>

                @if($source?->kind === 'surah' && filled($source->base_url) && $source->recitations_count < 114)
                    <form method="POST" action="{{ route('admin.reciters.sources.fill', [$reciter->id, $source->id]) }}" x-data="{ busy: false }" @submit="busy = true"
                          class="mx-6 mb-6 p-5 rounded-xl border border-dashed grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_auto] gap-3 items-end" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                        @csrf
                        <div>
                            <p class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <i data-lucide="list-plus" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ __('quran.sources.fill_title') }}
                                <span class="text-xs font-normal px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">{{ $source->recitations_count }} / 114</span>
                            </p>
                            <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.sources.fill_hint') }}</p>
                        </div>
                        <div>
                            <label for="naming_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.fill_naming') }}</label>
                            <select id="naming_{{ $uid }}" name="naming" dir="ltr" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                                <option value="plain">{{ rtrim($source->base_url, '/') }}/1.mp3</option>
                                <option value="padded">{{ rtrim($source->base_url, '/') }}/001.mp3</option>
                            </select>
                        </div>
                        <div>
                            <label for="format_{{ $uid }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ __('quran.sources.fill_format') }}</label>
                            <select id="format_{{ $uid }}" name="format" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                                @foreach(['mp3', 'aac', 'opus'] as $format)<option value="{{ $format }}">{{ strtoupper($format) }}</option>@endforeach
                            </select>
                        </div>
                        <button type="submit" :disabled="busy" class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-accent">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="busy" aria-hidden="true"></i>
                            <span x-text="busy ? @js(__('quran.sources.fill_running')) : @js(__('quran.sources.fill_submit'))"></span>
                        </button>
                    </form>
                @endif
            </div>
        </section>
    @endforeach

    @if($sources->isEmpty())
        <div x-show="!adding" class="text-center py-12 px-6 rounded-2xl border" :class="{{ $cardTheme }}">
            <i data-lucide="server-off" class="w-12 h-12 mx-auto mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-400'" aria-hidden="true"></i>
            <p class="font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t('no_sources') }}</p>
            <button type="button" @click="adding = true" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>{{ __('quran.sources.add') }}
            </button>
        </div>
    @endif
</div>
@endsection
