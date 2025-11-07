# Form Builder - Quick Reference Card

## Component Syntax Cheat Sheet

### Basic Form Structure
```blade
<x-admin.form-builder action="/path" method="POST">
    <x-admin.form-section title="Section" icon="icon-name">
        <!-- Fields here -->
    </x-admin.form-section>
</x-admin.form-builder>
```

---

## Field Components

### Text Input
```blade
<x-admin.form.text-input
    name="field_name"
    label="Field Label"
    type="text|email|url|number|password"
    :multilang="true|false"
    required
    icon="icon-name"
    maxlength="100"
    placeholder="Placeholder..."
    helpText="Help text"
/>
```

### Textarea
```blade
<x-admin.form.textarea
    name="field_name"
    label="Field Label"
    :multilang="true|false"
    rows="4"
    maxlength="1000"
    autoResize="true|false"
    helpText="Help text"
/>
```

### Select Dropdown
```blade
<x-admin.form.select
    name="field_name"
    label="Field Label"
    :options="['key1' => 'Label 1', 'key2' => 'Label 2']"
    value="default_key"
    multiple="true|false"
    searchable="true|false"
    icon="icon-name"
    required
/>
```

### Checkbox
```blade
<x-admin.form.checkbox
    name="field_name"
    label="Field Label"
    description="Longer description"
    checked="true|false"
/>
```

### Radio Buttons
```blade
<x-admin.form.radio
    name="field_name"
    label="Field Label"
    :options="[
        'value1' => 'Label 1',
        'value2' => [
            'label' => 'Label 2',
            'description' => 'Description',
            'icon' => 'icon-name'
        ]
    ]"
    value="default"
    inline="true|false"
    required
/>
```

### File Upload
```blade
<x-admin.form.file-input
    name="field_name"
    label="Field Label"
    accept="image/*|application/pdf"
    multiple="true|false"
    maxSize="5"
    preview="true|false"
    existingFile="{{ $model->file_url }}"
/>
```

### Date Picker
```blade
<x-admin.form.date-picker
    name="field_name"
    label="Field Label"
    time="true|false"
    min="2024-01-01"
    max="2024-12-31"
    icon="calendar"
/>
```

---

## Form Section
```blade
<x-admin.form-section
    title="Section Title"
    description="Section description"
    icon="icon-name"
    collapsible="true|false"
    collapsed="true|false">
    <!-- Fields here -->
</x-admin.form-section>
```

---

## Common Patterns

### Multi-language Text Field
```blade
<x-admin.form.text-input
    name="title"
    label="Title"
    :multilang="true"
    required
    maxlength="200"
/>
```
Generates: `title[ar]`, `title[en]`, `title[fr]`, `title[tr]`, `title[ur]`

### Email Field
```blade
<x-admin.form.text-input
    name="email"
    type="email"
    label="Email"
    icon="mail"
    required
/>
```

### URL Field
```blade
<x-admin.form.text-input
    name="website"
    type="url"
    label="Website"
    icon="link"
    placeholder="https://example.com"
/>
```

### Status Select
```blade
<x-admin.form.select
    name="status"
    label="Status"
    :options="['draft' => 'Draft', 'published' => 'Published']"
    icon="file-check"
    required
/>
```

### Image Upload
```blade
<x-admin.form.file-input
    name="image"
    label="Image"
    accept="image/jpeg,image/png,image/webp"
    maxSize="5"
    preview="true"
/>
```

### Featured Checkbox
```blade
<x-admin.form.checkbox
    name="is_featured"
    label="Featured"
    description="Display on homepage"
/>
```

### Visibility Radio
```blade
<x-admin.form.radio
    name="visibility"
    label="Visibility"
    :options="[
        'public' => ['label' => 'Public', 'icon' => 'globe'],
        'private' => ['label' => 'Private', 'icon' => 'lock']
    ]"
    value="public"
/>
```

---

## Form Actions (Bottom Buttons)
```blade
<div class="flex justify-end gap-4 p-6 rounded-xl glass"
     :class="darkMode ? 'glass-dark' : 'glass-light'">

    <button type="button"
            onclick="window.history.back()"
            class="px-6 py-3 rounded-lg font-semibold"
            :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
        Cancel
    </button>

    <button type="submit"
            name="action"
            value="draft"
            class="px-6 py-3 rounded-lg font-semibold border-2"
            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
        Save Draft
    </button>

    <button type="submit"
            class="px-8 py-3 rounded-lg font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
        Publish
    </button>
</div>
```

