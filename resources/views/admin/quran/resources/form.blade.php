@extends('layouts.admin')

@php
    $locales = config('translatable.locales', ['ar', 'en', 'fr', 'tr', 'ur']);
    $title = $resource ? __('quran.manage.edit_resource', ['name' => $resource->name]) : __('quran.manage.add_resource', ['kind' => $kind->label()]);
@endphp

@section('page-header')
    <x-admin.quran.page-header :title="$title" icon="library"
        :crumbs="[$kind->label() => route('admin.quran.resources.index', $kind->routeSegment())]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    @if ($resource?->isFromQf())
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">{{ __('quran.manage.qf_resource_edit_help', ['id' => $resource->qf_id]) }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $resource ? route('admin.quran.resources.update', $resource->id) : route('admin.quran.resources.store', $kind->routeSegment()) }}">
        @csrf
        @if ($resource) @method('PUT') @endif

        <x-admin.quran.panel :title="__('quran.manage.details')" icon="info">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.quran.field name="name" :label="__('quran.manage.name')" :value="$resource?->name" required maxlength="200" dir="auto" />
                <x-admin.quran.field name="slug" :label="__('quran.manage.slug')" :value="$resource?->slug" required maxlength="120" dir="ltr" :help="__('quran.manage.slug_help')" />
                <x-admin.quran.field name="author_name" :label="__('quran.manage.author')" :value="$resource?->author_name" maxlength="200" dir="auto" />
                <x-admin.quran.field name="language_iso" type="select" :label="__('quran.manage.language')" :value="$resource?->language_iso" :options="$languages" />
                <x-admin.quran.field name="display_order" type="number" :label="__('quran.manage.order')" :value="$resource?->display_order ?? 0" min="0" />
                <div>
                    <x-admin.quran.field name="image" type="file" :label="__('quran.manage.image')" accept="image/*" />
                    @if ($resource?->image)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ Storage::disk('public')->url($resource->image) }}" alt="" class="h-12 rounded-lg">
                            <x-admin.quran.field name="remove_image" type="checkbox" :label="__('quran.manage.remove_image')" :value="false" />
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-6">
                <x-admin.quran.field name="is_active" type="checkbox" :label="__('quran.manage.active')" :value="$resource?->is_active ?? true" />
                <x-admin.quran.field name="is_default" type="checkbox" :label="__('quran.manage.default')" :value="$resource?->is_default ?? false" />
                @if ($resource?->qf_id !== null)
                    <x-admin.quran.field name="sync_enabled" type="checkbox" :label="__('quran.manage.sync_enabled')" :value="$resource->sync_enabled" />
                @endif
            </div>
        </x-admin.quran.panel>

        @if ($kind->isReciter())
            <x-admin.quran.panel :title="__('quran.manage.reciter')" icon="mic">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-admin.quran.field name="style" type="select" :label="__('quran.manage.style')" :value="$resource?->style?->value"
                        :options="collect(\App\Domain\Quran\Enums\RecitationStyle::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                    <x-admin.quran.field name="qirat" :label="__('quran.manage.qirat')" :value="$resource?->qirat" maxlength="40" dir="auto" />
                    <x-admin.quran.field name="external_source" type="select" :label="__('quran.manage.external_source')" :value="$resource?->external_source?->value"
                        :options="collect(\App\Domain\Quran\Enums\ExternalSource::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                        :help="__('quran.manage.external_source_help')" />
                    <x-admin.quran.field name="external_ref_id" :label="__('quran.manage.external_ref_id')" :value="$resource?->external_ref_id" maxlength="50" dir="ltr" />
                    <x-admin.quran.field name="store_audio_locally" type="checkbox" :label="__('quran.manage.store_audio_locally')" :value="$resource?->store_audio_locally ?? false" />
                </div>

                @can('sync quran')
                    {{-- Preview what the source has for this id before saving. --}}
                    <div class="mt-4" x-data="{
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
                        <x-admin.quran.button type="button" variant="secondary" icon="search" x-on:click="lookup()" x-bind:disabled="busy">{{ __('quran.manage.lookup') }}</x-admin.quran.button>
                        <p class="mt-2 text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>
                        <dl class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm" x-show="preview" x-cloak>
                            <div><dt class="text-gray-500">{{ __('quran.manage.name') }}</dt><dd class="font-semibold" x-text="preview?.name"></dd></div>
                            <div><dt class="text-gray-500">{{ __('quran.manage.style') }} / {{ __('quran.manage.qirat') }}</dt><dd x-text="[preview?.style, preview?.qirat].filter(Boolean).join(' · ') || '—'"></dd></div>
                            <div><dt class="text-gray-500">{{ __('quran.manage.files_count') }}</dt><dd x-text="preview?.files_count"></dd></div>
                            <div><dt class="text-gray-500">{{ __('quran.manage.timestamps') }}</dt><dd x-text="preview?.has_timestamps ? @js(__('quran.manage.yes')) : @js(__('quran.manage.no'))"></dd></div>
                            <template x-for="url in (preview?.preview_urls || [])" :key="url">
                                <audio class="col-span-2 h-8 w-full" controls preload="none" :src="url"></audio>
                            </template>
                        </dl>
                    </div>
                @endcan
            </x-admin.quran.panel>
        @endif

        <x-admin.quran.panel :title="__('quran.manage.localized_names')" icon="languages">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($locales as $locale)
                    <div class="space-y-2">
                        <x-admin.quran.field :name="'name_translations['.$locale.']'" :label="__('quran.manage.name').' ('.$locale.')'"
                            :value="$resource?->getTranslation('name', $locale, false)" maxlength="200" :dir="in_array($locale, ['ar', 'ur'], true) ? 'rtl' : 'ltr'" />
                        <x-admin.quran.field :name="'description_translations['.$locale.']'" type="textarea" rows="2" :label="__('quran.manage.description').' ('.$locale.')'"
                            :value="$resource?->getTranslation('description', $locale, false)" maxlength="2000" :dir="in_array($locale, ['ar', 'ur'], true) ? 'rtl' : 'ltr'" />
                    </div>
                @endforeach
            </div>
        </x-admin.quran.panel>

        <x-admin.quran.panel :title="__('quran.manage.info')" icon="file-text">
            <x-admin.quran.field name="info" type="textarea" rows="6" :label="__('quran.manage.info_html')" :value="$resource?->info" dir="auto" />
        </x-admin.quran.panel>

        <div class="flex justify-end gap-3">
            <x-admin.quran.button variant="secondary" :href="$resource ? route('admin.quran.resources.show', $resource->id) : route('admin.quran.resources.index', $kind->routeSegment())">{{ __('quran.manage.cancel') }}</x-admin.quran.button>
            <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
        </div>
    </form>
@endsection
