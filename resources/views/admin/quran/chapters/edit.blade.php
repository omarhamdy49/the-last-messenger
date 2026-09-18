@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.edit_chapter', ['name' => $chapter->name_arabic])" icon="edit-2"
        :crumbs="[__('quran.manage.chapters') => route('admin.quran.chapters.index'), $chapter->name_arabic => route('admin.quran.chapters.show', $chapter->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <form method="POST" action="{{ route('admin.quran.chapters.update', $chapter->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <x-admin.quran.panel :title="__('quran.manage.localized_names')" icon="languages">
            <p class="mb-4 text-sm text-gray-500">{{ __('quran.manage.localized_names_help') }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach (config('translatable.locales', ['ar', 'en', 'fr', 'tr', 'ur']) as $locale)
                    <x-admin.quran.field :name="'name['.$locale.']'" :label="__('quran.manage.name').' ('.$locale.')'"
                        :value="$chapter->getTranslation('name', $locale) ?? ($locale === 'ar' ? $chapter->name_arabic : null)"
                        :dir="in_array($locale, ['ar', 'ur'], true) ? 'rtl' : 'ltr'" maxlength="150" />
                @endforeach
            </div>
        </x-admin.quran.panel>

        <x-admin.quran.panel :title="__('quran.manage.details')" icon="settings">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.place-picker name="revelation_location_id" :selected="old('revelation_location_id', $chapter->revelation_location_id)"
                    :selected-label="$chapter->location?->name()" :label="__('locations.singular')" />
                <div>
                    <x-admin.quran.field name="cover" type="file" :label="__('quran.manage.cover')" accept="image/*" />
                    @if ($chapter->cover_image)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ Storage::disk('public')->url($chapter->cover_image) }}" alt="" class="h-12 rounded-lg">
                            <x-admin.quran.field name="remove_cover" type="checkbox" :label="__('quran.manage.remove_cover')" :value="false" />
                        </div>
                    @endif
                </div>
                <x-admin.quran.field name="is_published" type="checkbox" :label="__('quran.manage.published')" :value="$chapter->is_published" />
            </div>
        </x-admin.quran.panel>

        <div class="flex justify-end gap-3">
            <x-admin.quran.button variant="secondary" :href="route('admin.quran.chapters.show', $chapter->id)">{{ __('quran.manage.cancel') }}</x-admin.quran.button>
            <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
        </div>
    </form>
@endsection
