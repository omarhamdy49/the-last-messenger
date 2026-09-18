@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="__('quran.manage.edit_verse', ['key' => $verse->verse_key])" icon="edit-2"
        :crumbs="[__('quran.manage.verses') => route('admin.quran.verses.index', ['chapter' => $verse->chapter_id]), $verse->verse_key => route('admin.quran.verses.show', $verse->id)]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ __('quran.manage.verse_lock_help') }}</div>

    <form method="POST" action="{{ route('admin.quran.verses.update', $verse->id) }}">
        @csrf
        @method('PUT')

        <x-admin.quran.panel :title="__('quran.manage.scripts')" icon="type">
            <div class="space-y-4">
                @foreach ($scriptFields as $field)
                    <x-admin.quran.field :name="$field" type="textarea" rows="2" :label="__('quran.manage.script_fields.'.$field)" :value="$verse->{$field}"
                        dir="rtl" class="font-quran text-xl leading-loose" />
                @endforeach
            </div>
        </x-admin.quran.panel>

        <x-admin.quran.panel :title="__('quran.manage.details')" icon="settings">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <x-admin.quran.field name="sajdah_type" type="select" :label="__('quran.manage.sajdah')" :value="$verse->sajdah_type"
                    :options="['recommended' => __('quran.manage.sajdah_recommended'), 'obligatory' => __('quran.manage.sajdah_obligatory')]" />
                <x-admin.quran.field name="sajdah_number" type="number" :label="__('quran.manage.sajdah_number')" :value="$verse->sajdah_number" min="1" max="15" />
                <x-admin.quran.field name="is_locked" type="checkbox" :label="__('quran.manage.locked')" :value="$verse->is_locked" />
            </div>
        </x-admin.quran.panel>

        <div class="flex justify-end gap-3">
            <x-admin.quran.button variant="secondary" :href="route('admin.quran.verses.show', $verse->id)">{{ __('quran.manage.cancel') }}</x-admin.quran.button>
            <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
        </div>
    </form>
@endsection
