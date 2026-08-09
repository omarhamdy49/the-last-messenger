@props([
    'name',
    'value' => '',
    'locale' => null,
    'dir' => null,
    'placeholder' => '',
    'height' => '220px',
])

{{-- Backwards-compatible alias; new screens should use x-admin.form.wysiwyg. --}}
<x-admin.form.wysiwyg
    :name="$name"
    :value="$value"
    :locale="$locale"
    :dir="$dir"
    :placeholder="$placeholder"
    :height="$height"
    toolbar="compact"
/>
