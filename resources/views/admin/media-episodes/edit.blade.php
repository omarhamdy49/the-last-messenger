@extends('layouts.admin')

@section('page-header')
<div class="flex items-center gap-4">
    <a href="{{ route('admin.media-episodes.index', ['program_id' => $episode->program_id]) }}"
       class="p-2 rounded-xl transition-colors"
       :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
    </a>
    <div>
        <h1 class="text-3xl font-bold gradient-text">{{ __('admin.edit_episode') }}</h1>
        <p class="mt-1 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
            — {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
        </p>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.media-episodes.update', $episode->id) }}" enctype="multipart/form-data" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    @method('PUT')

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
        <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Translations -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h3>

                @php
                    $locales = ['ar' => 'العربية', 'en' => 'English'];
                    $existingTranslations = $episode->translations->keyBy('locale');
                @endphp
                <div x-data="{ activeLocale: 'ar' }">
                    <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                        @foreach($locales as $code => $label)
                        <button type="button" @click="activeLocale = '{{ $code }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="activeLocale === '{{ $code }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

                    @foreach($locales as $code => $label)
                    @php $t = $existingTranslations->get($code); @endphp
                    <div x-show="activeLocale === '{{ $code }}'" class="space-y-4" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.title') }} ({{ $label }}) {{ $code === 'ar' ? '*' : '' }}
                            </label>
                            <input type="text" name="title[{{ $code }}]"
                                   value="{{ old('title.'.$code, $t?->title) }}"
                                   class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   {{ $code === 'ar' ? 'required' : '' }}>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.description') }} ({{ $label }})
                            </label>
                            <textarea name="description[{{ $code }}]" rows="3"
                                      class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                      :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('description.'.$code, $t?->description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.transcript') }} ({{ $label }})
                            </label>
                            <textarea name="transcript[{{ $code }}]" rows="8" dir="auto"
                                      placeholder="00:00 ..."
                                      class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                      :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('transcript.'.$code, $t?->transcript) }}</textarea>
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.transcript_hint') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.episode_information') }}</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.season_number') }}
                            </label>
                            <input type="number" name="season_number" value="{{ old('season_number', $episode->season_number) }}" min="1"
                                   class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.episode_number') }} *
                            </label>
                            <input type="number" name="episode_number" value="{{ old('episode_number', $episode->episode_number) }}" min="1" required
                                   class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.slug') }} *
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $episode->slug) }}" required
                               class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.video_url') }}
                        </label>
                        <input type="url" name="video_url" value="{{ old('video_url', $episode->video_url) }}"
                               class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="https://www.youtube.com/embed/...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.duration_seconds') }}
                        </label>
                        <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $episode->duration_seconds) }}" min="0"
                               class="w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>
                    @if($episode->getFirstMedia('thumbnail'))
                    <div class="mb-1">
                        <p class="text-xs mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.current') }}: thumbnail</p>
                        <img src="{{ $episode->getFirstMediaUrl('thumbnail') }}" class="h-16 w-auto rounded-lg object-cover" alt="thumbnail">
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Thumbnail ({{ __('admin.leave_blank_unchanged') }})
                        </label>
                        <input type="file" name="thumbnail" accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="w-full text-base sm:text-sm">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $episode->is_featured) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-[#0056b3]">
                        <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.featured_only') }}</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" :disabled="submitting"
                        class="flex-1 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="!submitting">{{ __('admin.save') }}</span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                        {{ __('admin.loading') }}
                    </span>
                </button>
                <a href="{{ route('admin.media-episodes.show', $episode->id) }}"
                   class="px-4 py-3 rounded-xl font-semibold border transition-colors"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </div>
    </div>
</form>
@endsection
