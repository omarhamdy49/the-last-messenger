# Language Tabs Component Implementation

Complete multi-language content editing system for The Last Messenger admin panel.

## Overview

The Language Tabs component provides a comprehensive solution for managing multi-language content with:
- Horizontal tab navigation with language flags
- Active language indicator with blue-green gradient
- Smooth fade transitions between language panels
- Validation state indicators (red dot for errors, green check for complete)
- Auto-switch to language with errors
- Mobile-responsive scrollable tabs
- Dark mode support
- Auto-save functionality
- Copy content between languages
- Keyboard navigation
- Translation completeness tracking

## Files Created

### 1. Main Component
**Path:** `/resources/views/components/admin/language-tabs.blade.php`

Main container component that manages tab navigation and state.

**Features:**
- Desktop horizontal tabs with flags and language names
- Mobile dropdown menu for language selection
- Validation error badges (red dots)
- Completion status badges (green checkmarks)
- Progress bar showing completion percentage
- "Copy from..." button to duplicate content between languages
- Alpine.js state management
- Keyboard navigation support

### 2. Tab Panel Component
**Path:** `/resources/views/components/admin/language-tab-panel.blade.php`

Individual panel for each language's form fields.

**Features:**
- Fade transition when switching languages
- Automatic RTL/LTR direction based on language
- Optional panel header with language info
- Translation status indicator
- RTL language hint for Arabic/Urdu

### 3. JavaScript Helper
**Path:** `/resources/js/admin/language-tabs.js`

Advanced JavaScript functionality for enhanced UX.

**Features:**
- Keyboard navigation (Ctrl/Cmd + Arrow keys)
- Auto-save to localStorage every 3 seconds
- Draft restoration on page reload
- Validation tracking per language
- Completion percentage calculation
- Copy content between languages
- Email and URL validation helpers

### 4. Example Demo Page
**Path:** `/resources/views/admin/examples/language-tabs-demo.blade.php`

Comprehensive examples showing all usage patterns.

## Supported Languages

| Code | Language   | Direction | Flag |
|------|------------|-----------|------|
| ar   | 'D91(J)    | RTL       | <ø<æ  |
| en   | English    | LTR       | <ì<ç  |
| tr   | Türkçe     | LTR       | <ù<÷  |
| fr   | Français   | LTR       | <ë<÷  |
| ur   | '1/H       | RTL       | <õ<ð  |

## Basic Usage

### Simple Article Form

```blade
<form action="{{ route('admin.articles.store') }}" method="POST">
    @csrf

    <x-admin.language-tabs :errors="$errors">
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <x-admin.language-tab-panel :language="$lang">

                {{-- Title Field --}}
                <x-admin.form.text-input
                    :name="'title_' . $lang"
                    label="Article Title"
                    placeholder="Enter title..."
                    required
                    icon="file-text"
                />

                {{-- Content Field --}}
                <x-admin.form.textarea
                    :name="'content_' . $lang"
                    label="Content"
                    placeholder="Write content..."
                    required
                    rows="8"
                />

            </x-admin.language-tab-panel>
        @endforeach
    </x-admin.language-tabs>

    <button type="submit" class="btn-primary">Save</button>
</form>
```

## Component Props

### Language Tabs Component

```blade
<x-admin.language-tabs
    :languages="['ar', 'en', 'fr', 'tr', 'ur']"  // Array of language codes
    :errors="$errors"                             // Laravel validation errors
    default-language="ar"                         // Initial active language
    :show-completion-status="true"                // Show progress bar
    :show-copy-button="true"                      // Show copy content button
>
    <!-- Tab panels here -->
</x-admin.language-tabs>
```

**Props:**
- `languages` (array): Language codes to include. Default: `['ar', 'en', 'fr', 'tr', 'ur']`
- `errors` (ErrorBag): Laravel validation errors object
- `defaultLanguage` (string): Initial active language. Default: `'ar'`
- `showCompletionStatus` (bool): Display completion progress bar. Default: `true`
- `showCopyButton` (bool): Display "Copy from..." button. Default: `true`

