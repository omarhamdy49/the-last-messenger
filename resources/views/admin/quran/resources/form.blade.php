@extends('layouts.admin')

@php
    /**
     * Catalogue resource form (create + edit) as a clickable-step wizard. Field names are
     * unchanged, so the normal submit, old() and @error keep working; quick save comes from
     * window.adminWizard (resources/js/admin/wizard.js). Reciter kinds get the extra "source" step.
     */
    $languageLabels = enabled_content_languages();
    $locales = array_keys($languageLabels);
    $title = $resource ? __('quran.manage.edit_resource', ['name' => $resource->name]) : __('quran.manage.add_resource', ['kind' => $kind->label()]);
    $dirFor = fn (string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';

    // Steps: key => [icon, error-key prefixes that belong to the step].
    $stepDefs = array_filter([
        'identity' => ['id-card', ['name', 'slug', 'author_name', 'language_iso', 'display_order', 'is_active', 'is_default', 'sync_enabled']],
        'source' => $kind->isReciter() ? ['mic', ['style', 'qirat', 'external_source', 'external_ref_id', 'store_audio_locally']] : null,
        'profile' => ['image', ['image', 'image_id', 'image_url', 'remove_image', 'name_translations', 'description_translations']],
        'about' => ['file-text', ['info']],
    ]);
    $steps = collect($stepDefs)->map(fn (array $def, string $key): array => [
        $def[0], __("wizard.resource.steps.{$key}.label"), __("wizard.resource.steps.{$key}.hint"),
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

    $wizardMessages = collect(['saving', 'saved', 'unsaved', 'no_changes', 'save_failed', 'fix_errors', 'session_expired', 'network_error', 'saved_toast'])
        ->mapWithKeys(fn (string $k): array => [$k => __("wizard.{$k}")])->all();

    $currentImage = filled($resource?->image)
        ? (str_starts_with($resource->image, 'http') ? $resource->image : Storage::disk('public')->url($resource->image))
        : null;
    $listUrl = route('admin.quran.resources.index', $kind->routeSegment());

    $toggles = array_filter([
        'is_active' => [__('quran.manage.active'), $resource?->is_active ?? true],
        'is_default' => [__('quran.manage.default'), $resource?->is_default ?? false],
        'sync_enabled' => $resource?->qf_id !== null ? [__('quran.manage.sync_enabled'), $resource->sync_enabled] : null,
    ]);
@endphp

@section('title', $title)

@section('page-header')
    {{-- Breadcrumb only: the wizard hero carries the live title. --}}
    <nav class="flex flex-wrap items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
        <a href="{{ $listUrl }}" class="hover:text-accent transition-colors">{{ $kind->label() }}</a>
        <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180" aria-hidden="true"></i>
        <span aria-current="page">{{ $title }}</span>
    </nav>
@endsection

@section('content')
    <x-admin.quran.flash />

    @if ($resource?->isFromQf())
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">{{ __('quran.manage.qf_resource_edit_help', ['id' => $resource->qf_id]) }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data" novalidate
          action="{{ $resource ? route('admin.quran.resources.update', $resource->id) : route('admin.quran.resources.store', $kind->routeSegment()) }}">
        @csrf
        @if ($resource) @method('PUT') @endif

        <div class="wz-root" x-data="quranResourceForm({{ Js::from([
                'step' => array_key_first($serverStepErrors),
                'steps' => $stepKeys,
                'stepErrors' => $serverStepErrors,
                'isEdit' => (bool) $resource,
                'updateUrl' => route('admin.quran.resources.update', '__ID__'),
                'editUrl' => route('admin.quran.resources.edit', '__ID__'),
                'messages' => $wizardMessages,
                'locales' => $locales,
            ]) }})">

            <x-admin.wizard.hero :eyebrow="$kind->label()" :icon="$kind->isReciter() ? 'mic' : 'library'" title-expr="summary.name" :steps="$steps">
                <x-slot:meta>
                    <span class="wz-pill" :class="summary.active ? 'is-success' : 'is-warn'">
                        <span class="wz-dot" aria-hidden="true"></span>
                        <span x-text="summary.active ? @js(__('quran.manage.active')) : @js(__('wizard.resource.inactive'))"></span>
                    </span>
                    <span class="wz-pill" x-show="summary.slug" x-cloak dir="ltr"><i data-lucide="link-2" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="summary.slug"></span></span>
                    <span class="wz-pill" x-show="summary.language" x-cloak><i data-lucide="languages" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="summary.language"></span></span>
                    @if ($resource)
                        <span class="wz-pill"><i data-lucide="history" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $resource->updated_at?->diffForHumans() }}</span>
                    @endif
                </x-slot:meta>
            </x-admin.wizard.hero>

            <x-admin.wizard.errors />

            <div class="wz-layout">
                <div class="min-w-0">

                    {{-- ═══════ IDENTITY ═══════ --}}
                    <x-admin.wizard.panel key="identity" :index="$stepNumber('identity')" :total="$totalSteps" icon="id-card"
                        :title="__('wizard.resource.steps.identity.label')" :description="__('wizard.resource.steps.identity.desc')">
                        <div class="wz-section">
                            <div class="wz-grid-2">
                                <x-admin.quran.field name="name" :label="__('quran.manage.name').' *'" :value="$resource?->name" required maxlength="200" dir="auto" />
                                <x-admin.quran.field name="slug" :label="__('quran.manage.slug')" :value="$resource?->slug" maxlength="120" dir="ltr" :help="__('wizard.resource.slug_help')" />
                                <x-admin.quran.field name="author_name" :label="__('quran.manage.author')" :value="$resource?->author_name" maxlength="200" dir="auto" />
                                <x-admin.quran.field name="language_iso" type="select" :label="__('quran.manage.language')" :value="$resource?->language_iso" :options="$languages" />
                                <x-admin.quran.field name="display_order" type="number" :label="__('quran.manage.order')" :value="$resource?->display_order ?? 0" min="0" />
                            </div>
                        </div>

                        <div class="wz-section">
                            <h3 class="wz-section-title"><i data-lucide="eye" aria-hidden="true"></i>{{ __('wizard.resource.visibility') }}</h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($toggles as $field => [$toggleLabel, $checked])
                                    <x-admin.quran.field :name="$field" type="switch" :label="$toggleLabel" :value="$checked" />
                                @endforeach
                            </div>
                        </div>
                    </x-admin.wizard.panel>

                    {{-- ═══════ RECITER & SOURCE ═══════ --}}
                    @if ($kind->isReciter())
                        <x-admin.wizard.panel key="source" :index="$stepNumber('source')" :total="$totalSteps" icon="mic"
                            :title="__('wizard.resource.steps.source.label')" :description="__('wizard.resource.steps.source.desc')">
                            <div class="wz-section">
                                <div class="wz-grid-2">
                                    <x-admin.quran.field name="style" type="select" :label="__('quran.manage.style')" :value="$resource?->style?->value"
                                        :options="collect(\App\Domain\Quran\Enums\RecitationStyle::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                                    <x-admin.quran.field name="qirat" :label="__('quran.manage.qirat')" :value="$resource?->qirat" maxlength="40" dir="auto" />
                                    <x-admin.quran.field name="external_source" type="select" :label="__('quran.manage.external_source')" :value="$resource?->external_source?->value"
                                        :options="collect(\App\Domain\Quran\Enums\ExternalSource::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                        :help="__('quran.manage.external_source_help')" />
                                    <x-admin.quran.field name="external_ref_id" :label="__('quran.manage.external_ref_id')" :value="$resource?->external_ref_id" maxlength="50" dir="ltr" />
                                    <x-admin.quran.field name="store_audio_locally" type="switch" :label="__('quran.manage.store_audio_locally')" :value="$resource?->store_audio_locally ?? false" />
                                </div>
                            </div>

                            @can('sync quran')
                                {{-- Preview what the source has for this id before saving. --}}
                                <div class="wz-section" data-wizard-ignore x-data="{
                                        busy: false, preview: null, error: null,
                                        async lookup() {
                                            const form = this.$root.closest('form');
                                            this.busy = true; this.preview = null; this.error = null;
                                            try {
                                                const response = await fetch(@js(route('admin.quran.resources.lookup')), {
                                                    method: 'POST',
                                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                                                    body: JSON.stringify({
                                                        external_source: form.querySelector('[name=external_source]').value,
                                                        external_ref_id: form.querySelector('[name=external_ref_id]').value,
                                                        kind: @js($kind->value),
                                                    }),
                                                });
                                                const json = await response.json();
                                                if (response.ok) { this.preview = json.data; } else { this.error = json.message || @js(__('quran.manage.lookup_failed')); }
                                            } catch (e) { this.error = @js(__('quran.manage.lookup_failed')); }
                                            this.busy = false;
                                        },
                                     }">
                                    <div class="flex"><x-admin.quran.button type="button" variant="secondary" icon="search" x-on:click="lookup()" x-bind:disabled="busy">{{ __('quran.manage.lookup') }}</x-admin.quran.button></div>
                                    <p class="wz-field-error" role="alert" x-show="error" x-text="error" x-cloak></p>
                                    <dl class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm" x-show="preview" x-cloak>
                                        <div><dt class="text-gray-500">{{ __('quran.manage.name') }}</dt><dd class="font-semibold" x-text="preview?.name"></dd></div>
                                        <div><dt class="text-gray-500">{{ __('quran.manage.style') }} / {{ __('quran.manage.qirat') }}</dt><dd x-text="[preview?.style, preview?.qirat].filter(Boolean).join(' · ') || '—'"></dd></div>
                                        <div><dt class="text-gray-500">{{ __('quran.manage.files_count') }}</dt><dd class="tabular-nums" x-text="preview?.files_count"></dd></div>
                                        <div><dt class="text-gray-500">{{ __('quran.manage.timestamps') }}</dt><dd x-text="preview?.has_timestamps ? @js(__('quran.manage.yes')) : @js(__('quran.manage.no'))"></dd></div>
                                        <template x-for="url in (preview?.preview_urls || [])" :key="url">
                                            <audio class="col-span-2 h-8 w-full" controls preload="none" :src="url"></audio>
                                        </template>
                                    </dl>
                                </div>
                            @endcan
                        </x-admin.wizard.panel>
                    @endif

                    {{-- ═══════ IMAGE & TRANSLATIONS ═══════ --}}
                    <x-admin.wizard.panel key="profile" :index="$stepNumber('profile')" :total="$totalSteps" icon="image"
                        :title="__('wizard.resource.steps.profile.label')" :description="__('wizard.resource.steps.profile.desc')">
                        <x-slot:actions><x-admin.wizard.langs :languages="$languageLabels" /></x-slot:actions>

                        <div class="grid gap-6 sm:grid-cols-[minmax(0,260px)_minmax(0,1fr)]">
                            <div data-wizard-image="image" data-wizard-field class="w-full max-w-[280px] sm:max-w-none">
                                <p class="wz-label">{{ __('quran.manage.image') }}</p>
                                <x-admin.image-upload name="image" idName="image_id" urlName="image_url" removeName="remove_image"
                                    aspectClass="aspect-square" :placeholderIcon="$kind->isReciter() ? 'user-round' : 'image-plus'" :currentImage="$currentImage" />
                                <p class="wz-help">{{ __('wizard.resource.image_help') }}</p>
                            </div>

                            <div class="min-w-0">
                                @foreach ($locales as $lang)
                                    <div x-show="lang === '{{ $lang }}'" @if (! $loop->first) x-cloak @endif class="grid gap-5">
                                        <x-admin.quran.field :name="'name_translations['.$lang.']'" :label="__('quran.manage.name').' ('.strtoupper($lang).')'"
                                            :value="$resource?->getTranslation('name', $lang, false)" maxlength="200" :dir="$dirFor($lang)"
                                            x-init="names['{{ $lang }}'] = $el.value" x-on:input="names['{{ $lang }}'] = $el.value" />
                                        <x-admin.quran.field :name="'description_translations['.$lang.']'" type="textarea" rows="9" :label="__('quran.manage.description').' ('.strtoupper($lang).')'"
                                            :value="$resource?->getTranslation('description', $lang, false)" maxlength="2000" :dir="$dirFor($lang)"
                                            :help="__('wizard.resource.description_help')" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-admin.wizard.panel>

                    {{-- ═══════ ABOUT ═══════ --}}
                    <x-admin.wizard.panel key="about" :index="$stepNumber('about')" :total="$totalSteps" icon="file-text"
                        :title="__('wizard.resource.steps.about.label')" :description="__('wizard.resource.steps.about.desc')">
                        <div class="wysiwyg-field">
                            <label class="wz-label">{{ __('quran.manage.info') }}</label>
                            <x-admin.form.wysiwyg name="info" :value="old('info', $resource?->info ?? '')" dir="auto" height="320px" />
                            @error('info') <p class="wz-field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </x-admin.wizard.panel>

                    <x-admin.wizard.command-bar :preview-url="$resource ? route('admin.quran.resources.show', $resource->id) : null" :total="$totalSteps" />
                </div>

                {{-- ═══════ RAIL ═══════ --}}
                <aside class="wz-rail" aria-label="{{ __('wizard.summary') }}">
                    <div class="wz-card wz-rail-card">
                        <p class="wz-rail-title"><i data-lucide="panel-top" aria-hidden="true"></i>{{ __('wizard.summary') }}</p>
                        <div class="wz-summary-media">
                            <template x-if="summary.image"><img :src="summary.image" alt=""></template>
                            <template x-if="!summary.image"><i data-lucide="{{ $kind->isReciter() ? 'user-round' : 'image' }}" class="w-8 h-8" aria-hidden="true"></i></template>
                        </div>
                        <p class="mt-3 font-bold leading-snug break-words" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="summary.name || @js(__('wizard.untitled'))"></p>
                        <dl class="wz-kv">
                            <div><dt>{{ __('wizard.resource.rail.status') }}</dt><dd x-text="summary.active ? @js(__('quran.manage.active')) : @js(__('wizard.resource.inactive'))"></dd></div>
                            <div><dt>{{ __('wizard.resource.rail.slug') }}</dt><dd dir="ltr" class="break-all" x-text="summary.slug || @js(__('wizard.not_set'))"></dd></div>
                            <div><dt>{{ __('wizard.resource.rail.language') }}</dt><dd x-text="summary.language || @js(__('wizard.not_set'))"></dd></div>
                            @if ($kind->isReciter())
                                <div><dt>{{ __('wizard.resource.rail.source') }}</dt><dd x-text="summary.source || @js(__('wizard.not_set'))"></dd></div>
                            @endif
                        </dl>
                    </div>

                    <div class="wz-card wz-rail-card">
                        <p class="wz-rail-title"><i data-lucide="languages" aria-hidden="true"></i>{{ __('wizard.languages') }}</p>
                        <ul class="grid gap-2">
                            @foreach ($languageLabels as $code => $langLabel)
                                <li class="flex items-center justify-between gap-2 text-sm">
                                    <button type="button" class="font-semibold hover:underline" :class="darkMode ? 'text-slate-200' : 'text-slate-700'" @click="lang = '{{ $code }}'; goTo('profile')">{{ $langLabel }}</button>
                                    <span class="wz-pill !min-h-6" :class="langFilled('{{ $code }}') ? 'is-success' : ''">
                                        <span class="wz-dot" aria-hidden="true"></span>
                                        <span x-text="langFilled('{{ $code }}') ? @js(__('wizard.ok')) : @js(__('wizard.not_set'))"></span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="wz-card wz-rail-card grid gap-2">
                        <a href="{{ $listUrl }}" class="wz-btn is-ghost w-full"><i data-lucide="list" aria-hidden="true"></i>{{ __('wizard.back_to_list') }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </form>

    <x-admin.media-picker-modal />
@endsection

@push('scripts')
<script>
    /** Resource wizard: live summary + step completeness on top of window.adminWizard. */
    function quranResourceForm(initial) {
        return {
            ...window.adminWizard({
                steps: initial.steps,
                initialStep: initial.step,
                isEdit: initial.isEdit,
                updateUrlTemplate: initial.updateUrl,
                editUrlTemplate: initial.editUrl,
                locales: initial.locales,
                langProp: 'lang',
                messages: initial.messages,
                stepComplete: (form, key) => form.isStepComplete(key),
                afterSave: (form, json) => {
                    // A blank or free-typed slug is generated/normalised server-side; pin the result
                    // so the next save neither regenerates it nor re-slugs what the editor sees.
                    const slug = form.field('slug');
                    if (slug && json.data?.slug) slug.value = json.data.slug;
                    form.refreshSummary();
                },
            }),

            lang: initial.locales[0] ?? 'ar',
            names: {},
            summary: { name: '', slug: '', language: '', source: '', image: '', active: true, tick: 0 },

            init() {
                this.stepErrors = initial.stepErrors ?? {};
                this.wizardInit();

                let queued = false;
                const refresh = () => {
                    if (queued) return;
                    queued = true;
                    setTimeout(() => { queued = false; this.refreshSummary(); }, 250);
                };
                const form = this.wizardForm();
                ['input', 'change', 'wizard:dirty'].forEach((type) => form?.addEventListener(type, refresh));
                this.$nextTick(() => this.refreshSummary());
            },

            field(name) { return this.wizardForm()?.elements.namedItem(name); },

            value(name) {
                const el = this.field(name);
                return (el && 'value' in el ? String(el.value ?? '') : '').trim();
            },

            selectedText(name) {
                const el = this.field(name);
                return el?.value ? (el.selectedOptions?.[0]?.textContent ?? '').trim() : '';
            },

            refreshSummary() {
                this.summary = {
                    name: this.value('name'),
                    slug: this.value('slug'),
                    language: this.selectedText('language_iso'),
                    source: [this.selectedText('external_source'), this.value('external_ref_id')].filter(Boolean).join(' · '),
                    image: this.$root.querySelector('[data-wizard-image="image"] img')?.getAttribute('src') || '',
                    active: !!this.wizardForm()?.querySelector('input[type="checkbox"][name="is_active"]')?.checked,
                    tick: this.summary.tick + 1,
                };
            },

            langFilled(code) { return (this.names[code] ?? '').trim() !== ''; },

            isStepComplete(key) {
                void this.summary.tick;
                switch (key) {
                    case 'identity': return this.summary.name !== '';
                    case 'source': return this.value('style') !== '' || this.value('external_source') !== '';
                    case 'profile': return this.summary.image !== '' || Object.keys(this.names).some((code) => this.langFilled(code));
                    case 'about': return this.value('info') !== '';
                    default: return !!this.visited[key];
                }
            },
        };
    }
</script>
@endpush
