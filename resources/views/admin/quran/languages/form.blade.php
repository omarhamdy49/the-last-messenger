@extends('layouts.admin')

@section('page-header')
    <x-admin.quran.page-header :title="$language ? __('quran.manage.edit_language', ['name' => $language->name]) : __('quran.manage.add_language')" icon="languages"
        :crumbs="[__('quran.manage.languages') => route('admin.quran.languages.index')]" />
@endsection

@section('content')
    <x-admin.quran.flash />

    <form method="POST" action="{{ $language ? route('admin.quran.languages.update', $language->id) : route('admin.quran.languages.store') }}">
        @csrf
        @if ($language) @method('PUT') @endif
        <x-admin.quran.panel>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.quran.field name="name" :label="__('quran.manage.name')" :value="$language?->name" required maxlength="60" />
                <x-admin.quran.field name="native_name" :label="__('quran.manage.native_name')" :value="$language?->native_name" maxlength="60" dir="auto" />
                <x-admin.quran.field name="iso_code" :label="__('quran.manage.iso_code')" :value="$language?->iso_code" required maxlength="10" dir="ltr" :help="__('quran.manage.iso_code_help')" />
                <x-admin.quran.field name="direction" type="select" :blank="false" :label="__('quran.manage.direction')" :value="$language?->direction ?? 'ltr'"
                    :options="['ltr' => __('quran.manage.ltr'), 'rtl' => __('quran.manage.rtl')]" />
                <x-admin.quran.field name="is_active" type="checkbox" :label="__('quran.manage.active')" :value="$language?->is_active ?? true" />
            </div>
        </x-admin.quran.panel>
        <div class="flex justify-end gap-3">
            <x-admin.quran.button variant="secondary" :href="route('admin.quran.languages.index')">{{ __('quran.manage.cancel') }}</x-admin.quran.button>
            <x-admin.quran.button icon="save">{{ __('quran.manage.save') }}</x-admin.quran.button>
        </div>
    </form>
@endsection
