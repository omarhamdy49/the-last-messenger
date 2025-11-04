{{--
    WYSIWYG Editor Component (CKEditor 5)
    Usage: <x-admin.wysiwyg-editor name="content" :value="$content" language="ar" :height="500" />
--}}

@props([
    'name' => 'content',
    'value' => '',
    'language' => 'ar',
    'height' => 400,
    'required' => false,
    'id' => null,
    'placeholder' => ''
])

@php
    $editorId = $id ?? 'editor_' . uniqid();
    $isRTL = in_array($language, ['ar', 'ur', 'he']);
    $placeholderText = $placeholder ?: ($isRTL ? 'اكتب المحتوى هنا...' : 'Write content here...');
@endphp

<div class="ckeditor-wrapper" data-editor-id="{{ $editorId }}">
    {{-- Editor Container --}}
    <div id="{{ $editorId }}"
         class="ckeditor-instance"
         data-language="{{ $language }}"
         data-height="{{ $height }}"
         data-rtl="{{ $isRTL ? 'true' : 'false' }}"
         data-placeholder="{{ $placeholderText }}"
         style="min-height: {{ $height }}px;"
         dir="{{ $isRTL ? 'rtl' : 'ltr' }}">
        {!! $value !!}
    </div>

    {{-- Hidden Input for Form Submission --}}
    <input type="hidden"
           name="{{ $name }}"
           id="{{ $editorId }}_input"
           value="{{ htmlspecialchars($value, ENT_QUOTES, 'UTF-8') }}"
           {{ $required ? 'required' : '' }}>

    {{-- Character Counter --}}
    <div class="mt-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
        <span id="{{ $editorId }}_count" class="text-xs">0 characters</span>
        @if($required)
            <span class="text-xs text-red-500">* Required field</span>
        @endif
    </div>
</div>

