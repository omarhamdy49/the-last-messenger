# Form Builder Component Guide

## Overview

A comprehensive, flexible form building system for The Last Messenger admin panel with multi-language support, validation, dark mode, and RTL layout awareness.

## File Structure

```
resources/views/components/admin/
├── form-builder.blade.php              # Main form wrapper
├── form-section.blade.php              # Collapsible form sections
└── form/
    ├── text-input.blade.php            # Text, email, URL, number inputs
    ├── textarea.blade.php              # Multi-line text input
    ├── select.blade.php                # Dropdown with search
    ├── checkbox.blade.php              # Single checkbox
    ├── radio.blade.php                 # Radio button group
    ├── file-input.blade.php            # File upload with drag & drop
    └── date-picker.blade.php           # Date/datetime picker
```

## Components

### 1. Form Builder (Wrapper)

Main form container with validation, dirty state tracking, and CSRF protection.

**Props:**
- `action` (string): Form submission URL
- `method` (string): HTTP method (GET, POST, PUT, PATCH, DELETE)
- `enctype` (string): Form encoding type (use `multipart/form-data` for file uploads)
- `class` (string): Additional CSS classes

**Example:**
```blade
<x-admin.form-builder action="/admin/articles" method="POST" enctype="multipart/form-data">
    <!-- Form fields here -->
</x-admin.form-builder>
```

**Features:**
- Automatic CSRF token injection
- Method spoofing for PUT/PATCH/DELETE
- Client-side validation
- Dirty state tracking (warns before leaving unsaved changes)
- Error message management

---

### 2. Form Section

Collapsible sections to organize form fields.

**Props:**
- `title` (string): Section heading
- `description` (string): Section description
- `collapsible` (boolean): Enable collapse/expand
- `collapsed` (boolean): Start collapsed
- `icon` (string): Lucide icon name

**Example:**
```blade
<x-admin.form-section
    title="Basic Information"
    description="Essential content details"
    icon="info"
    collapsible="true">

    <!-- Form fields here -->

</x-admin.form-section>
```

---

### 3. Text Input

Supports text, email, URL, number, password, tel, and other HTML5 input types.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `type` (string): Input type (default: `text`)
- `value` (string): Default value
- `placeholder` (string): Placeholder text
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable input
- `readonly` (boolean): Make readonly
- `multilang` (boolean): Enable multi-language tabs
- `helpText` (string): Helper text below field
- `icon` (string): Lucide icon name
- `maxlength` (int): Maximum character length
- `minlength` (int): Minimum character length
- `pattern` (string): Regex validation pattern
- `autocomplete` (string): Autocomplete attribute

**Example:**
```blade
<!-- Single language -->
<x-admin.form.text-input
    name="email"
    label="Email Address"
    type="email"
    placeholder="example@domain.com"
    icon="mail"
    required
    helpText="We'll never share your email"
/>

<!-- Multi-language -->
<x-admin.form.text-input
    name="title"
    label="Title"
    :multilang="true"
    required
    maxlength="200"
    icon="heading"
/>
```

**Features:**
- Language tabs for multi-language fields
- Character counter with warning at 90%
- Gradient focus state with glow effect
- Error display with icon
- RTL support for Arabic/Urdu

---

### 4. Textarea

Multi-line text input with optional auto-resize.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `value` (string): Default value
- `placeholder` (string): Placeholder text
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable textarea
- `readonly` (boolean): Make readonly
- `multilang` (boolean): Enable multi-language tabs
- `helpText` (string): Helper text
- `rows` (int): Number of rows (default: 4)
- `maxlength` (int): Maximum character length
- `minlength` (int): Minimum character length
- `autoResize` (boolean): Auto-expand height

**Example:**
```blade
<x-admin.form.textarea
    name="description"
    label="Description"
    :multilang="true"
    rows="4"
    maxlength="1000"
    autoResize="true"
    helpText="Provide a detailed description"
/>
```

**Features:**
- All features from text-input
- Auto-resize based on content
- Custom scrollbar styling

---

### 5. Select Dropdown

Custom-styled dropdown with search and multi-select support.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `value` (string|array): Selected value(s)
- `options` (array, required): Options as key-value pairs
- `placeholder` (string): Placeholder text
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable select
- `multiple` (boolean): Allow multiple selections
- `helpText` (string): Helper text
- `icon` (string): Lucide icon name
- `searchable` (boolean): Enable search filter

**Example:**
```blade
<!-- Single select -->
<x-admin.form.select
    name="status"
    label="Status"
    :options="[
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived'
    ]"
    value="draft"
    icon="file-check"
    required
/>

<!-- Searchable multi-select -->
<x-admin.form.select
    name="tags"
    label="Tags"
    :options="[
        'featured' => 'Featured',
        'trending' => 'Trending',
        'educational' => 'Educational'
    ]"
    icon="tag"
    multiple="true"
    searchable="true"
/>
```

