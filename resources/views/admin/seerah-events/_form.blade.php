@php
    /**
     * Seerah event form (create + edit) as a clickable-step wizard.
     *
     * Every step is a <x-admin.wizard.panel>; all field names are unchanged, so the normal
     * submit, old() and @error keep working. Quick save (adminWizard in resources/js/admin/wizard.js)
     * posts the same form over fetch and stays on the page.
     *
     * @var \App\Domain\Seerah\Models\SeerahEvent|null $event
     */
    $event = $event ?? null;
    $galleryItems = $galleryItems ?? [];
    $mediaItems = $mediaItems ?? [];
    $episodeItems = $episodeItems ?? [];
    $linkItems = $linkItems ?? [];
    $linkOptions = $linkOptions ?? ['tags' => [], 'categories' => []];
    $locales = enabled_content_locales();
    $languages = enabled_content_languages();

    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all';
    $inputDark = "darkMode ? 'bg-gray-900/60 border-gray-700 text-white' : 'bg-white border-slate-300 text-slate-900'";
    $label = 'wz-label';
    $help = 'wz-help';
    $dirFor = fn (string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';

    $oldStatistics = old('statistics');
    if ($oldStatistics === null && is_array($event?->statistics)) {
        $oldStatistics = collect($event->statistics)
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v, $k) => ['label' => (string) $k, 'value' => (string) $v])
            ->values()
            ->all();
    }

    // Exactly this locale's row — no Arabic fallback, or saving an untranslated
    // locale would copy the Arabic rows into it.
    $localeRow = fn (string $lang) => $event?->translations->firstWhere('locale', $lang);
    $localeRows = fn (string $field, string $lang): array => old("{$field}.{$lang}") ?? ($localeRow($lang)?->{$field} ?? []);
    $seoValue = fn (string $lang, string $key): ?string => old("seo.{$lang}.{$key}", $localeRow($lang)?->seo_metadata[$key] ?? null);

    $hijriMonths = \App\Utils\DateUtil::getHijriMonthNames();

    // Steps: key => [icon, error-key prefixes that belong to the step].
    $stepDefs = [
        'basics' => ['file-text', ['title', 'description', 'dispute_note', 'slug', 'status', 'event_type', 'sm_id', 'order', 'era']],
        'timeline' => ['calendar-clock', ['hijri_day', 'hijri_month', 'hijri_year', 'gregorian_date', 'location_id']],
        'content' => ['layers', ['content']],
        'media' => ['images', ['thumb', 'thumb_id', 'cover', 'cover_id', 'gallery', 'gallery_captions', 'media_items', 'links.episode']],
        'links' => ['network', ['links', 'external_links']],
        'seo' => ['search', ['seo']],
        'details' => ['bar-chart-3', ['statistics', 'metadata', 'sources']],
        'review' => ['badge-check', []],
    ];
    $steps = collect($stepDefs)->map(fn (array $def, string $key): array => [
        $def[0], __("wizard.seerah.steps.{$key}.label"), __("wizard.seerah.steps.{$key}.hint"),
    ])->all();
    $stepKeys = array_keys($stepDefs);
    $totalSteps = count($stepKeys);
    $stepNumber = fn (string $key): int => array_search($key, $stepKeys, true) + 1;

    $matches = fn (string $errorKey, array $prefixes): bool => collect($prefixes)
        ->contains(fn (string $p): bool => $errorKey === $p || str_starts_with($errorKey, $p.'.'));
    $serverStepErrors = collect($stepDefs)
        ->map(fn (array $def): int => collect($errors->keys())->filter(fn (string $k): bool => $matches($k, $def[1]))->count())
        ->filter()
        ->all();
    $firstErrorStep = array_key_first($serverStepErrors);

    $linkTypes = [
        'verse' => ['book-open', __('seerah.admin.link_verses'), 'verse'],
        'hadith' => ['quote', __('seerah.admin.link_hadiths'), 'hadith'],
        'muslim' => ['users', __('seerah.admin.link_companions'), 'sahabi'],
        'article' => ['newspaper', __('seerah.admin.link_articles'), 'article'],
    ];

    $wizardMessages = collect(['saving', 'saved', 'unsaved', 'no_changes', 'save_failed', 'fix_errors', 'session_expired', 'network_error', 'saved_toast'])
        ->mapWithKeys(fn (string $k): array => [$k => __("wizard.{$k}")])->all();
    $publicUrl = $event && $event->status?->value === 'published' ? route('public.seerah.event', $event->slug) : null;
@endphp

