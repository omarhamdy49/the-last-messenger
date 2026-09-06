{{--
    WYSIWYG Block Component
    Rich text editor block for article content
--}}

@props([
    'locale' => 'ar'
])

<div x-show="block.type === 'wysiwyg'" class="p-0">
    <div :id="'block_editor_' + block.id" class="quill-editor block-quill"
         x-init="$nextTick(() => initBlockQuill(block.id, '{{ $locale }}'))"></div>
</div>
