{{--
    Shared Media Program form (create + edit).

    @param \App\Domain\Media\Models\MediaProgram|null $program  Null on create
    @param \App\Domain\Media\Enums\ProgramCategory[]  $categories
--}}
@php
    $isEdit = $program !== null;
    $locales = enabled_content_languages();
    $existingTranslations = $isEdit ? $program->translations->keyBy('locale') : collect();
    $initialPublishStatus = old('is_published') !== null
        ? (old('is_published') ? 'published' : (old('publish_at') ? 'scheduled' : 'draft'))
        : ($isEdit ? $program->publishStatus() : 'published');
    $inputClasses = 'w-full text-base sm:text-sm border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-shadow';
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.media-programs.update', $program->id) : route('admin.media-programs.store') }}"
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

            {{-- YouTube source ------------------------------------------------
                 Create: analyze a playlist/video, prefill the form, and carry
                 the URL through so store() queues the episode import.
                 Edit:   the program exists, so the import runs right here. --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
                     x-data="{
                        source: @js(old('youtube_url', $isEdit && $program->youtube_playlist_id ? 'https://www.youtube.com/playlist?list='.$program->youtube_playlist_id : '')),
                        picked: null,
                        items: @js(old('youtube_items', [])),
                        init() {
                            window.addEventListener('yt-prefill', (e) => this.applyPrefill(e.detail));
                        },
                        applyPrefill({ url, analysis, items }) {
                            this.source = url;
                            this.picked = analysis;
                            this.items = items || [];

                            const locale = /[؀-ۿ]/.test(analysis.title || '') ? 'ar' : 'en';
                            this.setField(`title[${locale}]`, analysis.title);
                            this.setField(`host[${locale}]`, analysis.channel);
                            // Arabic is the required locale. Seed it from the source
                            // too so saving isn't blocked on a field the admin was
                            // never shown — they translate it in place afterwards.
                            if (locale !== 'ar') {
                                this.setField('title[ar]', analysis.title);
                                this.setField('host[ar]', analysis.channel);
                            }
                            // Confirmed rows, not the raw playlist size.
                            this.setField('total_episodes', this.items.length || analysis.count);
                            // Slug is transliterated server-side from the title.
                            this.setField('slug', analysis.suggested_slug);

                            // Reveal the locale we just filled.
                            this.$root.closest('form')
                                .querySelector(`[data-locale-tab='${locale}']`)?.click();
                        },
                        // A field counts as empty when blank or at its numeric default.
                        setField(name, value) {
                            if (value === undefined || value === null || value === '') return;
                            const el = this.$root.closest('form').querySelector(`[name='${name}']`);
                            if (!el) return;
                            const empty = el.value === '' || (el.type === 'number' && Number(el.value) === 0);
                            if (!empty) return;
                            el.value = value;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        },
                     }">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="youtube" class="w-5 h-5 text-red-600"></i>
                            {{ __('admin.import_from_youtube') }}
                        </h3>
                        <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $isEdit ? __('admin.yt_import_hint') : __('admin.yt_prefill_hint') }}
                        </p>
                    </div>
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('open-yt-import'))"
                            class="flex-shrink-0 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all hover:scale-105 flex items-center gap-2"
                            :class="darkMode ? 'border-gray-700 text-gray-200 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="{{ $isEdit ? 'download' : 'wand-sparkles' }}" class="w-4 h-4"></i>
                        {{ $isEdit ? __('admin.yt_import_now') : __('admin.yt_fetch') }}
                    </button>
                </div>

                @unless($isEdit)
                    <input type="hidden" name="youtube_url" :value="source">
                    {{-- The confirmed mapping travels with the form, so the run
                         queued on save imports exactly what was reviewed. --}}
                    <template x-for="(row, i) in items" :key="row.video_id">
                        <div>
                            <input type="hidden" :name="`youtube_items[${i}][video_id]`" :value="row.video_id">
                            <input type="hidden" :name="`youtube_items[${i}][episode_number]`" :value="row.episode_number">
                            <input type="hidden" :name="`youtube_items[${i}][title]`" :value="row.title">
                        </div>
                    </template>
                @endunless

                {{-- Chosen source summary --}}
                <template x-if="source">
                    <div class="flex items-center gap-3 rounded-xl border px-3 py-2.5"
                         :class="darkMode ? 'border-gray-800 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
                        <i data-lucide="link" class="w-4 h-4 flex-shrink-0" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                        <span class="text-xs font-mono truncate flex-1" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="source"></span>
                        <span x-show="picked" class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 bg-green-100 text-green-700"
                              x-text="@js(__('admin.yt_will_import')).replace(':count', items.length || picked?.count || 0)"></span>
                        @unless($isEdit)
                        <button type="button" @click="source = ''; picked = null; items = []"
                                aria-label="{{ __('admin.remove') }}"
                                class="p-1 rounded-lg flex-shrink-0" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                        @endunless
                    </div>
                </template>
                <p x-show="!source" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    {{ __('admin.yt_no_source') }}
                </p>

                @error('youtube_url')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </section>

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
                                data-locale-tab="{{ $code }}"
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
                                height="200px" />
                            @error("description.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.program_host') }} ({{ $label }})
                            </label>
                            <input type="text" name="host[{{ $code }}]" value="{{ old('host.'.$code, $t?->host) }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error("host.{$code}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Media --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-1 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.featured_image') }}
                </h3>
                @if($isEdit)
                <p class="text-xs mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.leave_blank_unchanged') }}</p>
                @else
                <div class="mb-4"></div>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- poster_url/banner_url are the guarded accessors; raw getFirstMedia* throws on the custom media table --}}
                    <x-admin.image-upload
                        name="poster"
                        idName="poster_id"
                        :label="__('admin.poster')"
                        :currentImage="$isEdit ? $program->poster_url : null"
                        aspectClass="aspect-square"
                        placeholderIcon="image"
                        maxSize="10MB"
                        formats="JPG, PNG, WebP"
                        accept="image/jpeg,image/jpg,image/png,image/webp" />
                    <x-admin.image-upload
                        name="banner"
                        idName="banner_id"
                        :label="__('admin.banner')"
                        :currentImage="$isEdit ? $program->banner_url : null"
                        aspectClass="aspect-video"
                        placeholderIcon="panorama"
                        maxSize="10MB"
                        formats="JPG, PNG, WebP"
                        accept="image/jpeg,image/jpg,image/png,image/webp" />
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
                        // title -> "{program title} | {app name}", description -> stripped program description.
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
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.seo_default_title_hint') }}</p>
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

            {{-- Program details --}}
            <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings-2" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.program_information') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.slug') }} *
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $program?->slug) }}" required dir="ltr"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.url_friendly_hint') }}</p>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.type') }} *
                        </label>
                        <select name="category" required class="{{ $inputClasses }}"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ old('category', $program?->category?->value) === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.maturity_rating') }}
                        </label>
                        <select name="maturity_rating" class="{{ $inputClasses }}"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @foreach(['all' => __('admin.maturity_all'), 'teen' => __('admin.maturity_teen'), 'mature' => __('admin.maturity_mature')] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('maturity_rating', $program?->maturity_rating?->value ?? 'all') === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.program_year') }}
                            </label>
                            <input type="number" name="year" value="{{ old('year', $program?->year) }}" min="1900" max="2100"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('year')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.program_rating') }}
                            </label>
                            <input type="number" name="rating" value="{{ old('rating', $program?->rating ?? 0) }}" min="0" max="10" step="0.1"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('rating')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.total_seasons') }}
                            </label>
                            <input type="number" name="total_seasons" value="{{ old('total_seasons', $program?->total_seasons ?? 1) }}" min="1" max="500"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('total_seasons')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.total_episodes') }}
                            </label>
                            <input type="number" name="total_episodes" value="{{ old('total_episodes', $program?->total_episodes ?? 0) }}" min="0" max="10000"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            @error('total_episodes')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.display_order') }}
                        </label>
                        <input type="number" name="display_order" value="{{ old('display_order', $program?->display_order ?? 0) }}" min="0"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.lower_numbers_first') }}</p>
                        @error('display_order')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-4 pt-2 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1"
                                   {{ old('is_featured', $program?->is_featured) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056b3]">
                            <span class="text-sm flex items-center gap-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-yellow-500"></i>
                                {{ __('admin.featured_only') }}
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_trending" value="1"
                                   {{ old('is_trending', $program?->is_trending) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056b3]">
                            <span class="text-sm flex items-center gap-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-blue-500"></i>
                                {{ __('admin.is_trending') }}
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
                           value="{{ old('publish_at', $program?->publish_at?->format('Y-m-d\TH:i')) }}"
                           :disabled="publishStatus !== 'scheduled'"
                           :required="publishStatus === 'scheduled'"
                           class="{{ $inputClasses }}"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.publish_schedule_hint') }}</p>
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
                <a href="{{ $isEdit ? route('admin.media-programs.show', $program->id) : route('admin.media-programs.index') }}"
                   class="px-6 py-3 rounded-xl font-semibold border transition-colors"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </aside>
    </div>
</form>

{{-- YouTube import modal. Kept outside <form> so its buttons can never be
     mistaken for form controls. On create it only prefills; on edit the
     program exists, so it queues a real import. --}}
@include('admin.media-programs._youtube-import', [
    'mode' => $isEdit ? 'import' : 'prefill',
    'syncRoute' => $isEdit ? route('admin.media-programs.youtube.sync', $program->id) : null,
    'prefill' => old('youtube_url', $isEdit && $program->youtube_playlist_id
        ? 'https://www.youtube.com/playlist?list='.$program->youtube_playlist_id
        : ''),
    'resumeRun' => null,
])

{{-- Media library picker (used by the image-upload components) --}}
<x-admin.media-picker-modal />
