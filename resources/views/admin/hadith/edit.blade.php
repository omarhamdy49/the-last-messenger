@extends('layouts.admin')

@php
    $gradingRows = old('gradings', $hadith->gradings->map(fn ($grading) => [
        'id' => $grading->id,
        'scholar_id' => $grading->scholar_id,
        'grade' => $grading->grade->value,
        'scope' => $grading->scope->value,
        'part_label' => $grading->part_label,
        'authority_rank' => $grading->authority_rank,
        'raw_grade' => $grading->raw_grade,
        'locale' => $grading->locale,
        'notes' => $grading->notes,
        'source_reference' => $grading->source_reference,
    ])->values()->all());
@endphp

@section('page-header')
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.hadith.index') }}" class="hover:text-accent">{{ __('hadith.hadiths') }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4 rtl:rotate-180"></i>
            <span>{{ $hadith->collection->code }}:{{ $hadith->hadith_number }}</span>
        </div>
        <h1 class="flex items-center gap-3 text-3xl font-bold gradient-text">
            <span class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] p-3 shadow-lg"><i data-lucide="square-pen" class="h-7 w-7 text-white"></i></span>
            {{ __('admin.edit_hadith') }}
        </h1>
    </div>
    <a href="{{ route('admin.hadith.index') }}" class="inline-flex items-center gap-2 self-start rounded-xl border-2 px-4 py-2.5 font-semibold"
       :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-900'">
        <i data-lucide="arrow-left" class="h-5 w-5 rtl:rotate-180"></i>{{ __('admin.back') }}
    </a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.hadith.update', $hadith) }}" class="space-y-6">
    @csrf
    @method('PUT')
    <input type="hidden" name="updated_at" value="{{ $hadith->updated_at->toISOString() }}">

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                <p class="text-sm leading-6">{{ __('hadith.admin.import_overwrite_warning') }}</p>
            </div>

            <x-admin.form-section :title="__('hadith.admin.localized_texts')" :description="__('hadith.admin.localized_texts_help')" icon="languages">
                <x-admin.language-tabs :languages="$locales" :errors="$errors" :show-copy-button="false">
                    @foreach($locales as $locale)
                        @php($text = $texts->get($locale))
                        <x-admin.language-tab-panel :language="$locale" :data-language="$locale">
                            <div>
                                <label class="mb-2 block text-sm font-semibold">{{ __('hadith.text') }} @if($locale === 'ar')<span class="text-red-500">*</span>@endif</label>
                                <textarea name="texts[{{ $locale }}][body]" rows="10" @required($locale === 'ar')
                                          class="w-full rounded-xl border px-4 py-3 text-base leading-8 focus:border-transparent focus:ring-2 focus:ring-accent {{ $locale === 'ar' ? 'font-amiri text-xl' : '' }}"
                                          :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">{{ old("texts.$locale.body", $text?->body) }}</textarea>
                                @error("texts.$locale.body")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.narrated_by') }}</label>
                                    <textarea name="texts[{{ $locale }}][narrated_by]" rows="3" class="w-full rounded-xl border px-4 py-3" :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("texts.$locale.narrated_by", $text?->narrated_by) }}</textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.translator') }}</label>
                                    <input name="texts[{{ $locale }}][translator]" value="{{ old("texts.$locale.translator", $text?->translator) }}" maxlength="255"
                                           class="w-full rounded-xl border px-4 py-3" :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.chain') }}</label>
                                    <textarea name="texts[{{ $locale }}][sanad]" rows="4" class="w-full rounded-xl border px-4 py-3" :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("texts.$locale.sanad", $text?->sanad) }}</textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.matn') }}</label>
                                    <textarea name="texts[{{ $locale }}][matn]" rows="4" class="w-full rounded-xl border px-4 py-3" :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("texts.$locale.matn", $text?->matn) }}</textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.source_url') }}</label>
                                    <input type="url" name="texts[{{ $locale }}][source_url]" value="{{ old("texts.$locale.source_url", $text?->source_url) }}" maxlength="500" dir="ltr"
                                           class="w-full rounded-xl border px-4 py-3" :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">
                                    @error("texts.$locale.source_url")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>
            </x-admin.form-section>

            <x-admin.form-section :title="__('hadith.admin.grading_claims')" :description="__('hadith.admin.grading_help')" icon="badge-check">
                <div x-data="{
                    rows: {{ Js::from($gradingRows) }},
                    add() {
                        this.rows.push({ id: null, scholar_id: '', grade: 'unknown', scope: 'hadith', part_label: '', authority_rank: 100, raw_grade: '', locale: '', notes: '', source_reference: '' });
                        this.$nextTick(() => lucide.createIcons());
                    }
                }" class="space-y-4">
                    <template x-for="(row, index) in rows" :key="row.id || `new-${index}`">
                        <div class="rounded-xl border p-4" :class="darkMode ? 'border-gray-700 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
                            <input type="hidden" :name="`gradings[${index}][id]`" x-model="row.id">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="font-bold" x-text="`{{ __('hadith.admin.claim') }} ${index + 1}`"></h3>
                                <button type="button" @click="rows.splice(index, 1)" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>{{ __('hadith.admin.remove') }}
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.grade') }}</label>
                                    <select :name="`gradings[${index}][grade]`" x-model="row.grade" required class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                        @foreach($grades as $grade)<option value="{{ $grade->value }}">{{ $grade->label() }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.scope') }}</label>
                                    <select :name="`gradings[${index}][scope]`" x-model="row.scope" required class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                        @foreach($scopes as $scope)<option value="{{ $scope->value }}">{{ __('hadith.grade_scope_'.$scope->value) }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.graded_by') }}</label>
                                    <select :name="`gradings[${index}][scholar_id]`" x-model="row.scholar_id" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                        <option value="">{{ __('hadith.admin.unspecified') }}</option>
                                        @foreach($scholars as $scholar)<option value="{{ $scholar->id }}">{{ $scholar->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.authority_rank') }}</label>
                                    <input type="number" min="0" max="255" :name="`gradings[${index}][authority_rank]`" x-model="row.authority_rank" required class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.raw_grade') }} <span class="text-red-500">*</span></label>
                                    <textarea rows="2" :name="`gradings[${index}][raw_grade]`" x-model="row.raw_grade" required class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'"></textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.part_label') }}</label>
                                    <input :name="`gradings[${index}][part_label]`" x-model="row.part_label" maxlength="100" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.locale') }}</label>
                                    <select :name="`gradings[${index}][locale]`" x-model="row.locale" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                        <option value="">{{ __('hadith.admin.unspecified') }}</option>
                                        @foreach($locales as $locale)<option value="{{ $locale }}">{{ strtoupper($locale) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.notes') }}</label>
                                    <textarea rows="2" :name="`gradings[${index}][notes]`" x-model="row.notes" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'"></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-bold">{{ __('hadith.admin.source_reference') }}</label>
                                    <input :name="`gradings[${index}][source_reference]`" x-model="row.source_reference" maxlength="255" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="rows.length === 0" class="rounded-xl border border-dashed p-6 text-center text-sm text-gray-500">{{ __('hadith.no_gradings') }}</p>
                    @error('gradings')<p class="text-sm text-red-500">{{ $message }}</p>@enderror
                    <button type="button" @click="add()" class="inline-flex items-center gap-2 rounded-xl border-2 border-[#0056b3] px-4 py-2.5 font-bold text-[#0056b3] hover:bg-blue-50 dark:border-[#64CCC5] dark:text-[#64CCC5] dark:hover:bg-gray-800">
                        <i data-lucide="plus" class="h-5 w-5"></i>{{ __('hadith.admin.add_claim') }}
                    </button>
                </div>
            </x-admin.form-section>
        </div>

        <aside class="space-y-6">
            <x-admin.form-section :title="__('hadith.admin.publication')" icon="eye">
                <x-admin.form.checkbox name="is_published" :label="__('hadith.admin.published')" :checked="$hadith->is_published" :description="__('hadith.admin.hadith_publication_help')" />
            </x-admin.form-section>
            <x-admin.form-section :title="__('hadith.admin.canonical_identity')" :description="__('hadith.admin.canonical_read_only')" icon="lock">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.collection') }}</dt><dd class="text-end font-bold">{{ $hadith->collection->name }} ({{ $hadith->collection->code }})</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.book') }}</dt><dd class="text-end font-bold">#{{ $hadith->book->book_number }} {{ $hadith->book->title }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.chapter') }}</dt><dd class="text-end font-bold">{{ $hadith->chapter ? '#'.$hadith->chapter->chapter_number.' '.$hadith->chapter->title : '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('admin.hadith_number') }}</dt><dd class="font-bold">{{ $hadith->hadith_number }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.in_book_number') }}</dt><dd class="font-bold">{{ $hadith->in_book_number ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.urn') }}</dt><dd class="font-bold">{{ $hadith->sunnah_urn ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.derived_grade') }}</dt><dd class="font-bold">{{ $hadith->display_grade?->label() ?? '—' }}</dd></div>
                </dl>
            </x-admin.form-section>
            @error('updated_at')<div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $message }}</div>@enderror
            @if($errors->any())<div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700">{{ __('hadith.admin.fix_errors') }}</div>@endif
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-6 py-3 font-bold text-white shadow-lg">
                <i data-lucide="save" class="h-5 w-5"></i>{{ __('admin.save_changes') }}
            </button>
        </aside>
    </div>
</form>
@endsection