### Language Tab Panel Component

```blade
<x-admin.language-tab-panel
    :language="$lang"          // Language code (required)
    :label="$label"            // Custom label (optional)
    :show-header="false"       // Show panel header
    header-class=""            // Custom header classes
>
    <!-- Form fields here -->
</x-admin.language-tab-panel>
```

**Props:**
- `language` (string): Language code (required)
- `label` (string): Custom label. Default: Language name
- `showHeader` (bool): Display panel header with language info. Default: `false`
- `headerClass` (string): Additional CSS classes for header

## Advanced Usage

### With Panel Headers

Show language info and translation status at the top of each panel:

```blade
<x-admin.language-tabs :errors="$errors">
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <x-admin.language-tab-panel
            :language="$lang"
            :show-header="true">

            <!-- Form fields -->

        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

### Different Default Language

Start with English instead of Arabic:

```blade
<x-admin.language-tabs
    :errors="$errors"
    default-language="en">

    <!-- Panels -->

</x-admin.language-tabs>
```

### Compact Mode (No Status Tracking)

Minimal UI without completion status and copy button:

```blade
<x-admin.language-tabs
    :errors="$errors"
    :show-completion-status="false"
    :show-copy-button="false">

    <!-- Panels -->

</x-admin.language-tabs>
```

### Subset of Languages

Only show Arabic and English:

```blade
<x-admin.language-tabs
    :languages="['ar', 'en']"
    :errors="$errors">

    @foreach(['ar', 'en'] as $lang)
        <x-admin.language-tab-panel :language="$lang">
            <!-- Form fields -->
        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

## Field Naming Convention

For the component to track validation and completion correctly, name fields with language suffix:

```blade
{{-- Correct naming --}}
<input name="title_ar" />
<input name="title_en" />
<textarea name="content_ar"></textarea>
<textarea name="content_en"></textarea>

{{-- Also works with array notation --}}
<input name="title[ar]" />
<input name="title[en]" />
```

## Validation Integration

### Controller Validation

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title_ar' => 'required|string|max:200',
        'title_en' => 'required|string|max:200',
        'title_fr' => 'nullable|string|max:200',
        'title_tr' => 'nullable|string|max:200',
        'title_ur' => 'nullable|string|max:200',

        'content_ar' => 'required|string',
        'content_en' => 'required|string',
        'content_fr' => 'nullable|string',
        'content_tr' => 'nullable|string',
        'content_ur' => 'nullable|string',
    ]);

    // Store data
}
```

### Custom Validation Messages

```php
$messages = [
    'title_ar.required' => 'Arabic title is required',
    'title_en.required' => 'English title is required',
    'content_ar.required' => 'Arabic content is required',
    'content_en.required' => 'English content is required',
];

$request->validate($rules, $messages);
```

### Auto-Switch to Error Language

When validation fails, the component automatically:
1. Detects which languages have errors
2. Switches to the first language with errors
3. Shows red dot badge on tabs with errors
4. Displays error messages within the panel

## JavaScript API

### Manual Initialization

```javascript
const manager = new LanguageTabsManager({
    container: document.querySelector('.language-tabs-container'),
    languages: ['ar', 'en', 'fr', 'tr', 'ur'],
    defaultLanguage: 'ar',
    autoSave: true,
    autoSaveDelay: 3000,
    storageKey: 'my_form_draft'
});
```

### Methods

```javascript
// Switch language programmatically
manager.switchLanguage('en');

// Get completion percentage
const completion = manager.getCompletionPercentage('ar');
console.log(`Arabic: ${completion}%`);

// Get all completion status
const allStatus = manager.getAllCompletionStatus();
// { ar: 75, en: 100, fr: 50, tr: 0, ur: 25 }

// Copy content between languages
manager.copyFromLanguage('ar', 'en');

// Save draft manually
manager.saveDraft('ar');

// Save all languages
manager.saveAllDrafts();