**Features:**
- Custom-styled dropdown (not native select)
- Live search filtering
- Multi-select with chips
- Gradient selection highlight
- Click-outside to close

---

### 6. Checkbox

Custom-styled checkbox with description support.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `value` (string): Checkbox value (default: `1`)
- `checked` (boolean): Default checked state
- `disabled` (boolean): Disable checkbox
- `helpText` (string): Helper text
- `description` (string): Longer description below label

**Example:**
```blade
<x-admin.form.checkbox
    name="featured"
    label="Featured Content"
    description="Display this content prominently on the homepage"
/>

<x-admin.form.checkbox
    name="allow_comments"
    label="Allow Comments"
    checked="true"
/>
```

**Features:**
- Animated checkmark
- Gradient checked state
- Hidden input for unchecked value (0)

---

### 7. Radio Buttons

Custom-styled radio button group with rich option support.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `options` (array, required): Options with labels/descriptions/icons
- `value` (string): Selected value
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable all radios
- `helpText` (string): Helper text
- `inline` (boolean): Display horizontally

**Example:**
```blade
<!-- Simple options -->
<x-admin.form.radio
    name="content_type"
    label="Content Type"
    :options="[
        'article' => 'Article',
        'video' => 'Video',
        'audio' => 'Audio'
    ]"
    value="article"
    inline="true"
/>

<!-- Rich options with icons and descriptions -->
<x-admin.form.radio
    name="visibility"
    label="Visibility Level"
    :options="[
        'public' => [
            'label' => 'Public',
            'description' => 'Visible to everyone',
            'icon' => 'globe'
        ],
        'members' => [
            'label' => 'Members Only',
            'description' => 'Visible only to registered users',
            'icon' => 'users'
        ]
    ]"
    required
/>
```

**Features:**
- Card-style radio options
- Support for icons and descriptions
- Gradient border on selection
- Animated selection state

---

### 8. File Input

Drag & drop file upload with preview support.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable upload
- `multiple` (boolean): Allow multiple files
- `accept` (string): Accepted file types (e.g., `image/*,application/pdf`)
- `helpText` (string): Helper text
- `maxSize` (int): Maximum file size in MB
- `preview` (boolean): Show image previews
- `existingFile` (string): URL/path to existing file

**Example:**
```blade
<!-- Single image with preview -->
<x-admin.form.file-input
    name="featured_image"
    label="Featured Image"
    accept="image/jpeg,image/png,image/webp"
    maxSize="5"
    preview="true"
    helpText="Max 5MB. Recommended: 1200x630px"
/>

<!-- Multiple files -->
<x-admin.form.file-input
    name="attachments"
    label="Attachments"
    multiple="true"
    accept="image/*,application/pdf,.doc,.docx"
    maxSize="10"
/>
```

**Features:**
- Drag & drop zone
- File size validation
- Image preview thumbnails
- File type icons
- Progress/success indicators
- Remove file functionality
- Existing file display

---

### 9. Date Picker

Native HTML5 date picker with quick selection buttons.

**Props:**
- `name` (string, required): Field name
- `label` (string): Field label
- `value` (string): Default date value
- `required` (boolean): Mark as required
- `disabled` (boolean): Disable picker
- `readonly` (boolean): Make readonly
- `helpText` (string): Helper text
- `icon` (string): Lucide icon name (default: `calendar`)
- `min` (string): Minimum date
- `max` (string): Maximum date
- `format` (string): Date format (default: `Y-m-d`)
- `time` (boolean): Include time selection

**Example:**
```blade
<!-- Date only -->
<x-admin.form.date-picker
    name="publish_date"
    label="Publication Date"
    helpText="When should this be published?"
/>

<!-- Date and time -->
<x-admin.form.date-picker
    name="scheduled_at"
    label="Schedule Date & Time"
    time="true"
/>

<!-- Date range -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-admin.form.date-picker
        name="start_date"
        label="Start Date"
    />
    <x-admin.form.date-picker
        name="end_date"
        label="End Date"
    />
</div>
```

**Features:**
- Quick selection buttons (Today, Tomorrow, Next Week)
- Native date picker with fallback
- Formatted date display
- Clear button
- Localized date format

---

## Complete Form Example

