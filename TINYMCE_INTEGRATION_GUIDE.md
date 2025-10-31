# TinyMCE 6 WYSIWYG Editor Integration Guide

## Overview

This guide provides complete documentation for the TinyMCE 6 rich text editor integration in The Last Messenger admin dashboard. The integration includes multi-language support, RTL handling, dark/light mode themes, and seamless integration with Laravel Blade and Alpine.js.

## Table of Contents

1. [Features](#features)
2. [Architecture](#architecture)
3. [Quick Start](#quick-start)
4. [Component Usage](#component-usage)
5. [Configuration](#configuration)
6. [Multi-Language Support](#multi-language-support)
7. [Form Integration](#form-integration)
8. [Advanced Usage](#advanced-usage)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Features

### Core Features
- **Rich Text Editing**: Full WYSIWYG editor with formatting options
- **Multi-Language**: Support for Arabic, English, French, Turkish, and Urdu
- **RTL/LTR Support**: Automatic text direction based on language
- **Dark/Light Mode**: Theme integration with admin dashboard
- **Image Upload**: Built-in image handling and media management
- **Word Count**: Real-time word and character counting
- **Validation**: Client-side content validation
- **Responsive**: Mobile-friendly toolbar and interface

### Toolbar Features
- Text formatting (bold, italic, underline, strikethrough)
- Headings (H1-H4)
- Text alignment (left, center, right, justify)
- Lists (bulleted, numbered, indent/outdent)
- Links and anchors
- Images and media
- Tables
- Text direction (LTR/RTL toggle)
- Colors (foreground and background)
- Find and replace
- Code view
- Fullscreen mode

### Technical Features
- **CDN Integration**: No build process required
- **Alpine.js Integration**: Reactive state management
- **Form Compatibility**: Standard HTML form submission
- **Error Handling**: Graceful fallback on load failure
- **Loading States**: Visual feedback during initialization
- **Performance**: Lazy loading and optimized rendering

---

## Architecture

### File Structure

```
resources/
├── views/
│   ├── components/
│   │   └── admin/
│   │       └── editor.blade.php          # Reusable editor component
│   └── admin/
│       └── articles/
│           ├── create.blade.php          # Article creation (with TinyMCE)
│           └── edit.blade.php            # Article editing (with TinyMCE)
└── js/
    └── admin/
        └── tinymce-config.js             # Configuration module

docs/
└── TINYMCE_INTEGRATION_GUIDE.md          # This file
```

### Component Architecture

```
┌─────────────────────────────────────┐
│   Blade Component (editor.blade.php)│
│   ┌──────────────────────────────┐  │
│   │  Alpine.js State Management  │  │
│   │  - editorInstance            │  │
│   │  - editorReady               │  │
│   │  - wordCount                 │  │
│   │  - validation                │  │
│   └──────────────────────────────┘  │
│   ┌──────────────────────────────┐  │
│   │  TinyMCE Instance            │  │
│   │  - Configuration             │  │
│   │  - Event Handlers            │  │
│   │  - Content Management        │  │
│   └──────────────────────────────┘  │
│   ┌──────────────────────────────┐  │
│   │  HTML Textarea               │  │
│   │  (Form submission target)    │  │
│   └──────────────────────────────┘  │
└─────────────────────────────────────┘
```

---

## Quick Start

### 1. Basic Usage

Include the editor component in any Blade template:

```blade
<x-admin.editor
    name="content"
    :value="$article->content ?? ''"
    language="ar"
    :required="true"
/>
```

### 2. Multi-Language Form

For multi-language forms with tabs:

```blade
<div x-data="{ activeLanguage: 'ar' }">
    <!-- Language Tabs -->
    <div class="flex gap-2 mb-4">
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <button type="button" @click="activeLanguage = '{{ $lang }}'">
                {{ $lang }}
            </button>
        @endforeach
    </div>

    <!-- Editors -->
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

### 3. Form Submission

Handle form submission with TinyMCE content extraction:

```javascript
function articleForm() {
    return {
        handleSubmit(event) {
            // Extract content from all TinyMCE editors
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            // Form validation logic here
            return true; // Allow submission
        }
    };
}
```

---

## Component Usage

### Props

| Prop | Type | Default | Required | Description |
|------|------|---------|----------|-------------|
| `name` | string | - | Yes | Field name for form submission |
| `value` | string | '' | No | Initial content value |
| `language` | string | 'ar' | No | Content language (ar, en, fr, tr, ur) |
| `height` | integer | 500 | No | Editor height in pixels |
| `required` | boolean | false | No | Whether field is required |
| `disabled` | boolean | false | No | Whether editor is disabled |
| `placeholder` | string | '' | No | Placeholder text |
| `id` | string | auto | No | Custom ID for the editor |

### Examples

#### Basic Editor
```blade
<x-admin.editor
    name="description"
    placeholder="Enter description..."
/>
```

#### Arabic Content (Required)
```blade
<x-admin.editor
    name="content[ar]"
    :value="$article->content['ar']"
    language="ar"
    :required="true"
    placeholder="اكتب محتوى المقال هنا..."
/>
```

#### English Content (Optional)
```blade
<x-admin.editor
    name="content[en]"
    :value="$article->content['en']"
    language="en"
    placeholder="Write your article content here..."
/>
```

#### Custom Height
```blade
<x-admin.editor
    name="bio"
    :height="300"
    placeholder="Brief bio..."
/>
```

#### Disabled State
```blade
<x-admin.editor
    name="preview"
    :value="$publishedContent"
    :disabled="true"
/>
```

---

## Configuration

### TinyMCE Configuration Options

The editor component uses a comprehensive configuration set in `tinymce-config.js`:

```javascript
// Default configuration
{
    license_key: 'gpl',
    height: 500,
    menubar: false,
    statusbar: true,
    resize: true,

    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image',
        'charmap', 'anchor', 'searchreplace', 'visualblocks',
        'code', 'fullscreen', 'insertdatetime', 'media',
        'table', 'help', 'wordcount', 'directionality', 'paste'
    ],

    toolbar: 'undo redo | styles | bold italic | ...',

    // Content styling
    content_style: '...',

    // Dark mode support
    skin: darkMode ? 'oxide-dark' : 'oxide',
    content_css: darkMode ? 'dark' : 'default'
}
```

### Customizing Configuration

To customize the editor configuration, modify `tinymce-config.js`:

```javascript
// Add custom plugins
plugins: [
    ...DEFAULT_CONFIG.plugins,
    'emoticons',  // Add emoticons plugin
    'codesample'  // Add code sample plugin
],

// Customize toolbar
toolbar: 'undo redo | bold italic | emoticons codesample',

// Custom styles
style_formats: [
    {
        title: 'Custom Heading',
        block: 'h2',
        classes: 'custom-heading',
        wrapper: true
    }
]
```

---

## Multi-Language Support

### Supported Languages

- **Arabic (ar)**: RTL, Arabic language pack
- **English (en)**: LTR, default language pack
- **French (fr)**: LTR, French language pack
- **Turkish (tr)**: LTR, Turkish language pack
- **Urdu (ur)**: RTL, uses Arabic language pack

### RTL Support

RTL is automatically enabled for Arabic, Urdu, Farsi, and Hebrew:

```javascript
// Automatic RTL detection
const isRtl = ['ar', 'ur', 'fa', 'he'].includes(language);
const directionality = isRtl ? 'rtl' : 'ltr';
```

### Language Configuration

Each language has specific configuration in `tinymce-config.js`:

```javascript
const LANGUAGE_MAP = {
    'ar': 'ar',      // Arabic
    'en': 'en',      // English
    'fr': 'fr_FR',   // French
    'tr': 'tr',      // Turkish
    'ur': 'ar'       // Urdu (uses Arabic)
};
```

### Content Styling per Language

RTL languages have custom content styling:

```css
body[dir="rtl"] {
    text-align: right;
}

body[dir="rtl"] blockquote {
    border-left: none;
    border-right: 4px solid #0056b3;
    padding-right: 16px;
}
```

---

## Form Integration

### Laravel Blade Forms

#### 1. Create Form

```blade
<form action="{{ route('admin.articles.store') }}" method="POST" @submit="handleSubmit">
    @csrf

    <x-admin.editor
        name="content[ar]"
        language="ar"
        :required="true"
    />

    <button type="submit">Create Article</button>
</form>
```

#### 2. Edit Form

```blade
<form action="{{ route('admin.articles.update', $article->id) }}" method="POST" @submit="handleSubmit">
    @csrf
    @method('PUT')

    <x-admin.editor
        name="content[ar]"
        :value="old('content.ar', $article->content['ar'])"
        language="ar"
        :required="true"
    />

    <button type="submit">Update Article</button>
</form>
```

### Form Submission Handler

```javascript
function articleForm() {
    return {
        isSubmitting: false,

        handleSubmit(event) {
            this.isSubmitting = true;

            // Extract content from TinyMCE editors
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();

                // Validate content
                const editors = tinymce.editors;
                for (let editor of editors) {
                    const content = editor.getContent({ format: 'text' }).trim();
                    const wordCount = editor.plugins.wordcount.body.getWordCount();

                    if (editor.id.includes('ar') && wordCount < 10) {
                        alert('Arabic content requires minimum 10 words');
                        this.isSubmitting = false;
                        event.preventDefault();
                        return false;
                    }
                }
            }

            return true; // Allow submission
        }
    };
}
```

### Validation

#### Client-Side Validation

```javascript
// Validate editor content before submission
function validateEditorContent(editorId, minWords = 10) {
    const editor = tinymce.get(editorId);
    if (!editor) return false;

    const wordCount = editor.plugins.wordcount.body.getWordCount();
    return wordCount >= minWords;
}
```

#### Server-Side Validation (Laravel)

```php
// In ArticleController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'content.ar' => 'required|string|min:50',
        'content.en' => 'nullable|string',
        'content.fr' => 'nullable|string',
        'content.tr' => 'nullable|string',
        'content.ur' => 'nullable|string',
    ]);

    // Process content
    Article::create($validated);
}
```

---

## Advanced Usage

### JavaScript API

The editor component exposes several methods through Alpine.js:

```javascript
// Get editor instance
this.editorInstance

// Get content
const html = this.getContent();

// Set content
this.setContent('<p>New content</p>');

// Clear content
this.clearContent();

// Update word count
this.updateWordCount(editor);

// Destroy editor
this.destroy();
```

### Using Configuration Module

Import and use the configuration module:

```javascript
import TinyMCEConfig from './tinymce-config.js';

// Get configuration for language
const config = TinyMCEConfig.getConfigForLanguage('ar');

// Get dark mode configuration
const darkConfig = TinyMCEConfig.getDarkModeConfig(true);

// Initialize multiple editors
TinyMCEConfig.initMultiLanguageEditors([
    { selector: '#editor_ar', language: 'ar' },
    { selector: '#editor_en', language: 'en' }
], darkMode);

// Extract all editor content
const allContent = TinyMCEConfig.extractAllEditorContent();

// Validate editor
const result = TinyMCEConfig.validateEditorContent('editor_ar', {
    required: true,
    minWords: 10
});

// Remove all editors (cleanup)
TinyMCEConfig.removeAllEditors();
```

### Custom Events

Listen to editor events:

```javascript
setup: (editor) => {
    editor.on('init', () => {
        console.log('Editor initialized');
    });

    editor.on('change', () => {
        console.log('Content changed');
    });

    editor.on('focus', () => {
        console.log('Editor focused');
    });

    editor.on('blur', () => {
        console.log('Editor blurred');
    });
}
```

### Image Upload Handler

Implement custom image upload:

```javascript
images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
    const formData = new FormData();
    formData.append('file', blobInfo.blob(), blobInfo.filename());

    fetch('/admin/upload-image', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(result => {
        resolve(result.location);
    })
    .catch(error => {
        reject('Image upload failed: ' + error.message);
    });
})
```

---

## Troubleshooting

### Common Issues

#### 1. Editor Not Loading

**Problem**: Editor shows "Loading editor..." indefinitely

**Solutions**:
- Check internet connection (TinyMCE loads from CDN)
- Verify CDN URL is not blocked
- Check browser console for errors
- Try clicking "Retry" button

#### 2. Content Not Saving

**Problem**: Form submission doesn't include editor content

**Solution**:
```javascript
// Ensure triggerSave is called before submission
handleSubmit(event) {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave(); // This saves to textarea
    }
    return true;
}
```

#### 3. Dark Mode Not Working

**Problem**: Editor doesn't match dashboard theme

**Solution**:
- Ensure darkMode state is accessible in parent component
- Check `$root.closest('[x-data]')?.darkMode` returns correct value
- Verify TinyMCE dark skin is loading

#### 4. RTL Not Working

**Problem**: Arabic content displays LTR

**Solution**:
- Verify language prop is set correctly: `language="ar"`
- Check RTL languages array includes the language
- Ensure directionality is set in config

#### 5. Multiple Editors Conflict

**Problem**: Multiple editors on same page interfere with each other

**Solution**:
- Ensure each editor has unique ID
- Use `x-show` instead of `v-if` for tab switching
- Don't initialize multiple editors on same selector

### Debugging

Enable debug mode:

```javascript
// In Alpine component
init() {
    console.log('Editor config:', {
        id: this.id,
        language: this.language,
        direction: this.direction
    });
}

// In TinyMCE setup
setup: (editor) => {
    editor.on('init', () => {
        console.log('Editor initialized:', editor.id);
        console.log('Editor config:', editor.settings);
    });
}
```

### Browser Console Errors

Common errors and solutions:

```
Error: TinyMCE is not defined
Solution: Check CDN script is loaded before component initialization

Error: Cannot read property 'getContent' of null
Solution: Wait for editorReady before accessing editor methods

Error: Failed to load skin
Solution: Check internet connection or use local TinyMCE installation
```

---

## Best Practices

### 1. Performance

- **Lazy Load**: Only initialize editors when needed
- **Cleanup**: Remove editors before component unmount
- **Debounce**: Debounce content change handlers
- **Minimize Plugins**: Only include necessary plugins

```javascript
// Remove editors on navigation
window.addEventListener('beforeunload', () => {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
});
```

### 2. Validation

- **Client-Side**: Validate before submission
- **Server-Side**: Always validate on backend
- **User Feedback**: Show clear error messages

```javascript
// Comprehensive validation
function validateContent() {
    const editor = tinymce.get('editor_ar');
    const content = editor.getContent({ format: 'text' }).trim();
    const wordCount = editor.plugins.wordcount.body.getWordCount();

    if (!content) {
        showError('Content is required');
        return false;
    }

    if (wordCount < 10) {
        showError('Minimum 10 words required');
        return false;
    }

    return true;
}
```

### 3. User Experience

- **Loading States**: Show visual feedback during initialization
- **Error Handling**: Provide retry options on failure
- **Autosave**: Implement autosave for long-form content
- **Keyboard Shortcuts**: Document available shortcuts

```javascript
// Autosave implementation
let autosaveTimer;
editor.on('change', () => {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(() => {
        saveContent(editor.getContent());
    }, 30000); // Autosave every 30 seconds
});
```

### 4. Accessibility

- **ARIA Labels**: Ensure all toolbar buttons have labels
- **Keyboard Navigation**: Test tab navigation
- **Screen Readers**: Verify screen reader compatibility
- **Focus Management**: Proper focus states

### 5. Content Security

- **Sanitize HTML**: Always sanitize on backend
- **XSS Prevention**: Validate and escape content
- **File Uploads**: Validate image uploads server-side
- **CSRF Protection**: Include CSRF token in upload requests

```php
// Laravel backend sanitization
use HTMLPurifier;

$purifier = new HTMLPurifier();
$cleanContent = $purifier->purify($request->input('content.ar'));
```

### 6. Maintenance

- **Version Control**: Lock TinyMCE version in production
- **Documentation**: Document custom configurations
- **Testing**: Test editor in all supported browsers
- **Updates**: Monitor TinyMCE updates and changelogs

---

## Testing

### Manual Testing Checklist

- [ ] Editor initializes correctly
- [ ] All toolbar buttons work
- [ ] Content saves to textarea on form submission
- [ ] RTL languages display correctly
- [ ] Dark mode switches properly
- [ ] Image upload works
- [ ] Word count updates in real-time
- [ ] Validation errors display
- [ ] Multiple editors work on same page
- [ ] Tab switching preserves content
- [ ] Mobile toolbar is responsive
- [ ] Browser compatibility (Chrome, Firefox, Safari, Edge)

### Automated Testing

```javascript
// Jest/Vitest test example
describe('TinyMCE Editor Component', () => {
    it('initializes editor', async () => {
        const component = tinymceEditor({
            id: 'test_editor',
            language: 'ar',
            direction: 'rtl'
        });

        await component.initEditor();
        expect(component.editorReady).toBe(true);
    });

    it('extracts content', () => {
        const content = '<p>Test content</p>';
        component.setContent(content);
        expect(component.getContent()).toBe(content);
    });
});
```

---

## Resources

### Documentation
- [TinyMCE Official Docs](https://www.tiny.cloud/docs/)
- [TinyMCE API Reference](https://www.tiny.cloud/docs/api/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Laravel Blade Components](https://laravel.com/docs/blade#components)

### Support
- TinyMCE Community Forum
- Stack Overflow Tag: `tinymce`
- GitHub Issues (for this project)

### Related Files
- `resources/views/components/admin/editor.blade.php` - Editor component
- `resources/js/admin/tinymce-config.js` - Configuration module
- `resources/views/admin/articles/create.blade.php` - Implementation example
- `resources/views/admin/articles/edit.blade.php` - Implementation example

---

## License

TinyMCE is used under the GPL license for this open-source project. For commercial use, obtain a license from [Tiny Technologies](https://www.tiny.cloud/pricing/).

---

## Changelog

### Version 1.0.0 (2024-11-01)
- Initial TinyMCE 6 integration
- Multi-language support (ar, en, fr, tr, ur)
- RTL/LTR automatic detection
- Dark/light mode theme support
- Alpine.js integration
- Blade component implementation
- Configuration module
- Form integration examples
- Comprehensive documentation

---

## Contributors

- The Last Messenger Development Team
- Claude Code (AI Assistant)

---

## Feedback

For questions, issues, or suggestions regarding the TinyMCE integration, please:
1. Check this documentation first
2. Review the Troubleshooting section
3. Open an issue on the project repository
4. Contact the development team

---

**Last Updated**: November 1, 2024
**Version**: 1.0.0
**Maintained By**: The Last Messenger Development Team