// Clear specific draft
manager.clearDraft('ar');

// Clear all drafts
manager.clearAllDrafts();

// Validate specific language
const errors = manager.validateLanguage('ar');
// true if valid, or array of error objects

// Validate all languages
const allErrors = manager.validateAllLanguages();
// true if all valid, or object with errors per language

// Destroy manager instance
manager.destroy();
```

### Events

Listen for custom events:

```javascript
// Language changed
window.addEventListener('language-changed', (e) => {
    console.log('Switched to:', e.detail.language);
});

// Draft saved
window.addEventListener('draft-saved', (e) => {
    console.log('Draft saved for:', e.detail.language);
});

// Content copied
window.addEventListener('language-content-copied', (e) => {
    console.log(`Copied from ${e.detail.source} to ${e.detail.target}`);
    console.log(`${e.detail.count} fields copied`);
});

// Validation error
window.addEventListener('language-validation-error', (e) => {
    console.log('Validation errors in:', e.detail.language);
    console.log('Errors:', e.detail.errors);
});
```

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl/Cmd + Left Arrow | Switch to previous language |
| Ctrl/Cmd + Right Arrow | Switch to next language |
| Ctrl/Cmd + S | Save draft (prevents default) |

## Features Deep Dive

### 1. Validation Tracking

The component tracks validation errors per language:

- **Visual Indicators**: Red dot badge on tabs with errors
- **Auto-Switch**: Automatically switches to first language with errors
- **Error Display**: Shows error messages below fields
- **Real-time**: Updates as you fix errors

### 2. Completion Status

Tracks progress based on required fields:

- **Progress Bar**: Shows 0-100% completion
- **Per Language**: Each language tracked independently
- **Green Badge**: Checkmark when 100% complete
- **Real-time**: Updates as you fill fields

### 3. Auto-Save Drafts

Automatically saves work to prevent data loss:

- **LocalStorage**: Saves to browser storage
- **Per Language**: Each language saved separately
- **Auto Interval**: Saves every 3 seconds after changes
- **Restore**: Loads drafts on page reload
- **Visibility**: Saves when tab becomes hidden
- **Beforeunload**: Saves before page navigation

### 4. Copy Content

Quickly duplicate content between languages:

- **Copy Button**: "Copy from..." dropdown in action bar
- **Text Fields**: Copies input and textarea values
- **Skip Source**: Doesn't show current language in menu
- **Toast Notification**: Shows success message
- **Triggers**: Recalculates completion after copy

### 5. Mobile Responsive

Adapts to different screen sizes:

- **Desktop**: Horizontal scrollable tabs
- **Mobile**: Dropdown menu with language selection
- **Touch**: Optimized for touch interactions
- **Icons**: Maintains flags and badges on all sizes

### 6. RTL Support

Proper right-to-left support for Arabic and Urdu:

- **Auto Direction**: Sets `dir="rtl"` on Arabic/Urdu panels
- **Text Alignment**: Automatic text alignment
- **Info Hint**: Shows RTL mode indicator (dismissible)
- **Icons**: Properly positioned for RTL

## Styling & Theming

### Color System

Uses blue-green gradient scheme:

```css
/* Active tab */
bg-gradient-primary /* #0056b3 to #218838 */