```blade
@extends('layouts.admin')

@section('content')
    <x-admin.form-builder action="/admin/articles" method="POST" enctype="multipart/form-data">

        <x-admin.form-section title="Basic Information" icon="info">

            <x-admin.form.text-input
                name="title"
                label="Title"
                :multilang="true"
                required
                maxlength="200"
            />

            <x-admin.form.textarea
                name="description"
                label="Description"
                :multilang="true"
                rows="4"
            />

            <x-admin.form.select
                name="status"
                label="Status"
                :options="['draft' => 'Draft', 'published' => 'Published']"
                value="draft"
                required
            />

        </x-admin.form-section>

        <x-admin.form-section title="Media" icon="image">

            <x-admin.form.file-input
                name="featured_image"
                label="Featured Image"
                accept="image/*"
                maxSize="5"
                preview="true"
            />

        </x-admin.form-section>

        <x-admin.form-section title="Options" icon="settings" collapsible="true">

            <x-admin.form.checkbox
                name="featured"
                label="Featured"
                description="Display on homepage"
            />

            <x-admin.form.radio
                name="visibility"
                label="Visibility"
                :options="[
                    'public' => 'Public',
                    'private' => 'Private'
                ]"
                value="public"
            />

            <x-admin.form.date-picker
                name="publish_date"
                label="Publish Date"
            />

        </x-admin.form-section>

        <!-- Submit buttons -->
        <div class="flex justify-end gap-4">
            <button type="button" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">Save</button>
        </div>

    </x-admin.form-builder>
@endsection
```

---

## Multi-Language Support

All text-based fields support multi-language input:

```blade
<x-admin.form.text-input
    name="title"
    :multilang="true"
    label="Title"
/>
```

This creates tabs for each configured language (ar, en, fr, tr, ur) and generates separate inputs:
- `title[ar]`
- `title[en]`
- `title[fr]`
- `title[tr]`
- `title[ur]`

**Backend Handling:**
```php
// In your controller
$validated = $request->validate([
    'title.ar' => 'required|string|max:200',
    'title.en' => 'required|string|max:200',
    'title.fr' => 'nullable|string|max:200',
    'title.tr' => 'nullable|string|max:200',
    'title.ur' => 'nullable|string|max:200',
]);

// Save to model with translatable trait
$article = Article::create([
    'title' => $validated['title'], // Stores as JSON: {"ar": "...", "en": "..."}
]);
```

---

## Validation & Error Handling

The form builder automatically handles Laravel validation errors:

```blade
<!-- Errors are automatically displayed -->
<x-admin.form.text-input name="email" />
```

**Backend validation:**
```php
$request->validate([
    'email' => 'required|email|unique:users',
    'title.ar' => 'required|string|max:200',
]);
```

Errors appear below fields with:
- Red border on the input
- Error icon
- Error message
- Animation

---

## Dark Mode Support

All components automatically adapt to dark mode via Alpine.js `darkMode` state:

```html
:class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'"
```

No additional configuration needed.

---

## RTL Support

Text inputs automatically detect RTL languages (Arabic, Urdu):

```blade
dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
```

The form adjusts text direction, alignment, and layout accordingly.

---

## Styling & Customization

### Color Scheme

Components use the project's blue-green gradient palette:

- **Primary Gradient:** `from-[#0056b3] to-[#218838]`
- **Accent Color:** `#64CCC5` (teal)
- **Focus State:** Teal border with glow effect

### Icons

Uses Lucide icons. Specify icon names as props:

```blade
<x-admin.form.text-input icon="mail" />
```

Icons are automatically initialized via:
```javascript
lucide.createIcons();
```

### Custom Classes

Add custom classes to any component:

```blade
<x-admin.form-builder class="max-w-4xl mx-auto">
```

---

## Accessibility

All components follow accessibility best practices:

- Semantic HTML
- ARIA labels and roles
- Keyboard navigation
- Focus indicators
- Screen reader support
- Error announcements

---

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- Mobile browsers: Full support

Native date pickers use HTML5 input types with fallbacks.

---

## Demo Page

View all components in action:

```
/admin/form-builder-demo
```

File location:
```
resources/views/admin/form-builder-demo.blade.php
```

---

## Tips & Best Practices

1. **Always use form-builder wrapper** for CSRF protection and validation
2. **Group related fields** in form-section components
3. **Use multilang sparingly** - only for user-facing content
4. **Set appropriate maxlength** to prevent database overflow
5. **Add helpText** to guide users
6. **Use icons consistently** for visual hierarchy
7. **Test RTL layout** for Arabic/Urdu content
8. **Validate on backend** - never trust client-side validation alone

---

## Troubleshooting

### Icons not showing
```javascript
// Add to your page
lucide.createIcons();
```

### Multi-language tabs not working
Ensure `translatable.php` config is properly set:
```php
'locales' => ['ar', 'en', 'fr', 'tr', 'ur']
```

### Form not submitting
Check for:
- Missing `action` attribute
- JavaScript errors in console
- CSRF token (automatically added by form-builder)

### Dark mode not working
Ensure Alpine.js is loaded and `darkMode` state is initialized in layout.

---

## File Locations

- **Components:** `/resources/views/components/admin/form/`
- **Demo:** `/resources/views/admin/form-builder-demo.blade.php`
- **Layout:** `/resources/views/layouts/admin.blade.php`
- **Config:** `/config/translatable.php`

---

## Support

For issues or questions, refer to:
- CLAUDE.md project documentation
- Laravel Blade Components documentation
- Alpine.js documentation
