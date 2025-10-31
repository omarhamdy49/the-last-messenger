# TinyMCE Quick Reference

## Component Usage

### Basic Editor
```blade
<x-admin.editor
    name="content"
    :value="$article->content"
    language="ar"
    :required="true"
/>
```

### Multi-Language Form
```blade
<div x-data="{ activeLanguage: 'ar' }">
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
            <x-admin.editor
                name="content[{{ $lang }}]"
                :value="old('content.' . $lang, '')"
                language="{{ $lang }}"
                :height="500"
                :required="$lang === 'ar'"
            />
        </div>
    @endforeach
</div>
```

## Props Reference

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| name | string | required | Form field name |
| value | string | '' | Initial content |
| language | string | 'ar' | ar, en, fr, tr, ur |
| height | int | 500 | Editor height (px) |
| required | bool | false | Required field |
| disabled | bool | false | Disabled state |
| placeholder | string | '' | Placeholder text |
| id | string | auto | Custom editor ID |

## Form Submission

```javascript
function articleForm() {
    return {
        handleSubmit(event) {
            // Save all editors to textareas
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            // Validate (example)
            const editor = tinymce.get('editor_id');
            const wordCount = editor.plugins.wordcount.body.getWordCount();
            if (wordCount < 10) {
                alert('Minimum 10 words required');
                event.preventDefault();
                return false;
            }

            return true;
        }
    };
}
```

## JavaScript API

```javascript
// Get editor instance
const editor = tinymce.get('editor_id');

// Get content
const html = editor.getContent();
const text = editor.getContent({ format: 'text' });

// Set content
editor.setContent('<p>New content</p>');

// Get word count
const wordCount = editor.plugins.wordcount.body.getWordCount();

// Focus editor
editor.focus();

// Save to textarea
editor.save();

// Remove editor
editor.remove();

// Save all editors
tinymce.triggerSave();

// Remove all editors
tinymce.remove();
```

## Configuration Module

```javascript
import TinyMCEConfig from './tinymce-config.js';

// Get language config
const config = TinyMCEConfig.getConfigForLanguage('ar');

// Get dark mode config
const darkConfig = TinyMCEConfig.getDarkModeConfig(true);

// Validate editor
const result = TinyMCEConfig.validateEditorContent('editor_id', {
    required: true,
    minWords: 10
});

// Extract all content
const allContent = TinyMCEConfig.extractAllEditorContent();
```

## Supported Languages

- **ar**: Arabic (RTL)
- **en**: English (LTR)
- **fr**: French (LTR)
- **tr**: Turkish (LTR)
- **ur**: Urdu (RTL)

## Common Issues

### Editor Not Loading
- Check internet connection (CDN)
- Verify no script blockers
- Check browser console for errors

### Content Not Saving
```javascript
// Always call before submission
tinymce.triggerSave();
```

### Multiple Editors
```blade
<!-- Use unique names -->
<x-admin.editor name="content[ar]" language="ar" />
<x-admin.editor name="content[en]" language="en" />
```

## Toolbar Features

- **Format**: Bold, Italic, Underline, Strikethrough
- **Headings**: H1, H2, H3, H4
- **Alignment**: Left, Center, Right, Justify
- **Lists**: Bullets, Numbers, Indent, Outdent
- **Insert**: Link, Image, Media, Table
- **Colors**: Text color, Background color
- **Direction**: LTR/RTL toggle
- **Tools**: Find/Replace, Code view, Fullscreen

## Keyboard Shortcuts

- **Ctrl/Cmd + B**: Bold
- **Ctrl/Cmd + I**: Italic
- **Ctrl/Cmd + U**: Underline
- **Ctrl/Cmd + K**: Insert link
- **Ctrl/Cmd + Z**: Undo
- **Ctrl/Cmd + Y**: Redo
- **Ctrl/Cmd + F**: Find
- **Alt + F11**: Fullscreen

## Files

- Component: `resources/views/components/admin/editor.blade.php`
- Config: `resources/js/admin/tinymce-config.js`
- Example: `resources/views/admin/articles/create.blade.php`
- Docs: `TINYMCE_INTEGRATION_GUIDE.md`

## Best Practices

1. Always call `tinymce.triggerSave()` before form submission
2. Use unique IDs for multiple editors
3. Validate content server-side
4. Remove editors before navigation (`tinymce.remove()`)
5. Handle loading states gracefully
6. Test in all supported browsers
7. Sanitize HTML on backend

## Support

- Full Documentation: `TINYMCE_INTEGRATION_GUIDE.md`
- TinyMCE Docs: https://www.tiny.cloud/docs/
- Alpine.js Docs: https://alpinejs.dev/

---

**Version**: 1.0.0
**Last Updated**: 2024-11-01