---

## Validation Rules (Backend)

```php
// Single language
$request->validate([
    'email' => 'required|email|unique:users',
    'title' => 'required|string|max:200',
    'description' => 'nullable|string|max:1000',
    'status' => 'required|in:draft,published',
    'image' => 'nullable|image|max:5120', // 5MB
]);

// Multi-language
$request->validate([
    'title.ar' => 'required|string|max:200',
    'title.en' => 'required|string|max:200',
    'title.fr' => 'nullable|string|max:200',
    'description.ar' => 'required|string|max:1000',
    'description.en' => 'required|string|max:1000',
]);
```

---

## Common Icons (Lucide)

| Field Type | Recommended Icon |
|------------|------------------|
| Title | `heading`, `text` |
| Email | `mail`, `at-sign` |
| URL | `link`, `globe` |
| Password | `lock`, `key` |
| Phone | `phone` |
| Search | `search` |
| Date | `calendar`, `calendar-days` |
| Time | `clock` |
| Image | `image`, `camera` |
| File | `file`, `paperclip` |
| Status | `file-check`, `circle-dot` |
| Category | `folder`, `tag` |
| Info | `info`, `alert-circle` |
| Settings | `settings`, `sliders` |
| User | `user`, `users` |

---

## Grid Layouts

### Two Columns
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-admin.form.text-input name="first_name" label="First Name" />
    <x-admin.form.text-input name="last_name" label="Last Name" />
</div>
```

### Three Columns
```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <x-admin.form.select name="status" label="Status" :options="[]" />
    <x-admin.form.select name="category" label="Category" :options="[]" />
    <x-admin.form.date-picker name="date" label="Date" />
</div>
```

---

## File Upload Accept Types

```blade
<!-- Images only -->
accept="image/*"
accept="image/jpeg,image/png,image/webp,image/gif"

<!-- Documents -->
accept="application/pdf"
accept="application/pdf,.doc,.docx"

<!-- Videos -->
accept="video/mp4,video/webm,video/ogg"

<!-- Audio -->
accept="audio/mp3,audio/wav,audio/ogg"

<!-- Mixed -->
accept="image/*,application/pdf,.doc,.docx"
```

---

## Complete Example

```blade
<x-admin.form-builder action="/admin/articles" method="POST" enctype="multipart/form-data">

    <x-admin.form-section title="Basic Info" icon="info">
        <x-admin.form.text-input name="title" label="Title" :multilang="true" required maxlength="200" />
        <x-admin.form.textarea name="description" label="Description" :multilang="true" rows="4" />
        <x-admin.form.select name="status" label="Status" :options="['draft' => 'Draft', 'published' => 'Published']" required />
    </x-admin.form-section>

    <x-admin.form-section title="Media" icon="image">
        <x-admin.form.file-input name="image" label="Featured Image" accept="image/*" maxSize="5" preview="true" />
    </x-admin.form-section>

    <x-admin.form-section title="Options" icon="settings" collapsible="true">
        <x-admin.form.checkbox name="featured" label="Featured" />
        <x-admin.form.date-picker name="publish_date" label="Publish Date" />
    </x-admin.form-section>

    <div class="flex justify-end gap-4">
        <button type="button" class="btn-secondary">Cancel</button>
        <button type="submit" class="btn-primary">Save</button>
    </div>

</x-admin.form-builder>
```

---

## Key Features

- Multi-language support (5 languages: ar, en, fr, tr, ur)
- Dark mode compatible
- RTL-aware layout
- Client-side validation
- Character counters
- Drag & drop file uploads
- Searchable dropdowns
- Custom-styled inputs
- Gradient focus states
- Error display with animations
- Collapsible sections
- Icon support (Lucide)

---

## File Locations

```
resources/views/components/admin/
├── form-builder.blade.php
├── form-section.blade.php
└── form/
    ├── text-input.blade.php
    ├── textarea.blade.php
    ├── select.blade.php
    ├── checkbox.blade.php
    ├── radio.blade.php
    ├── file-input.blade.php
    └── date-picker.blade.php
```

**Demo:** `/admin/form-builder-demo`
**Full Guide:** `FORM-BUILDER-GUIDE.md`
