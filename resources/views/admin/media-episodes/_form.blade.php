{{--
    Shared Media Episode form (create + edit).

    @param \App\Domain\Media\Models\MediaEpisode|null $episode   Null on create
    @param \Illuminate\Support\Collection              $programs  Program options
    @param int|null                                    $programId Preselected program (create only)
--}}
@php
    $isEdit = $episode !== null;
    $locales = ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'];
    $existingTranslations = $isEdit ? $episode->translations->keyBy('locale') : collect();
    $initialPublishStatus = old('is_published') !== null
        ? (old('is_published') ? 'published' : (old('publish_at') ? 'scheduled' : 'draft'))
        : ($isEdit ? $episode->publishStatus() : 'published');
    $inputClasses = 'w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-shadow';
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.media-episodes.update', $episode->id) : route('admin.media-episodes.store') }}"
      enctype="multipart/form-data"
      x-data="{ submitting: false }"
      @submit="submitting = true">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl border-s-4 border-red-500 animate-fadeInUp"
         :class="darkMode ? 'bg-red-900/20' : 'bg-red-50'">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <h3 class="font-bold text-red-500 mb-2">{{ __('common.please_fix_errors') }}</h3>
                <ul class="list-disc list-inside space-y-1 text-sm" :class="darkMode ? 'text-red-400' : 'text-red-600'">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ==================== MAIN COLUMN ==================== --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Translations --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.translations') }}
                </h3>

                <div x-data="{ activeLocale: 'ar' }">
                    <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                        @foreach($locales as $code => $label)
                        @php $hasTitle = filled(old("title.{$code}", $existingTranslations->get($code)?->title)); @endphp
                        <button type="button" @click="activeLocale = '{{ $code }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
                                :class="activeLocale === '{{ $code }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                            {{ $label }}
                            {{-- Completion dot: green when this locale already has a title --}}
                            <span class="w-1.5 h-1.5 rounded-full {{ $hasTitle ? 'bg-green-400' : 'bg-gray-400/60' }}"></span>
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
                            <input type="text" name="title[{{ $code }}]" value="{{ old('title.'.$code, $t?->title) }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   {{ $code === 'ar' ? 'required' : '' }}>
                            @error("title.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.description') }} ({{ $label }})
                            </label>
                            <x-admin.form.wysiwyg
                                name="description[{{ $code }}]"
                                :value="old('description.'.$code, $t?->description)"
                                dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}"
                                height="180px" />
                            @error("description.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.transcript') }} ({{ $label }})
                            </label>
                            <x-admin.form.wysiwyg
                                name="transcript[{{ $code }}]"
                                :value="old('transcript.'.$code, $t?->transcript)"
                                dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}"
                                height="220px" />
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.transcript_hint') }}</p>
                            @error("transcript.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- SEO --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="search-check" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.seo_settings') }}
                </h3>
                <p class="text-xs mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.seo_settings_desc') }}</p>

                <div x-data="{ activeSeoLocale: 'ar' }">
                    <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                        @foreach($locales as $code => $label)
                        @php $hasSeo = filled(old("seo_title.{$code}", $existingTranslations->get($code)?->seo_title)); @endphp
                        <button type="button" @click="activeSeoLocale = '{{ $code }}'"
                                class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
                                :class="activeSeoLocale === '{{ $code }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                            {{ $label }}
                            <span class="w-1.5 h-1.5 rounded-full {{ $hasSeo ? 'bg-green-400' : 'bg-gray-400/60' }}"></span>
                        </button>
                        @endforeach
                    </div>

                    @foreach($locales as $code => $label)
                    @php
                        $t = $existingTranslations->get($code);
                        // Default values when no explicit SEO is stored yet:
                        // title -> "{episode title} | {app name}", description -> stripped episode description.
                        $localeTitle = old('title.'.$code, $t?->title);
                        $seoTitleValue = old('seo_title.'.$code)
                            ?? ($t?->seo_title ?: ($localeTitle ? $localeTitle.' | '.config('app.name') : ''));
                        $seoDescValue = old('seo_description.'.$code)
                            ?? ($t?->seo_description ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) $t?->description)), 160, ''));
                    @endphp
                    <div x-show="activeSeoLocale === '{{ $code }}'" class="space-y-4" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                        <div x-data="{ seoTitleLength: {{ mb_strlen($seoTitleValue ?? '') }} }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('admin.meta_title') }} ({{ $label }})
                                </label>
                                <span class="text-xs" :class="seoTitleLength > 60 ? 'text-amber-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')"
                                      x-text="seoTitleLength + ' / 60'"></span>
                            </div>
                            <input type="text" name="seo_title[{{ $code }}]" maxlength="255"
                                   value="{{ $seoTitleValue }}"
                                   @input="seoTitleLength = $event.target.value.length"
                                   placeholder="{{ __('admin.seo_title_placeholder') }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.episode_seo_default_title_hint') }}</p>
                            @error("seo_title.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{ seoDescLength: {{ mb_strlen($seoDescValue ?? '') }} }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('admin.meta_description') }} ({{ $label }})
                                </label>
                                <span class="text-xs" :class="seoDescLength > 160 ? 'text-amber-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')"
                                      x-text="seoDescLength + ' / 160'"></span>
                            </div>
                            <textarea name="seo_description[{{ $code }}]" rows="2" maxlength="500"
                                      @input="seoDescLength = $event.target.value.length"
                                      placeholder="{{ __('admin.seo_desc_placeholder') }}"
                                      class="{{ $inputClasses }}"
                                      :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">{{ $seoDescValue }}</textarea>
                            @error("seo_description.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.seo_keywords') }} ({{ $label }})
                            </label>
                            <input type="text" name="seo_keywords[{{ $code }}]" maxlength="255"
                                   value="{{ old('seo_keywords.'.$code, $t?->seo_keywords) }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error("seo_keywords.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ==================== SIDEBAR ==================== --}}
        <aside class="space-y-6">

            {{-- Thumbnail --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.thumbnail') }}
                </h3>
                @if($isEdit)
                <p class="text-xs mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.leave_blank_unchanged') }}</p>
                @else
                <div class="mb-4"></div>
                @endif
                {{-- thumb_url is the guarded accessor; raw getFirstMedia* throws on the custom media table --}}
                <x-admin.image-upload
                    name="thumbnail"
                    idName="thumbnail_id"
                    :label="__('admin.thumbnail')"
                    :currentImage="$isEdit ? $episode->thumb_url : null"
                    aspectClass="aspect-video"
                    placeholderIcon="image"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                    accept="image/jpeg,image/jpg,image/png,image/webp" />
            </section>

            {{-- Episode details --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings-2" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.episode_information') }}
                </h3>

                <div class="space-y-4">
                    @if(! $isEdit)
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.program') }} *
                        </label>
                        <select name="program_id" required class="{{ $inputClasses }}"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.select_program') }}</option>
                            @foreach($programs as $prog)
                            <option value="{{ $prog->id }}" {{ (old('program_id', $programId) == $prog->id) ? 'selected' : '' }}>
                                {{ $prog->getTranslation('title', 'ar') ?? $prog->slug }}
                            </option>
                            @endforeach
                        </select>
                        @error('program_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    @else
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.program') }}</span>
                        <a href="{{ route('admin.media-programs.show', $episode->program_id) }}"
                           class="text-sm font-semibold text-[#0056b3] hover:underline truncate max-w-[160px]"
                           :class="darkMode ? '!text-[#64CCC5]' : ''">
                            {{ $episode->program?->getTranslation('title', 'ar') ?? $episode->program?->slug }}
                        </a>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.season_number') }}
                            </label>
                            <input type="number" name="season_number" value="{{ old('season_number', $episode?->season_number ?? 1) }}" min="1"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('season_number')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.episode_number') }} *
                            </label>
                            <input type="number" name="episode_number" value="{{ old('episode_number', $episode?->episode_number) }}" min="1" required
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('episode_number')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.slug') }} {{ $isEdit ? '*' : '' }}
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $episode?->slug) }}" {{ $isEdit ? 'required' : '' }} dir="ltr"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $isEdit ? __('admin.url_friendly_hint') : __('admin.episode_slug_auto_hint') }}
                        </p>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.video_url') }}
                        </label>
                        <input type="url" name="video_url" value="{{ old('video_url', $episode?->video_url) }}" dir="ltr"
                               placeholder="https://www.youtube.com/embed/..."
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        @error('video_url')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    @php $durationTotal = (int) old('duration_seconds', $episode?->duration_seconds ?? 0); @endphp
                    <div x-data="{
                            h: {{ intdiv($durationTotal, 3600) }},
                            m: {{ intdiv($durationTotal % 3600, 60) }},
                            s: {{ $durationTotal % 60 }},
                            clamp(v, max) { v = Math.max(0, Math.floor(Number(v) || 0)); return Math.min(v, max); },
                            total() { return this.clamp(this.h, 99) * 3600 + this.clamp(this.m, 59) * 60 + this.clamp(this.s, 59); },
                         }">
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.duration') }} ({{ __('admin.duration_hms') }})
                        </label>
                        <div class="flex items-center gap-1.5" dir="ltr">
                            <input type="number" x-model="h" @blur="h = clamp(h, 99)" min="0" max="99" placeholder="00"
                                   aria-label="{{ __('admin.duration_hours') }}"
                                   class="{{ $inputClasses }} text-center tabular-nums"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <span class="font-bold" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">:</span>
                            <input type="number" x-model="m" @blur="m = clamp(m, 59)" min="0" max="59" placeholder="00"
                                   aria-label="{{ __('admin.duration_minutes') }}"
                                   class="{{ $inputClasses }} text-center tabular-nums"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <span class="font-bold" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">:</span>
                            <input type="number" x-model="s" @blur="s = clamp(s, 59)" min="0" max="59" placeholder="00"
                                   aria-label="{{ __('admin.duration_secs') }}"
                                   class="{{ $inputClasses }} text-center tabular-nums"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                        <input type="hidden" name="duration_seconds" :value="total()">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ __('admin.duration_saved_as_seconds') }} (<span dir="ltr" x-text="total()"></span>)
                        </p>
                        @error('duration_seconds')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1"
                                   {{ old('is_featured', $episode?->is_featured) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056b3]">
                            <span class="text-sm flex items-center gap-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-yellow-500"></i>
                                {{ __('admin.featured_only') }}
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            {{-- Publish --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
                     x-data="{ publishStatus: '{{ $initialPublishStatus }}' }">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="send" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.publish_status') }}
                </h3>

                <input type="hidden" name="is_published" :value="publishStatus === 'published' ? 1 : 0">

                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg transition-colors"
                           :class="publishStatus === 'published' ? (darkMode ? 'bg-green-900/30' : 'bg-green-50') : ''">
                        <input type="radio" value="published" x-model="publishStatus" class="w-4 h-4 text-[#218838]">
                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.publish_now') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg transition-colors"
                           :class="publishStatus === 'scheduled' ? (darkMode ? 'bg-amber-900/30' : 'bg-amber-50') : ''">
                        <input type="radio" value="scheduled" x-model="publishStatus" class="w-4 h-4 text-[#0056b3]">
                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.schedule_for_later') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg transition-colors"
                           :class="publishStatus === 'draft' ? (darkMode ? 'bg-gray-800' : 'bg-gray-100') : ''">
                        <input type="radio" value="draft" x-model="publishStatus" class="w-4 h-4">
                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.draft') }}</span>
                    </label>
                </div>

                <div x-show="publishStatus === 'scheduled'" x-cloak class="mt-4">
                    <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('admin.publish_at') }} *
                    </label>
                    <input type="datetime-local" name="publish_at"
                           value="{{ old('publish_at', $episode?->publish_at?->format('Y-m-d\TH:i')) }}"
                           :disabled="publishStatus !== 'scheduled'"
                           :required="publishStatus === 'scheduled'"
                           class="{{ $inputClasses }}"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.episode_publish_schedule_hint') }}</p>
                    @error('publish_at')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3">
                <button type="submit" :disabled="submitting"
                        class="flex-1 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('admin.save') }}
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                        {{ __('admin.loading') }}
                    </span>
                </button>
                <a href="{{ $isEdit ? route('admin.media-episodes.show', $episode->id) : route('admin.media-episodes.index', $programId ? ['program_id' => $programId] : []) }}"
                   class="px-6 py-3 rounded-xl font-semibold border transition-colors"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </aside>
    </div>
</form>

{{-- Media library picker (used by the image-upload component) --}}
<x-admin.media-picker-modal />
