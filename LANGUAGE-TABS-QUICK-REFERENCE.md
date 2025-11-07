# Language Tabs - Quick Reference

Fast reference guide for using the Language Tabs component.

## Basic Template

```blade
<x-admin.language-tabs :errors="$errors">
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <x-admin.language-tab-panel :language="$lang">

            <x-admin.form.text-input
                :name="'title_' . $lang"
                label="Title"
                required
            />

            <x-admin.form.textarea
                :name="'content_' . $lang"
                label="Content"
                required
                rows="8"
            />

        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

## Component Props

### Language Tabs
```blade
<x-admin.language-tabs
    :languages="['ar', 'en']"          # Languages to show
    :errors="$errors"                   # Validation errors
    default-language="ar"               # Initial language
    :show-completion-status="true"      # Show progress bar
    :show-copy-button="true"            # Show copy button
/>
```

### Language Tab Panel
```blade
<x-admin.language-tab-panel
    :language="$lang"       # Required: 'ar', 'en', 'fr', 'tr', 'ur'
    :show-header="false"    # Show language header
    :label="'Custom'"       # Custom label
/>
```

## Supported Languages

| Code | Language | Dir | Flag |
|------|----------|-----|------|
| ar   | 'D91(J)  | RTL | <ø<æ |
| en   | English  | LTR | <ì<ç |
| tr   | Türkçe   | LTR | <ù<÷ |
| fr   | Français | LTR | <ë<÷ |
| ur   | '1/H     | RTL | <õ<ð |

## Field Naming

```blade
# Pattern: field_language
title_ar
title_en
content_ar
content_en

# Or array notation
title[ar]
title[en]
```

## Controller Validation

```php
$request->validate([
    'title_ar' => 'required|max:200',
    'title_en' => 'required|max:200',
    'content_ar' => 'required',
    'content_en' => 'required',
]);
```

## Keyboard Shortcuts

| Keys | Action |
|------|--------|
| Ctrl/Cmd +  | Previous language |
| Ctrl/Cmd + ’ | Next language |
| Ctrl/Cmd + S | Save draft |

## JavaScript API

```javascript
// Get manager instance (auto-initialized)
const manager = window.languageTabsManager;

// Switch language
manager.switchLanguage('en');

// Get completion
manager.getCompletionPercentage('ar'); // Returns 0-100

// Copy content
manager.copyFromLanguage('ar', 'en');

// Save draft
manager.saveDraft('ar');
manager.saveAllDrafts();

// Clear drafts
manager.clearDraft('ar');
manager.clearAllDrafts();

// Validate
manager.validateLanguage('ar');
manager.validateAllLanguages();
```

## Events

```javascript
// Language changed
window.addEventListener('language-changed', (e) => {
    console.log(e.detail.language);
});

// Draft saved
window.addEventListener('draft-saved', (e) => {
    console.log(e.detail.language);
});

// Content copied
window.addEventListener('language-content-copied', (e) => {
    console.log(e.detail.source, e.detail.target);
});

// Validation error
window.addEventListener('language-validation-error', (e) => {
    console.log(e.detail.language, e.detail.errors);
});
```

## Common Patterns

### Arabic & English Only
```blade
<x-admin.language-tabs :languages="['ar', 'en']" :errors="$errors">
    @foreach(['ar', 'en'] as $lang)
        <x-admin.language-tab-panel :language="$lang">
            <!-- Fields -->
        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

### With Headers
```blade
<x-admin.language-tab-panel :language="$lang" :show-header="true">
    <!-- Fields -->
</x-admin.language-tab-panel>
```

### Compact Mode
```blade
<x-admin.language-tabs
    :errors="$errors"
    :show-completion-status="false"
    :show-copy-button="false">
    <!-- Panels -->
</x-admin.language-tabs>
```

### English Default
```blade
<x-admin.language-tabs :errors="$errors" default-language="en">
    <!-- Panels -->
</x-admin.language-tabs>
```

## Features

 Horizontal tabs with flags
 Mobile dropdown menu
 Validation tracking (red dots)
 Completion status (green checks)
 Progress bar (0-100%)
 Copy between languages
 Auto-save to localStorage
 Auto-restore drafts
 Keyboard navigation
 RTL support
 Dark mode
 Smooth transitions

## Files

| File | Path |
|------|------|
| Main Component | `resources/views/components/admin/language-tabs.blade.php` |
| Tab Panel | `resources/views/components/admin/language-tab-panel.blade.php` |
| JavaScript Helper | `resources/js/admin/language-tabs.js` |
| Demo Page | `resources/views/admin/examples/language-tabs-demo.blade.php` |
| Full Docs | `LANGUAGE-TABS-IMPLEMENTATION.md` |

## Troubleshooting

### Tabs not switching
- Check Alpine.js is loaded
- Verify `x-data="languageTabs()"` exists

### Icons not showing
- Call `lucide.createIcons()` after updates

### Auto-save not working
- Check browser console
- Ensure localStorage enabled

### Validation not tracking
- Use correct field naming: `field_lang`
- Pass `:errors="$errors"` prop

### Completion stuck at 0%
- Add `required` attribute to fields

## Quick Checklist

- [ ] Import component in Blade template
- [ ] Pass `:errors="$errors"` prop
- [ ] Name fields with `_lang` suffix
- [ ] Add `required` to required fields
- [ ] Validate in controller
- [ ] Test all languages
- [ ] Test mobile view
- [ ] Test dark mode
- [ ] Test keyboard shortcuts
- [ ] Test validation errors
- [ ] Test copy feature
- [ ] Test auto-save/restore

## Links

- [Full Documentation](LANGUAGE-TABS-IMPLEMENTATION.md)
- [Demo Page](resources/views/admin/examples/language-tabs-demo.blade.php)
- [JavaScript Source](resources/js/admin/language-tabs.js)

---

**Version**: 1.0.0
**Last Updated**: November 7, 2025
