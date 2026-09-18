@props([
    'name',
    'value' => '',
    'locale' => null,
    'dir' => null,
    'height' => '220px',
    'placeholder' => '',
    'toolbar' => 'full',
    'modules' => false,
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
        {{-- Styles: resources/css/admin/rich-text.css (loaded with admin.css). --}}
    @endpush
@endonce

<div id="{{ $uid }}"
     class="wysiwyg-field"
     data-wysiwyg
     data-default-dir="{{ $direction }}"
     @wysiwyg-set.window="if ($event.detail.name === @js($name)) setHtml($event.detail.html)"
     @module-inline-insert.window="insertModuleEmbed($event.detail)"
     x-data="{
         editor: null,
         editorId: @js($uid),
         modules: null,
         sync: null,
         /**
          * Quill's getText() only sees typed characters, so a body that is nothing
          * but an embed (a verse card, an image) reads as empty and used to be
          * blanked on submit. Anything non-text in the DOM counts as content.
          */
         isEmpty() {
             if (! this.editor) { return true; }
             if (this.editor.getText().trim() !== '') { return false; }

             return this.editor.root.querySelector('.module-embed, img, iframe, video') === null;
         },
         setHtml(html) {
             if (! this.editor) { return; }
             this.editor.setContents(this.editor.clipboard.convert({ html: html }));
             // Injected HTML carries no per-line direction, so re-apply the field's.
             window.AdminWysiwyg.applyDefaultDirection(this.editor, @js($direction));
             this.$refs.input.value = this.isEmpty() ? '' : this.editor.root.innerHTML;
         },
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

             const sync = (delta, oldDelta, source) => {
                 input.value = this.isEmpty() ? '' : this.editor.root.innerHTML;
                 // Tell an enclosing form wizard about real edits (not the initial load).
                 if (source === 'user') { input.dispatchEvent(new CustomEvent('wizard:dirty', { bubbles: true })); }
             };
             this.editor.on('text-change', sync);
             this.sync = sync;

             const form = input.closest('form');
             if (form) { form.addEventListener('submit', sync); }

             if (@js((bool) $modules)) {
                 this.modules = window.AdminWysiwygModules.attachToolbar(this.editor, {
                     id: this.editorId,
                     locale: @js($locale),
                 });
             }
         },

         /** Insert whatever the shared picker produced, at the caret saved on toolbar click. */
         insertModuleEmbed(detail) {
             if (! this.editor || ! this.modules || detail.quillBlockId !== this.editorId) { return; }
             window.AdminWysiwygModules.insert(
                 this.editor, this.modules.takeRange(), detail.moduleType, detail.data, @js($direction === 'rtl' ? 'ar' : 'en')
             );
             this.sync && this.sync(null, null, 'user');
             this.editor.focus();
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

@if ($modules)
    {{-- Shared cross-module picker; @once inside means one instance per page. --}}
    <x-admin.module-selector-modal />
@endif
