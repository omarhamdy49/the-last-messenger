@props([
    'name',
    'value' => '',
    'locale' => null,
    'dir' => null,
    'height' => '220px',
    'placeholder' => '',
    'toolbar' => 'full',
])

@php
    $rtlLocales = ['ar', 'ur', 'fa', 'he'];
    $direction = $dir
        ?? ($locale
            ? (in_array(strtolower($locale), $rtlLocales, true) ? 'rtl' : 'ltr')
            : (in_array(app()->getLocale(), $rtlLocales, true) ? 'rtl' : 'ltr'));
    $uid = 'wysiwyg_'.\Illuminate\Support\Str::random(10);
@endphp

{{--
    Canonical admin rich-text component. Quill is bundled locally and the
    hidden textarea preserves normal validation, old() values, and form posts.
    The direction defaults to the field locale (or the app locale) while the
    toolbar always lets an editor toggle RTL/LTR for selected paragraphs.
--}}
@once
    @push('styles')
        @vite('resources/js/admin/rich-text.js')
        <style>
            .wysiwyg-field .ql-toolbar.ql-snow { border-radius: 0.5rem 0.5rem 0 0; }
            .wysiwyg-field .ql-container.ql-snow {
                border-radius: 0 0 0.5rem 0.5rem;
                font-family: 'Cairo', sans-serif;
                font-size: 1rem;
            }
            .wysiwyg-field[data-default-dir="rtl"] .ql-editor.ql-blank::before {
                direction: rtl;
                text-align: right;
                left: 15px;
                right: 15px;
            }
            .dark .wysiwyg-field .ql-toolbar.ql-snow,
            .dark .wysiwyg-field .ql-container.ql-snow { border-color: #374151; background-color: #111827; }
            .dark .wysiwyg-field .ql-editor { color: #f3f4f6; }
            .dark .wysiwyg-field .ql-editor.ql-blank::before { color: #9ca3af; }
            .dark .wysiwyg-field .ql-snow .ql-stroke { stroke: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-fill { fill: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-picker { color: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-picker-options { background-color: #1f2937; border-color: #374151; }
        </style>
    @endpush
@endonce

<div id="{{ $uid }}"
     class="wysiwyg-field"
     data-wysiwyg
     data-default-dir="{{ $direction }}"
     x-data="{
         editor: null,
         initEditor() {
             const editorEl = this.$refs.editor;
             const input = this.$refs.input;

             if (editorEl.dataset.wysiwygReady) { return; }
             editorEl.dataset.wysiwygReady = '1';

             this.editor = window.AdminWysiwyg.create(editorEl, {
                 value: input.value,
                 locale: @js($locale),
                 direction: @js($direction),
                 placeholder: @js($placeholder),
                 toolbar: @js($toolbar),
             });

             const sync = () => {
                 input.value = this.editor.getText().trim() === '' ? '' : this.editor.root.innerHTML;
             };
             this.editor.on('text-change', sync);

             const form = input.closest('form');
             if (form) { form.addEventListener('submit', sync); }
         },
         init() {
             if (window.AdminWysiwyg) {
                 this.initEditor();
             } else {
                 window.addEventListener('rich-text:ready', () => this.initEditor(), { once: true });
             }
         }
     }">
    <div x-ref="editor" style="min-height: {{ $height }}"></div>
    <textarea x-ref="input"
              name="{{ $name }}"
              class="hidden"
              aria-hidden="true">{{ $value }}</textarea>
</div>