/* Hover states */
text-[#64CCC5] /* Teal accent */

/* Focus states */
ring-accent /* Teal ring */
```

### Dark Mode

Fully supports dark mode with conditional classes:

```blade
:class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'"
```

### Custom Scrollbar

Uses custom scrollbar styling for consistency:

```css
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 204, 197, 0.5) transparent;
}
```

## Performance Considerations

### Debounced Updates

Auto-save and completion calculation are debounced:

```javascript
// Only recalculates after 300ms of inactivity
debouncedCalculateCompletion()
```

### Lazy Lucide Icons

Icons reinitialized only after tab switches:

```javascript
this.$nextTick(() => {
    lucide.createIcons();
});
```

### Efficient State Management

Alpine.js reactive state for optimal updates:

```javascript
x-data="{
    activeLanguage: 'ar',
    validationErrors: {},
    completionStatus: {}
}"
```

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- ES6+ JavaScript features
- LocalStorage API
- CSS Grid and Flexbox
- CSS Transitions
- Alpine.js 3.x

## Accessibility

### Keyboard Navigation
- Full keyboard support
- Focus management
- Tab key navigation

### Screen Readers
- Proper ARIA labels
- Role attributes
- Semantic HTML

### Visual Indicators
- High contrast badges
- Clear focus states
- Error announcements

## Troubleshooting

### Tabs not switching
**Solution**: Ensure Alpine.js is loaded and `x-data="languageTabs()"` is present.

### Icons not showing
**Solution**: Call `lucide.createIcons()` after content changes.

### Auto-save not working
**Solution**: Check console for errors. Ensure LocalStorage is enabled.

### Validation not tracking
**Solution**: Verify field naming follows convention (`field_lang` or `field[lang]`).

### Completion stuck at 0%
**Solution**: Ensure required fields have `required` attribute.

### Copy content not working
**Solution**: Check that field names match pattern with language suffix.

## Integration Examples

### With TinyMCE Editor

```blade
<x-admin.language-tab-panel :language="$lang">
    <x-admin.wysiwyg-editor
        :name="'content_' . $lang"
        label="Content"
        required
    />
</x-admin.language-tab-panel>
```

### With Image Upload

```blade
<x-admin.language-tab-panel :language="$lang">
    <x-admin.form.image-upload
        :name="'featured_image_' . $lang"
        label="Featured Image"
    />
</x-admin.language-tab-panel>
```

### With Tag Select

```blade
<x-admin.language-tab-panel :language="$lang">
    <x-admin.form.select-tags
        :name="'tags_' . $lang"
        label="Tags"
    />
</x-admin.language-tab-panel>
```

## Best Practices

1. **Always pass errors**: Include `:errors="$errors"` for validation tracking
2. **Use consistent naming**: Follow `field_lang` or `field[lang]` convention
3. **Mark required fields**: Add `required` attribute for completion tracking
4. **Test RTL languages**: Always check Arabic/Urdu display
5. **Provide help text**: Guide users with clear field descriptions
6. **Validate both sides**: Client and server validation
7. **Test mobile view**: Ensure dropdown works on small screens
8. **Clear drafts**: Provide way to clear saved drafts
9. **Limit languages**: Only include needed languages to reduce complexity
10. **Monitor performance**: Watch for slowdowns with many fields

## Migration from Old System

If migrating from the old multi-lang input component:

### Before
```blade
<x-admin.form.text-input
    name="title"
    :multilang="true"
    required
/>
```

### After
```blade
<x-admin.language-tabs :errors="$errors">
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <x-admin.language-tab-panel :language="$lang">
            <x-admin.form.text-input
                :name="'title_' . $lang"
                label="Title"
                required
            />
        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

## Future Enhancements

Potential improvements for future versions:

- [ ] Translation API integration (Google Translate, DeepL)
- [ ] Voice input for fields
- [ ] Translation memory (suggest previous translations)
- [ ] Diff view to compare translations
- [ ] Export/import translations as JSON/CSV
- [ ] Translation completeness reports
- [ ] Collaborative editing with conflict resolution
- [ ] Translation review workflow
- [ ] Machine translation suggestions
- [ ] Glossary support for consistent terminology

## Support

For issues or questions:
1. Check this documentation
2. Review example demo page
3. Inspect browser console for errors
4. Test with minimal example
5. Check Alpine.js and Lucide are loaded

## Credits

- **Design System**: Blue-green gradient scheme from CLAUDE.md
- **Icons**: Lucide Icons
- **Framework**: Laravel Blade + Alpine.js
- **Font**: Cairo (Arabic-optimized)

---

**Version**: 1.0.0
**Last Updated**: November 7, 2025
**Maintained by**: The Last Messenger Development Team