<div x-data="seerahEventForm({{ Js::from([
        'step' => $firstErrorStep,
        'steps' => $stepKeys,
        'stepErrors' => $serverStepErrors,
        'isEdit' => (bool) $event,
        'updateUrl' => route('admin.seerah-events.update', '__ID__'),
        'editUrl' => route('admin.seerah-events.edit', '__ID__'),
        'messages' => $wizardMessages,
        'gallery' => $galleryItems,
        'media' => $mediaItems,
        'episodes' => $episodeItems,
        'links' => $linkItems,
        'locales' => $locales,
        'tagStoreUrl' => route('admin.tags.store'),
        'categoryStoreUrl' => route('admin.categories.store'),
    ]) }})"
     @media-selected.window="onMediaSelected($event.detail)"
     @episodes-selected.window="addEpisodes($event.detail)"
     @module-block-add.window="onModuleBlock($event.detail)"
     @articles-selected.window="addArticles($event.detail)"
     class="wz-root">

    <x-admin.wizard.hero :eyebrow="__('wizard.seerah.eyebrow')" icon="landmark" title-expr="titles.ar || titles[lang]" :steps="$steps">
        <x-slot:meta>
            <span class="wz-pill" :class="summary.status === 'published' ? 'is-success' : 'is-warn'">
                <span class="wz-dot" aria-hidden="true"></span><span x-text="summary.statusLabel">{{ $event?->status?->labelArabic() ?? '—' }}</span>
            </span>
            <span class="wz-pill" x-show="summary.type" x-cloak><i data-lucide="tag" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="summary.type"></span></span>
            <span class="wz-pill" x-show="summary.date" x-cloak><i data-lucide="calendar" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="summary.date"></span></span>
            @if($event)
                <span class="wz-pill"><i data-lucide="history" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $event->updated_at?->diffForHumans() }}</span>
            @endif
        </x-slot:meta>
    </x-admin.wizard.hero>

    <x-admin.wizard.errors />

    <div class="wz-layout">
        <div class="min-w-0">

            {{-- ═══════ 1. BASICS ═══════ --}}
            <x-admin.wizard.panel key="basics" :index="1" :total="$totalSteps" icon="file-text"
                :title="__('wizard.seerah.steps.basics.label')" :description="__('wizard.seerah.steps.basics.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>

                <div class="wz-section">
                    @foreach($locales as $lang)
                        <div x-show="lang === '{{ $lang }}'" class="grid gap-5">
                            <div>
                                <label class="{{ $label }}" for="title-{{ $lang }}">
                                    العنوان ({{ strtoupper($lang) }}) @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                                </label>
                                <input type="text" id="title-{{ $lang }}" name="title[{{ $lang }}]" maxlength="500"
                                       value="{{ old("title.{$lang}", $event?->getTranslation('title', $lang, false)) }}"
                                       class="{{ $inputClass }} text-base font-semibold" :class="{{ $inputDark }}" dir="{{ $dirFor($lang) }}"
                                       x-init="titles['{{ $lang }}'] = $el.value"
                                       @input="titles['{{ $lang }}'] = $el.value"
                                       placeholder="أدخل عنوان الحدث..." {{ $lang === 'ar' ? 'required' : '' }}>
                                @error("title.{$lang}") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                            </div>

                            <div class="wysiwyg-field">
                                <label class="{{ $label }}">الوصف المختصر ({{ strtoupper($lang) }})</label>
                                <x-admin.form.wysiwyg :name="'description['.$lang.']'"
                                    :value="old('description.'.$lang, $event?->getTranslation('description', $lang, false) ?? '')"
                                    :locale="$lang" height="160px" toolbar="minimal" placeholder="أدخل وصف الحدث..." />
                                <p class="{{ $help }}">يظهر على البطاقات، ويُستخدم وصفاً لمحركات البحث إن لم تُملأ خطوة «محركات البحث».</p>
                                @error("description.{$lang}") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="{{ $label }}" for="dispute-{{ $lang }}">تنبيه الخلاف في التأريخ ({{ strtoupper($lang) }})</label>
                                <textarea id="dispute-{{ $lang }}" name="dispute_note[{{ $lang }}]" rows="3" maxlength="1000"
                                          class="{{ $inputClass }} resize-y" :class="{{ $inputDark }}" dir="{{ $dirFor($lang) }}"
                                          placeholder="مثال: موافقة عام الفيل لسنة 570/571م هي المشهور، وقد رجّح باحثون سنة 571م.">{{ old("dispute_note.{$lang}", $event?->getTranslation('dispute_note', $lang, false)) }}</textarea>
                                <p class="{{ $help }}">اتركه فارغاً إن لم يكن في تأريخ الحدث خلاف.</p>
                                @error("dispute_note.{$lang}") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="settings-2" aria-hidden="true"></i>{{ __('wizard.seerah.sections.classification') }}</h3>
                    <div class="wz-grid-2">
                        <div>
                            <label class="{{ $label }}" for="event_type">نوع الحدث <span class="text-red-500">*</span></label>
                            <select id="event_type" name="event_type" required class="{{ $inputClass }}" :class="{{ $inputDark }}">
                                <option value="">اختر النوع</option>
                                @foreach($eventTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('event_type', $event?->event_type) === (string) $type->id)>{{ $type->name('ar') }}</option>
                                @endforeach
                            </select>
                            @error('event_type') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="sm_id">{{ __('admin.milestone') }}</label>
                            <select id="sm_id" name="sm_id" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                                <option value="">{{ __('admin.no_milestone') }}</option>
                                @foreach($milestones as $milestone)
                                    <option value="{{ $milestone->id }}" @selected((string) old('sm_id', $event?->sm_id) === (string) $milestone->id)>
                                        {{ $milestone->order }}. {{ $milestone->getTranslation('title', 'ar') }}@if($milestone->gregorianRangeLabel()) ({{ $milestone->gregorianRangeLabel() }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('sm_id') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="era">الحقبة التاريخية</label>
                            <select id="era" name="era" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                                <option value="">—</option>
                                @foreach($eras as $era)
                                    <option value="{{ $era->value }}" @selected(old('era', $event?->era?->value) === $era->value)>{{ $era->labelArabic() }}</option>
                                @endforeach
                            </select>
                            @error('era') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="order">الترتيب <span class="text-red-500">*</span></label>
                            <input type="number" id="order" name="order" min="0" required value="{{ old('order', $event?->order ?? 0) }}"
                                   class="{{ $inputClass }} tabular-nums" :class="{{ $inputDark }}">
                            <p class="{{ $help }}">ترتيب الحدث داخل حقبته؛ يتقدّم على التاريخ عند التساوي.</p>
                            @error('order') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="status">{{ __('admin.status') }} <span class="text-red-500">*</span></label>
                            <select id="status" name="status" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $event?->status?->value ?? 'draft') === $status->value)>{{ $status->labelArabic() }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="slug">الرابط المختصر (Slug)</label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $event?->slug) }}"
                                   class="{{ $inputClass }} font-mono text-sm" :class="{{ $inputDark }}" pattern="[a-z0-9\-]+" dir="ltr">
                            <p class="{{ $help }}">{{ __('admin.live_channel_slug_auto_hint') }} {{ __('admin.slug_format') }}</p>
                            @error('slug') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 2. TIME & PLACE ═══════ --}}
            <x-admin.wizard.panel key="timeline" :index="2" :total="$totalSteps" icon="calendar-clock"
                :title="__('wizard.seerah.steps.timeline.label')" :description="__('wizard.seerah.steps.timeline.desc')">
                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="moon" aria-hidden="true"></i>{{ __('wizard.seerah.sections.hijri') }}</h3>
                    <div class="wz-grid-3">
                        <div>
                            <label class="{{ $label }}" for="hijri_day">يوم</label>
                            <input type="number" id="hijri_day" name="hijri_day" min="1" max="30" value="{{ old('hijri_day', $event?->hijri_day) }}" class="{{ $inputClass }} tabular-nums" :class="{{ $inputDark }}">
                        </div>
                        <div>
                            <label class="{{ $label }}" for="hijri_month">شهر</label>
                            <select id="hijri_month" name="hijri_month" class="{{ $inputClass }}" :class="{{ $inputDark }}">
                                <option value="">—</option>
                                @foreach($hijriMonths as $number => $monthName)
                                    <option value="{{ $number }}" @selected((string) old('hijri_month', $event?->hijri_month) === (string) $number)>{{ $monthName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="hijri_year">سنة هـ</label>
                            <input type="number" id="hijri_year" name="hijri_year" min="-100" max="100" value="{{ old('hijri_year', $event?->hijri_year) }}" class="{{ $inputClass }} tabular-nums" :class="{{ $inputDark }}">
                        </div>
                    </div>
                    <p class="{{ $help }} !mt-0">السنوات قبل الهجرة بالسالب (مثال: 13-).</p>
                    @foreach(['hijri_day', 'hijri_month', 'hijri_year'] as $field)
                        @error($field) <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                    @endforeach
                </div>

                <div class="wz-section">
                    <div class="wz-grid-2">
                        <div>
                            <h3 class="wz-section-title mb-3"><i data-lucide="calendar-days" aria-hidden="true"></i>{{ __('wizard.seerah.sections.gregorian') }}</h3>
                            <label class="{{ $label }}" for="gregorian_date">التاريخ الميلادي</label>
                            <input type="date" id="gregorian_date" name="gregorian_date" value="{{ old('gregorian_date', $event?->gregorian_date?->format('Y-m-d')) }}"
                                   class="{{ $inputClass }}" :class="{{ $inputDark }}">
                            @error('gregorian_date') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div data-wizard-field>
                            <h3 class="wz-section-title mb-3"><i data-lucide="map-pin" aria-hidden="true"></i>{{ __('wizard.seerah.sections.place') }}</h3>
                            <x-admin.place-picker
                                name="location_id"
                                :selected="old('location_id', $event?->location_id)"
                                :selected-label="$event?->place?->name()"
                                :label="__('locations.singular')"
                                :hint="__('locations.hints.parent')" />
                            @error('location_id') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 3. CONTENT ═══════ --}}
            <x-admin.wizard.panel key="content" :index="3" :total="$totalSteps" icon="layers" class="seerah-content-field"
                :title="__('wizard.seerah.steps.content.label')" :description="__('wizard.seerah.steps.content.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>
                @foreach($locales as $lang)
                    <div x-show="lang === '{{ $lang }}'" class="wysiwyg-field">
                        <label class="{{ $label }}">المحتوى التفصيلي ({{ strtoupper($lang) }})</label>
                        <x-admin.form.wysiwyg :name="'content['.$lang.']'"
                            :value="old('content.'.$lang, $event?->getTranslation('content', $lang, false) ?? '')"
                            :locale="$lang" height="520px" toolbar="full" :modules="true" placeholder="أدخل المحتوى التفصيلي..." />
                        @error("content.{$lang}") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 4. IMAGES & MEDIA ═══════ --}}
            <x-admin.wizard.panel key="media" :index="4" :total="$totalSteps" icon="images"
                :title="__('wizard.seerah.steps.media.label')" :description="__('wizard.seerah.steps.media.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>

                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="image" aria-hidden="true"></i>{{ __('wizard.seerah.sections.images') }}</h3>
                    <div class="wz-grid-2">
                        <div data-wizard-image="thumb" data-wizard-field>
                            <p class="{{ $label }}">الصورة المصغّرة</p>
                            <x-admin.image-upload name="thumb" idName="thumb_id" removeName="remove_thumb" aspectClass="aspect-video" placeholderIcon="calendar"
                                maxSize="10MB" formats="JPG, PNG, WebP"
                                :currentImage="$event?->thumb_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->thumb_url) : null" />
                            <p class="{{ $help }}">تظهر على البطاقات والقوائم.</p>
                            @error('thumb') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div data-wizard-image="cover" data-wizard-field>
                            <p class="{{ $label }}">صورة الغلاف</p>
                            <x-admin.image-upload name="cover" idName="cover_id" removeName="remove_cover" aspectClass="aspect-video" placeholderIcon="image"
                                maxSize="10MB" formats="JPG, PNG, WebP"
                                :currentImage="$event?->cover_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_url) : null" />
                            <p class="{{ $help }}">تظهر أعلى صفحة الحدث.</p>
                            @error('cover') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="wz-section">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="wz-section-title"><i data-lucide="gallery-horizontal-end" aria-hidden="true"></i>{{ __('wizard.seerah.sections.gallery') }}
                            <span x-show="gallery.length" class="wz-pill is-accent tabular-nums" x-text="gallery.length"></span></h3>
                        <button type="button" class="wz-btn is-soft" @click="$dispatch('open-media-library', { target: 'seerah_gallery', multiple: true })">
                            <i data-lucide="plus" aria-hidden="true"></i>{{ __('articles.fields.add_image') }}
                        </button>
                    </div>
                    <p class="{{ $help }} !mt-0">{{ __('articles.help.gallery') }} التعليقات تتبع اللغة المختارة أعلى هذه الخطوة.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" x-show="gallery.length > 0">
                        <template x-for="(item, index) in gallery" :key="item.id">
                            <figure class="wz-card overflow-hidden !rounded-2xl">
                                <div class="relative">
                                    <img :src="item.url" alt="" loading="lazy" class="w-full aspect-video object-cover">
                                    <span class="absolute top-2 start-2 wz-pill !bg-black/55 !text-white !border-transparent tabular-nums" x-text="index + 1"></span>
                                    <div class="absolute top-2 end-2 flex items-center gap-1">
                                        <button type="button" x-show="index > 0" aria-label="تحريك لأعلى" @click="move(gallery, index, -1)" class="w-9 h-9 grid place-items-center rounded-lg bg-black/55 text-white hover:bg-black/70"><i data-lucide="arrow-up" class="w-4 h-4"></i></button>
                                        <button type="button" x-show="index < gallery.length - 1" aria-label="تحريك لأسفل" @click="move(gallery, index, 1)" class="w-9 h-9 grid place-items-center rounded-lg bg-black/55 text-white hover:bg-black/70"><i data-lucide="arrow-down" class="w-4 h-4"></i></button>
                                        <button type="button" aria-label="حذف" @click="gallery.splice(index, 1)" class="w-9 h-9 grid place-items-center rounded-lg bg-red-600/90 text-white hover:bg-red-600"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                </div>
                                <input type="hidden" :name="`gallery[${index}]`" :value="item.id">
                                <figcaption class="p-3">
                                    @foreach($locales as $lang)
                                        <input type="text" x-show="lang === '{{ $lang }}'" x-model="item.caption['{{ $lang }}']"
                                               :name="`gallery_captions[${item.id}][{{ $lang }}]`" maxlength="255"
                                               placeholder="التعليق ({{ strtoupper($lang) }})" aria-label="التعليق ({{ strtoupper($lang) }})" dir="{{ $dirFor($lang) }}"
                                               class="w-full px-3 py-2 text-sm rounded-lg border" :class="{{ $inputDark }}">
                                    @endforeach
                                </figcaption>
                            </figure>
                        </template>
                    </div>
                    <template x-if="gallery.length === 0"><input type="hidden" name="gallery" value=""></template>
                    <div x-show="gallery.length === 0" class="wz-empty">
                        <i data-lucide="images" aria-hidden="true"></i>
                        <p>{{ __('articles.empty.gallery') }}</p>
                    </div>
                    @error('gallery') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                    @error('gallery.*') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="wz-section">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="wz-section-title"><i data-lucide="paperclip" aria-hidden="true"></i>{{ __('wizard.seerah.sections.files') }}
                            <span x-show="media.length" class="wz-pill is-accent tabular-nums" x-text="media.length"></span></h3>
                        <button type="button" class="wz-btn is-soft" @click="$dispatch('open-media-library', { target: 'seerah_media', multiple: true })">
                            <i data-lucide="plus" aria-hidden="true"></i>{{ __('articles.fields.add_media') }}
                        </button>
                    </div>
                    <p class="{{ $help }} !mt-0">{{ __('articles.help.media') }}</p>
                    <div class="grid gap-2" x-show="media.length > 0">
                        <template x-for="(item, index) in media" :key="item.id">
                            <div class="pv-item">
                                <span class="pv-item-icon">
                                    <template x-if="item.thumbnail_url"><img :src="item.thumbnail_url" alt=""></template>
                                    <template x-if="!item.thumbnail_url"><i data-lucide="file" aria-hidden="true"></i></template>
                                </span>
                                <span class="min-w-0">
                                    <span class="pv-item-title block" x-text="item.title"></span>
                                    <span class="pv-item-sub block" x-text="item.type"></span>
                                </span>
                                <button type="button" aria-label="إزالة" @click="media.splice(index, 1)" class="w-9 h-9 grid place-items-center rounded-lg text-red-500 hover:bg-red-500/10"><i data-lucide="x" class="w-4 h-4"></i></button>
                                <input type="hidden" :name="`media_items[${index}]`" :value="item.id">
                            </div>
                        </template>
                    </div>
                    <template x-if="media.length === 0"><input type="hidden" name="media_items" value=""></template>
                    <div x-show="media.length === 0" class="wz-empty">
                        <i data-lucide="paperclip" aria-hidden="true"></i>
                        <p>{{ __('articles.empty.media') }}</p>
                    </div>
                    @error('media_items.*') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="wz-section">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="wz-section-title"><i data-lucide="list-video" aria-hidden="true"></i>{{ __('wizard.seerah.sections.episodes') }}
                            <span x-show="episodes.length" class="wz-pill is-accent tabular-nums" x-text="episodes.length"></span></h3>
                        <button type="button" class="wz-btn is-soft" @click="$dispatch('open-episode-picker')">
                            <i data-lucide="plus" aria-hidden="true"></i>{{ __('articles.actions.assign_episodes') }}
                        </button>
                    </div>
                    <p class="{{ $help }} !mt-0">{{ __('articles.help.episodes') }}</p>
                    <div class="grid gap-2" x-show="episodes.length > 0">
                        <template x-for="(ep, index) in episodes" :key="ep.id">
                            <div class="pv-item" style="grid-template-columns: auto auto minmax(0,1fr) auto">
                                <span class="w-6 text-center text-xs font-bold tabular-nums text-slate-500" x-text="index + 1"></span>
                                <img :src="ep.thumb" x-show="ep.thumb" alt="" class="w-24 aspect-video rounded-lg object-cover bg-slate-200 dark:bg-slate-800">
                                <span class="min-w-0">
                                    <span class="pv-item-title block" x-text="ep.title"></span>
                                    <span class="pv-item-sub block" x-text="ep.subtitle || ep.program"></span>
                                </span>
                                <span class="flex items-center">
                                    <button type="button" aria-label="تحريك لأعلى" @click="move(episodes, index, -1)" :disabled="index === 0" class="w-9 h-9 grid place-items-center rounded-lg disabled:opacity-30 hover:bg-black/5"><i data-lucide="arrow-up" class="w-4 h-4"></i></button>
                                    <button type="button" aria-label="تحريك لأسفل" @click="move(episodes, index, 1)" :disabled="index === episodes.length - 1" class="w-9 h-9 grid place-items-center rounded-lg disabled:opacity-30 hover:bg-black/5"><i data-lucide="arrow-down" class="w-4 h-4"></i></button>
                                    <button type="button" aria-label="إزالة" @click="episodes.splice(index, 1)" class="w-9 h-9 grid place-items-center rounded-lg text-red-500 hover:bg-red-500/10"><i data-lucide="x" class="w-4 h-4"></i></button>
                                </span>
                                <input type="hidden" :name="`links[episode][${index}]`" :value="ep.id">
                            </div>
                        </template>
                    </div>
                    <template x-if="episodes.length === 0"><input type="hidden" name="links[episode]" value=""></template>
                    <div x-show="episodes.length === 0" class="wz-empty">
                        <i data-lucide="list-video" aria-hidden="true"></i>
                        <p>{{ __('articles.empty.episodes') }}</p>
                    </div>
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 5. LINKS ═══════ --}}
            <x-admin.wizard.panel key="links" :index="5" :total="$totalSteps" icon="network"
                :title="__('wizard.seerah.steps.links.label')" :description="__('wizard.seerah.steps.links.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>

                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="network" aria-hidden="true"></i>{{ __('seerah.admin.related_content') }}</h3>
                    <p class="{{ $help }} !mt-0">{{ __('seerah.admin.related_content_help') }}</p>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach($linkTypes as $type => [$icon, $typeLabel, $selectorType])
                            <div class="rounded-2xl border p-4" :class="darkMode ? 'border-white/10 bg-white/[0.02]' : 'border-slate-200 bg-slate-50/60'">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <h4 class="text-sm font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                        <i data-lucide="{{ $icon }}" class="w-4 h-4 text-[color:var(--wz-accent)]"></i> {{ $typeLabel }}
                                        <span x-show="links.{{ $type }}.length" class="wz-pill is-accent tabular-nums !min-h-0 !py-0" x-text="links.{{ $type }}.length"></span>
                                    </h4>
                                    <button type="button" class="wz-btn !min-h-9 !px-3 !text-xs"
                                            @click="{{ $type === 'article' ? "\$dispatch('open-article-picker', { exclude: links.article.map((a) => a.id) })" : "pickModule('{$type}', '{$selectorType}')" }}">
                                        <i data-lucide="plus" aria-hidden="true"></i> {{ __('seerah.admin.add_link') }}
                                    </button>
                                </div>
                                <ul class="grid gap-2">
                                    <template x-for="(item, index) in links.{{ $type }}" :key="item.id">
                                        <li class="pv-item" style="grid-template-columns: minmax(0,1fr) auto">
                                            <span class="min-w-0">
                                                <span class="pv-item-title block" x-text="item.title"></span>
                                                <span class="pv-item-sub block" x-show="item.subtitle" x-text="item.subtitle"></span>
                                            </span>
                                            <span class="flex items-center">
                                                <button type="button" aria-label="تحريك لأعلى" @click="move(links.{{ $type }}, index, -1)" :disabled="index === 0" class="w-8 h-8 grid place-items-center rounded-lg disabled:opacity-30 hover:bg-black/5"><i data-lucide="arrow-up" class="w-3.5 h-3.5"></i></button>
                                                <button type="button" aria-label="إزالة" @click="links.{{ $type }}.splice(index, 1)" class="w-8 h-8 grid place-items-center rounded-lg text-red-500 hover:bg-red-500/10"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                            </span>
                                            <input type="hidden" :name="`links[{{ $type }}][${index}]`" :value="item.id">
                                        </li>
                                    </template>
                                </ul>
                                <template x-if="links.{{ $type }}.length === 0"><input type="hidden" name="links[{{ $type }}]" value=""></template>
                                <p x-show="links.{{ $type }}.length === 0" class="text-xs py-2" :class="darkMode ? 'text-slate-500' : 'text-slate-400'">{{ __('seerah.admin.no_links') }}</p>
                            </div>
                        @endforeach
                    </div>
                    @error('links') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="wz-section">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach(['tag' => ['tags', 'tags', __('articles.fields.tags')], 'category' => ['categories', 'folder', __('articles.fields.categories')]] as $type => [$optionsKey, $icon, $groupLabel])
                            <fieldset class="rounded-2xl border p-4" :class="darkMode ? 'border-white/10' : 'border-slate-200'">
                                <legend class="px-1 text-sm font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    <i data-lucide="{{ $icon }}" class="w-4 h-4 text-[color:var(--wz-accent)]"></i> {{ $groupLabel }}
                                </legend>
                                @php $selected = collect(old("links.{$type}", collect($linkItems[$type] ?? [])->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(); @endphp
                                {{-- Posted first; any checked box below replaces it, so none checked = clear. --}}
                                <input type="hidden" name="links[{{ $type }}]" value="">
                                <div class="max-h-56 overflow-y-auto flex flex-wrap gap-2 pt-2">
                                    <template x-for="option in created.{{ $type }}" :key="option.id">
                                        <label class="min-h-9 inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm cursor-pointer has-[:checked]:border-[#0056b3] has-[:checked]:bg-[#0056b3]/10"
                                               :class="darkMode ? 'border-gray-600 text-gray-200' : 'border-slate-300 text-slate-700'">
                                            <input type="checkbox" name="links[{{ $type }}][]" :value="option.id" class="w-4 h-4 rounded" checked>
                                            <span x-text="option.name"></span>
                                        </label>
                                    </template>
                                    @forelse($linkOptions[$optionsKey] as $id => $name)
                                        <label class="min-h-9 inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm cursor-pointer has-[:checked]:border-[#0056b3] has-[:checked]:bg-[#0056b3]/10"
                                               :class="darkMode ? 'border-gray-600 text-gray-200' : 'border-slate-300 text-slate-700'">
                                            <input type="checkbox" name="links[{{ $type }}][]" value="{{ $id }}" class="w-4 h-4 rounded"
                                                   @checked(in_array((int) $id, $selected, true))>
                                            {{ $name }}
                                        </label>
                                    @empty
                                        <p x-show="!created.{{ $type }}.length" class="text-xs" :class="darkMode ? 'text-slate-500' : 'text-slate-400'">{{ __('seerah.admin.no_options') }}</p>
                                    @endforelse
                                </div>

                                {{-- Quick create: saved immediately, then ticked for this event. --}}
                                <div class="mt-3 pt-3 border-t grid gap-2 sm:grid-cols-[1fr_1fr_auto]" :class="darkMode ? 'border-white/10' : 'border-slate-200'" data-wizard-ignore>
                                    <input type="text" x-model="quick.{{ $type }}.ar" dir="rtl" @keydown.enter.prevent="quickCreate('{{ $type }}')"
                                           placeholder="{{ __('seerah.admin.quick_name_ar') }}" aria-label="{{ __('seerah.admin.quick_name_ar') }}"
                                           class="w-full px-3 py-2 text-sm rounded-lg border" :class="{{ $inputDark }}">
                                    <input type="text" x-model="quick.{{ $type }}.en" dir="ltr" @keydown.enter.prevent="quickCreate('{{ $type }}')"
                                           placeholder="{{ __('seerah.admin.quick_name_en') }}{{ $type === 'category' ? ' *' : '' }}" aria-label="{{ __('seerah.admin.quick_name_en') }}"
                                           class="w-full px-3 py-2 text-sm rounded-lg border" :class="{{ $inputDark }}">
                                    <button type="button" class="wz-btn is-soft !min-h-10" @click="quickCreate('{{ $type }}')" :disabled="quick.{{ $type }}.busy">
                                        <i data-lucide="plus" aria-hidden="true"></i> {{ __('seerah.admin.quick_create') }}
                                    </button>
                                    <p x-show="quick.{{ $type }}.error" x-text="quick.{{ $type }}.error" class="sm:col-span-3 wz-field-error" role="alert"></p>
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </div>

                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="external-link" aria-hidden="true"></i>{{ __('seerah.admin.external_links') }}</h3>
                    <p class="{{ $help }} !mt-0">{{ __('seerah.admin.external_links_help') }}</p>
                    @foreach($locales as $lang)
                        <div x-show="lang === '{{ $lang }}'">
                            <x-admin.form.repeater :name="'external_links['.$lang.']'" :rows="$localeRows('external_links', $lang)"
                                :fields="['title' => __('seerah.admin.link_title'), 'url' => 'https://…']" :dir="$dirFor($lang)"
                                :add-label="__('seerah.admin.add_link')" :empty-text="__('seerah.admin.no_links')" />
                            @foreach($errors->get("external_links.{$lang}.*") as $messages)
                                <p class="wz-field-error" role="alert">{{ $messages[0] }}</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 6. SEO ═══════ --}}
            <x-admin.wizard.panel key="seo" :index="6" :total="$totalSteps" icon="search"
                :title="__('wizard.seerah.steps.seo.label')" :description="__('wizard.seerah.steps.seo.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>
                @foreach($locales as $lang)
                    <div x-show="lang === '{{ $lang }}'" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] items-start"
                         x-data="{ seoTitle: @js((string) $seoValue($lang, 'title')), seoDescription: @js((string) $seoValue($lang, 'description')) }">
                        <div class="grid gap-5">
                            <div>
                                <label class="{{ $label }}" for="seo-title-{{ $lang }}">
                                    {{ __('articles.seo.title') }} <span class="text-slate-400 font-normal text-xs">({{ __('common.optional') }})</span>
                                </label>
                                <input type="text" id="seo-title-{{ $lang }}" name="seo[{{ $lang }}][title]" maxlength="70" x-model="seoTitle"
                                       class="{{ $inputClass }}" :class="{{ $inputDark }}" dir="{{ $dirFor($lang) }}">
                                <div class="flex justify-between gap-3 mt-1.5">
                                    <p class="wz-help !mt-0">{{ __('articles.help.seo_title_length') }}</p>
                                    <span class="text-xs tabular-nums font-semibold" :class="seoTitle.length > 60 ? 'text-amber-600' : 'text-slate-400'"><span x-text="seoTitle.length"></span>/70</span>
                                </div>
                                <div class="mt-2 h-1 rounded-full overflow-hidden bg-slate-200 dark:bg-white/10" aria-hidden="true">
                                    <div class="h-full ltr:origin-left rtl:origin-right transition-transform duration-300" :class="seoTitle.length > 60 ? 'bg-amber-500' : 'bg-[#218838]'" :style="`transform: scaleX(${Math.min(seoTitle.length / 70, 1)})`"></div>
                                </div>
                                @error("seo.{$lang}.title") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}" for="seo-description-{{ $lang }}">
                                    {{ __('articles.seo.description') }} <span class="text-slate-400 font-normal text-xs">({{ __('common.optional') }})</span>
                                </label>
                                <textarea id="seo-description-{{ $lang }}" name="seo[{{ $lang }}][description]" rows="4" maxlength="160" x-model="seoDescription"
                                          class="{{ $inputClass }} resize-y" :class="{{ $inputDark }}" dir="{{ $dirFor($lang) }}"></textarea>
                                <div class="flex justify-between gap-3 mt-1.5">
                                    <p class="wz-help !mt-0">{{ __('articles.help.seo_desc_length') }}</p>
                                    <span class="text-xs tabular-nums font-semibold" :class="seoDescription.length > 155 ? 'text-amber-600' : 'text-slate-400'"><span x-text="seoDescription.length"></span>/160</span>
                                </div>
                                <div class="mt-2 h-1 rounded-full overflow-hidden bg-slate-200 dark:bg-white/10" aria-hidden="true">
                                    <div class="h-full ltr:origin-left rtl:origin-right transition-transform duration-300" :class="seoDescription.length > 155 ? 'bg-amber-500' : 'bg-[#218838]'" :style="`transform: scaleX(${Math.min(seoDescription.length / 160, 1)})`"></div>
                                </div>
                                @error("seo.{$lang}.description") <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="rounded-2xl border p-5" :class="darkMode ? 'bg-white/[0.03] border-white/10' : 'bg-white border-slate-200'" dir="{{ $dirFor($lang) }}">
                            <p class="text-xs font-bold mb-4 flex items-center gap-1.5 text-slate-500"><i data-lucide="scan-search" class="w-4 h-4" aria-hidden="true"></i>{{ __('articles.seo.preview') }}</p>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-7 h-7 rounded-full grid place-items-center bg-slate-100 dark:bg-white/10"><i data-lucide="globe" class="w-3.5 h-3.5 text-slate-500" aria-hidden="true"></i></span>
                                <span class="min-w-0 text-xs leading-tight">
                                    <span class="block font-semibold" :class="darkMode ? 'text-slate-200' : 'text-slate-800'">{{ config('app.name') }}</span>
                                    <span class="block text-slate-500 truncate" dir="ltr">{{ url('/seerah/event') }}/<span x-text="summary.slug || 'event-slug'"></span></span>
                                </span>
                            </div>
                            <p class="text-lg font-semibold leading-snug break-words text-[#1a0dab] dark:text-[#8ab4f8]" x-text="seoTitle || titles['{{ $lang }}'] || @js(__('articles.fields.title'))"></p>
                            <p class="mt-1 text-sm leading-relaxed break-words" :class="darkMode ? 'text-slate-400' : 'text-slate-600'" x-text="seoDescription || '…'"></p>
                        </div>
                    </div>
                @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 7. DETAILS & SOURCES ═══════ --}}
            <x-admin.wizard.panel key="details" :index="7" :total="$totalSteps" icon="bar-chart-3"
                :title="__('wizard.seerah.steps.details.label')" :description="__('wizard.seerah.steps.details.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$languages" /></x-slot:actions>
                <div class="wz-section">
                    <h3 class="wz-section-title"><i data-lucide="bar-chart-3" aria-hidden="true"></i>الإحصائيات</h3>
                    <p class="{{ $help }} !mt-0">تظهر كبطاقات أعلى صفحة الحدث (أول 4 بطاقات). مثال: «عدد المسلمين» / «313». غير مرتبطة باللغة.</p>
                    <x-admin.form.repeater name="statistics" :rows="$oldStatistics ?? []" :fields="['label' => 'التسمية', 'value' => 'القيمة']" :max="12" empty-text="لا توجد إحصائيات بعد." />
                    @error('statistics') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                @foreach($locales as $lang)
                    <div x-show="lang === '{{ $lang }}'" class="wz-section">
                        <div class="grid gap-8">
                            <div class="grid gap-3">
                                <h3 class="wz-section-title"><i data-lucide="list" aria-hidden="true"></i>تفاصيل إضافية ({{ strtoupper($lang) }})</h3>
                                <p class="{{ $help }} !mt-0">أزواج «مفتاح / قيمة» تُعرض كقائمة في صفحة الحدث. مثال: «المكان» / «حمراء الأسد، على نحو 8 أميال جنوب المدينة».</p>
                                <x-admin.form.repeater :name="'metadata['.$lang.']'" :rows="$localeRows('metadata', $lang)" :fields="['key' => 'المفتاح', 'value' => 'القيمة']" :dir="$dirFor($lang)" add-label="إضافة تفصيل" empty-text="لا توجد تفاصيل بعد." />
                            </div>
                            <div class="grid gap-3">
                                <h3 class="wz-section-title"><i data-lucide="library" aria-hidden="true"></i>المصادر والمراجع ({{ strtoupper($lang) }})</h3>
                                <p class="{{ $help }} !mt-0">مثال: «الرحيق المختوم» / «صفي الرحمن المباركفوري» / «فصل غزوة بدر».</p>
                                <x-admin.form.repeater :name="'sources['.$lang.']'" :rows="$localeRows('sources', $lang)" :fields="['title' => 'اسم المرجع', 'author' => 'المؤلف', 'reference' => 'الموضع (فصل/جزء)']" :dir="$dirFor($lang)" add-label="إضافة مرجع" empty-text="لا توجد مراجع بعد." />
                            </div>
                        </div>
                    </div>
                @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 8. REVIEW ═══════ --}}
            <x-admin.wizard.panel key="review" :index="8" :total="$totalSteps" icon="badge-check"
                :title="__('wizard.seerah.steps.review.label')" :description="__('wizard.seerah.steps.review.desc')">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums" :class="reviewCount('required') ? 'text-red-600' : 'text-[color:var(--wz-success)]'" x-text="reviewCount('required')"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.required') }}</p>
                    </div>
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums text-[color:var(--wz-warn)]" x-text="reviewCount('recommended')"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.recommended') }}</p>
                    </div>
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums text-[color:var(--wz-success)]" x-text="reviewChecks().filter((c) => c.ok).length"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.ok') }}</p>
                    </div>
                </div>

                <p class="wz-pill !min-h-9 !px-4 w-fit" :class="summary.status === 'published' ? 'is-success' : 'is-warn'">
                    <i data-lucide="info" class="w-4 h-4" aria-hidden="true"></i>
                    <span x-text="summary.status === 'published' ? @js(__('wizard.published_note')) : @js(__('wizard.draft_note'))"></span>
                </p>

                <div class="wz-checks">
                    <template x-for="check in reviewChecks()" :key="check.key">
                        <button type="button" class="wz-check" :class="check.ok ? 'is-ok' : (check.level === 'required' ? 'is-required' : 'is-warn')" @click="goTo(check.step)">
                            <span class="wz-check-icon">
                                <span x-show="check.ok"><i data-lucide="check" aria-hidden="true"></i></span>
                                <span x-show="!check.ok && check.level === 'required'"><i data-lucide="x" aria-hidden="true"></i></span>
                                <span x-show="!check.ok && check.level !== 'required'"><i data-lucide="lightbulb" aria-hidden="true"></i></span>
                            </span>
                            <span class="min-w-0">
                                <span class="wz-check-title block" x-text="check.label"></span>
                                <span class="wz-check-detail block" x-text="check.ok ? @js(__('wizard.ok')) : (check.level === 'required' ? @js(__('wizard.required')) : @js(__('wizard.recommended')))"></span>
                            </span>
                            <span class="wz-check-go" x-text="stepLabel(check.step)"></span>
                        </button>
                    </template>
                </div>
            </x-admin.wizard.panel>

            <x-admin.wizard.command-bar :preview-url="$event ? route('admin.seerah-events.show', $event->id) : null" :total="$totalSteps" />
        </div>

        {{-- ═══════ RAIL ═══════ --}}
        <aside class="wz-rail" aria-label="{{ __('wizard.summary') }}">
            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="panel-top" aria-hidden="true"></i>{{ __('wizard.summary') }}</p>
                <div class="wz-summary-media">
                    <template x-if="summary.image"><img :src="summary.image" alt=""></template>
                    <template x-if="!summary.image"><i data-lucide="image" class="w-8 h-8" aria-hidden="true"></i></template>
                </div>
                <p class="mt-3 font-bold leading-snug break-words" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="titles.ar || @js(__('wizard.untitled'))"></p>
                <dl class="wz-kv">
                    <div><dt>{{ __('wizard.seerah.rail.status') }}</dt><dd x-text="summary.statusLabel || '—'"></dd></div>
                    <div><dt>{{ __('wizard.seerah.rail.type') }}</dt><dd x-text="summary.type || @js(__('wizard.not_set'))"></dd></div>
                    <div><dt>{{ __('wizard.seerah.rail.milestone') }}</dt><dd x-text="summary.milestone || @js(__('wizard.not_set'))"></dd></div>
                    <div><dt>{{ __('wizard.seerah.rail.date') }}</dt><dd x-text="summary.date || @js(__('wizard.not_set'))"></dd></div>
                    <div><dt>{{ __('wizard.seerah.rail.links') }}</dt><dd class="tabular-nums" x-text="linkCount()"></dd></div>
                    <div><dt>{{ __('wizard.seerah.rail.gallery') }}</dt><dd class="tabular-nums" x-text="gallery.length"></dd></div>
                </dl>
            </div>

            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="languages" aria-hidden="true"></i>{{ __('wizard.languages') }}</p>
                <ul class="grid gap-2">
                    @foreach($languages as $code => $langLabel)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <button type="button" class="font-semibold hover:underline" :class="darkMode ? 'text-slate-200' : 'text-slate-700'" @click="lang = '{{ $code }}'; goTo('basics')">{{ $langLabel }}</button>
                            <span class="wz-pill !min-h-6" :class="langFilled('{{ $code }}') ? 'is-success' : ''">
                                <span class="wz-dot" aria-hidden="true"></span>
                                <span x-text="langFilled('{{ $code }}') ? @js(__('wizard.ok')) : @js(__('wizard.not_set'))"></span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="wz-card wz-rail-card grid gap-2">
                @if($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="wz-btn w-full"><i data-lucide="external-link" aria-hidden="true"></i>{{ __('wizard.open_public') }}</a>
                @endif
                <a href="{{ route('admin.seerah-events.index') }}" class="wz-btn is-ghost w-full"><i data-lucide="list" aria-hidden="true"></i>{{ __('wizard.back_to_list') }}</a>
            </div>
        </aside>
    </div>
</div>

<x-admin.media-picker-modal />
<x-admin.episode-picker-modal />
<x-admin.article-picker-modal :categories="$linkOptions['categories']" />

@once
@push('scripts')
<script>
    /**
     * Seerah event wizard. Gallery/media answer the media library's `media-selected`;
     * episodes answer the episode picker's `episodes-selected`; verse/hadith/companion
     * links answer the module selector's `module-block-add`, but only when this form
     * opened it (the content editors share that modal). Step navigation, dirty tracking
     * and quick save come from window.adminWizard.
     */
    function seerahEventForm(initial) {
        const locales = initial.locales ?? ['ar'];
        const withCaption = (item) => ({
            ...item,
            caption: Object.fromEntries(locales.map((l) => [l, (item.caption ?? {})[l] ?? ''])),
        });
        const linkList = (type) => (initial.links?.[type] ?? []);
        const stepLabels = @js(collect($steps)->map(fn ($s) => $s[1]));
        const checkLabels = @js(__('wizard.seerah.checks'));

        return {
            ...window.adminWizard({
                steps: initial.steps,
                initialStep: initial.step,
                isEdit: initial.isEdit,
                updateUrlTemplate: initial.updateUrl,
                editUrlTemplate: initial.editUrl,
                locales,
                langProp: 'lang',
                messages: initial.messages,
                stepComplete: (form, key) => form.isStepComplete(key),
                afterSave: (form, json) => {
                    // A blank slug is generated server-side; pin it so later saves don't regenerate it from an edited title.
                    const slug = form.field('slug');
                    if (slug && !slug.value && json.data?.slug) slug.value = json.data.slug;
                    form.refreshSummary();
                },
            }),

            lang: 'ar',
            titles: {},
            summary: { status: '', statusLabel: '', type: '', milestone: '', date: '', slug: '', image: '', tick: 0 },
            gallery: (initial.gallery ?? []).map(withCaption),
            media: initial.media ?? [],
            episodes: initial.episodes ?? [],
            links: {
                verse: linkList('verse'),
                hadith: linkList('hadith'),
                muslim: linkList('muslim'),
                article: linkList('article'),
            },
            pickingLinkType: null,
            created: { tag: [], category: [] },
            quick: {
                tag: { ar: '', en: '', busy: false, error: '' },
                category: { ar: '', en: '', busy: false, error: '' },
            },

            init() {
                this.stepErrors = initial.stepErrors ?? {};
                this.wizardInit();

                // Alpine-only list edits never fire DOM input events.
                ['gallery', 'media', 'episodes', 'links', 'created'].forEach((key) =>
                    this.$watch(key, () => this.markDirty()));

                const form = this.wizardForm();
                let queued = false;
                const refresh = () => {
                    if (queued) return;
                    queued = true;
                    // Image inputs preview through FileReader, so read the DOM a beat later.
                    setTimeout(() => { queued = false; this.refreshSummary(); }, 250);
                };
                form?.addEventListener('input', refresh);
                form?.addEventListener('change', refresh);
                form?.addEventListener('wizard:dirty', refresh);
                window.addEventListener('media-selected', refresh);
                this.$watch('step', refresh);
                this.$nextTick(() => this.refreshSummary());
            },

            // ── Live summary / completeness ───────────────────────────
            field(name) {
                return this.wizardForm()?.elements.namedItem(name);
            },

            value(name) {
                const el = this.field(name);
                return (el && 'value' in el ? String(el.value ?? '') : '').trim();
            },

            selectedText(name) {
                const el = this.field(name);
                return el?.value ? (el.selectedOptions?.[0]?.textContent ?? '').trim() : '';
            },

            filledRows(prefix) {
                return [...(this.wizardForm()?.querySelectorAll(`[name^="${prefix}["]`) ?? [])]
                    .some((el) => el.type !== 'hidden' && el.type !== 'checkbox' && String(el.value).trim() !== '');
            },

            imagePresent(key) {
                const img = this.$root.querySelector(`[data-wizard-image="${key}"] img`);
                return !!img?.getAttribute('src');
            },

            refreshSummary() {
                const day = this.value('hijri_day');
                const month = this.selectedText('hijri_month');
                const year = this.value('hijri_year');
                const hijri = year ? [day, month, `${year} هـ`].filter(Boolean).join(' ') : '';

                this.summary = {
                    status: this.value('status'),
                    statusLabel: this.selectedText('status'),
                    type: this.selectedText('event_type'),
                    milestone: this.selectedText('sm_id').replace(/\s*\(.*\)$/, ''),
                    date: hijri || this.value('gregorian_date'),
                    slug: this.value('slug'),
                    image: this.$root.querySelector('[data-wizard-image="thumb"] img')?.getAttribute('src')
                        || this.$root.querySelector('[data-wizard-image="cover"] img')?.getAttribute('src') || '',
                    tick: this.summary.tick + 1,
                };
            },

            langFilled(code) {
                return (this.titles[code] ?? '').trim() !== '';
            },

            taxonomyCount() {
                return this.wizardForm()?.querySelectorAll('input[type="checkbox"][name^="links["]:checked').length ?? 0;
            },

            isStepComplete(key) {
                void this.summary.tick;
                switch (key) {
                    case 'basics': return this.langFilled('ar') && this.value('event_type') !== '';
                    case 'timeline': return this.value('hijri_year') !== '' || this.value('gregorian_date') !== '' || this.value('location_id') !== '';
                    case 'content': return this.value('content[ar]') !== '';
                    case 'media': return this.imagePresent('thumb') || this.gallery.length > 0 || this.media.length > 0 || this.episodes.length > 0;
                    case 'links': return this.linkCount() + this.taxonomyCount() > 0 || this.filledRows('external_links');
                    case 'seo': return this.value('seo[ar][title]') !== '' || this.value('seo[ar][description]') !== '';
                    case 'details': return this.filledRows('statistics') || this.filledRows('sources') || this.filledRows('metadata');
                    default: return !!this.visited[key];
                }
            },

            stepLabel(key) {
                return stepLabels[key] ?? key;
            },

            reviewChecks() {
                void this.summary.tick;
                void this.step;
                return [
                    { key: 'title', step: 'basics', level: 'required', ok: this.langFilled('ar') },
                    { key: 'type', step: 'basics', level: 'required', ok: this.value('event_type') !== '' },
                    { key: 'description', step: 'basics', level: 'recommended', ok: this.value('description[ar]') !== '' },
                    { key: 'date', step: 'timeline', level: 'recommended', ok: this.value('hijri_year') !== '' },
                    { key: 'place', step: 'timeline', level: 'recommended', ok: this.value('location_id') !== '' },
                    { key: 'content', step: 'content', level: 'recommended', ok: this.value('content[ar]') !== '' },
                    { key: 'thumb', step: 'media', level: 'recommended', ok: this.imagePresent('thumb') },
                    { key: 'cover', step: 'media', level: 'recommended', ok: this.imagePresent('cover') },
                    { key: 'links', step: 'links', level: 'recommended', ok: this.linkCount() > 0 },
                    { key: 'seo', step: 'seo', level: 'recommended', ok: this.value('seo[ar][title]') !== '' },
                    { key: 'sources', step: 'details', level: 'recommended', ok: this.filledRows('sources[ar]') },
                ].map((check) => ({ ...check, label: checkLabels[check.key] ?? check.key }));
            },

            reviewCount(level) {
                return this.reviewChecks().filter((check) => !check.ok && check.level === level).length;
            },

            // ── Lists ─────────────────────────────────────────────────
            linkCount() {
                return Object.values(this.links).reduce((sum, list) => sum + list.length, 0);
            },

            move(list, index, step) {
                const to = index + step;
                if (to < 0 || to >= list.length) return;
                [list[to], list[index]] = [list[index], list[to]];
            },

            addLink(type, item) {
                if (!this.links[type].some((existing) => existing.id === item.id)) {
                    this.links[type].push(item);
                }
                this.$nextTick(() => window.lucide?.createIcons());
            },

            pickModule(type, selectorType) {
                this.pickingLinkType = type;
                window.dispatchEvent(new CustomEvent('open-module-selector', {
                    detail: { type: selectorType, locale: this.lang, insertMode: 'block' },
                }));
            },

            onModuleBlock(detail) {
                const type = this.pickingLinkType;
                const block = detail?.block;
                if (!type || !block) return;
                this.pickingLinkType = null;

                if (type === 'verse') {
                    (block.verses ?? []).filter((v) => v.id).forEach((v) =>
                        this.addLink('verse', { id: v.id, title: v.text, subtitle: `${block.surah_name} · ${v.key}` }));
                } else if (type === 'hadith' && block.hadith_id) {
                    this.addLink('hadith', { id: block.hadith_id, title: block.text, subtitle: [block.collection, block.number].filter(Boolean).join(' · ') });
                } else if (type === 'muslim' && block.sahabi_id) {
                    this.addLink('muslim', { id: block.sahabi_id, title: block.name, subtitle: block.kunya || '' });
                }
            },

            addArticles(selected) {
                (selected ?? []).forEach((row) => this.addLink('article', {
                    id: row.id, title: row.title, subtitle: [row.category, row.status].filter(Boolean).join(' · '),
                }));
            },

            async quickCreate(type) {
                const state = this.quick[type];
                if (state.busy || !state.ar.trim()) { state.error = state.ar.trim() ? '' : @js(__('seerah.admin.quick_name_required')); return; }
                state.busy = true;
                state.error = '';
                const body = type === 'tag'
                    ? { name: { ar: state.ar.trim(), en: state.en.trim() || null }, type: 'event' }
                    : { name: { ar: state.ar.trim(), en: state.en.trim() }, type: 'timeline' };
                try {
                    const response = await fetch(type === 'tag' ? initial.tagStoreUrl : initial.categoryStoreUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify(body),
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        state.error = Object.values(json.errors ?? {})[0]?.[0] ?? json.message ?? @js(__('seerah.admin.quick_create_failed'));
                        return;
                    }
                    this.created[type].push({ id: json.data.id, name: state.ar.trim() });
                    state.ar = '';
                    state.en = '';
                } catch (e) {
                    state.error = @js(__('seerah.admin.quick_create_failed'));
                } finally {
                    state.busy = false;
                }
            },

            addEpisodes(selected) {
                const existing = new Set(this.episodes.map((e) => e.id));
                (selected ?? []).forEach((ep) => {
                    if (!existing.has(ep.id)) {
                        this.episodes.push({ id: ep.id, title: ep.title, subtitle: [ep.program, ep.episode ? `E${ep.episode}` : null].filter(Boolean).join(' · '), thumb: ep.thumb });
                        existing.add(ep.id);
                    }
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            onMediaSelected(detail) {
                const items = detail.multiple ? detail.media : [detail.media];

                if (detail.target === 'seerah_gallery') {
                    items.filter((m) => !this.gallery.some((g) => g.id === m.id))
                        .forEach((m) => this.gallery.push(withCaption({ id: m.id, url: m.thumbnail_url || m.url })));
                } else if (detail.target === 'seerah_media') {
                    items.filter((m) => !this.media.some((x) => x.id === m.id))
                        .forEach((m) => this.media.push({ id: m.id, title: m.title || m.original_filename || m.filename, type: m.type, thumbnail_url: m.thumbnail_url }));
                } else {
                    return;
                }

                this.$nextTick(() => window.lucide?.createIcons());
            },
        };
    }
</script>
@endpush
@endonce
