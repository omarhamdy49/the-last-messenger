# Blade Components Documentation

**Created:** 2026-09-13  
**Status:** Active Reference  
**Last Updated:** 2026-09-13

## Overview

This document provides a comprehensive guide to the Blade component library used throughout The Last Messenger backend. The component library consists of 124+ reusable, anonymous Blade components organized into distinct domains (Admin, Site, Public, Icons) with consistent patterns for multi-language support, dark mode, and accessibility.

## Table of Contents

1. [Component Architecture](#component-architecture)
2. [Directory Structure](#directory-structure)
3. [Component Categories](#component-categories)
4. [Common Patterns](#common-patterns)
5. [Usage Examples](#usage-examples)
6. [Props Documentation](#props-documentation)
7. [Migration Guide](#migration-guide)
8. [Best Practices](#best-practices)

---

## Component Architecture

### Anonymous Components

All components in this codebase are **anonymous Blade components** (not class-based components). They:

- Live in `resources/views/components/`
- Are invoked using `<x-namespace.component-name />` syntax
- Define props using the `@props` directive
- Do not require corresponding PHP classes in `app/View/Components/`

### Technology Stack

**Frontend Technologies:**
- **Alpine.js** - Lightweight JavaScript framework for interactivity
- **Tailwind CSS** - Utility-first CSS framework
- **Lucide Icons** - SVG icon library (`<i data-lucide="icon-name">`)
- **Custom Animations** - CSS animations (`animate-fadeInUp`, `animate-pulse`, etc.)

**Features:**
- Full RTL/LTR support for all languages
- Dark mode with system preference detection
- Multi-language content management
- Responsive design (mobile-first)
- Accessibility features (ARIA labels, keyboard navigation)

---

## Directory Structure

```
resources/views/components/
├── admin/                          # Admin panel components (90+ components)
│   ├── form/                       # Form controls (9 components)
│   │   ├── checkbox.blade.php
│   │   ├── date-picker.blade.php
│   │   ├── file-input.blade.php
│   │   ├── radio.blade.php
│   │   ├── rich-text.blade.php
│   │   ├── select.blade.php
│   │   ├── text-input.blade.php
│   │   ├── textarea.blade.php
│   │   └── wysiwyg.blade.php
│   ├── article/                    # Article-specific components
│   │   ├── blocks/                 # Content blocks for article builder
│   │   │   ├── hadith.blade.php
│   │   │   ├── sahabi.blade.php
│   │   │   ├── seerah.blade.php
│   │   │   ├── verse.blade.php
│   │   │   └── wysiwyg.blade.php
│   │   ├── tabs/                   # Article form wizard tabs
│   │   │   ├── content.blade.php
│   │   │   ├── gallery.blade.php
│   │   │   ├── media.blade.php
│   │   │   ├── seo.blade.php
│   │   │   └── stats.blade.php
│   │   ├── action-bar.blade.php
│   │   └── sidebar.blade.php
│   ├── media/                      # Media picker components
│   │   ├── picker-grid.blade.php
│   │   ├── picker-header.blade.php
│   │   ├── picker-tabs.blade.php
│   │   └── picker-upload.blade.php
│   ├── activity-feed.blade.php
│   ├── audio-player.blade.php
│   ├── bulk-delete-bar.blade.php
│   ├── category-tree-item.blade.php
│   ├── chart-card.blade.php
│   ├── color-picker.blade.php
│   ├── confirm-modal.blade.php
│   ├── data-table.blade.php
│   ├── form-builder.blade.php
│   ├── form-section.blade.php
│   ├── gradient-picker.blade.php
│   ├── header.blade.php
│   ├── hijri-date-picker.blade.php
│   ├── icon-picker.blade.php
│   ├── image-upload.blade.php
│   ├── language-tab-panel.blade.php
│   ├── language-tabs.blade.php
│   ├── list-card.blade.php
│   ├── media-card.blade.php
│   ├── media-filter.blade.php
│   ├── media-library-input.blade.php
│   ├── media-picker-modal.blade.php
│   ├── media-upload-modal.blade.php
│   ├── module-selector-modal.blade.php
│   ├── muslim-picker-modal.blade.php
│   ├── place-picker.blade.php
│   ├── program-picker-modal.blade.php
│   ├── publish-scheduler.blade.php
│   ├── repeatable-field.blade.php
│   ├── selected-media-preview.blade.php
│   ├── sidebar.blade.php
│   ├── stats-card.blade.php
│   ├── table-header.blade.php
│   ├── table-row.blade.php
│   ├── tag-merge-modal.blade.php
│   ├── toast-container.blade.php
│   ├── toc-manager.blade.php
│   └── upload-progress.blade.php
├── site/                           # Public site components (20+ components)
│   ├── article-card.blade.php
│   ├── audio-player.blade.php
│   ├── auth-bg.blade.php
│   ├── ayah-marker.blade.php
│   ├── badge.blade.php
│   ├── bookmark-card.blade.php
│   ├── brand-icon.blade.php
│   ├── breadcrumb.blade.php
│   ├── daily-hadith.blade.php
│   ├── daily-panel.blade.php
│   ├── daily-verse.blade.php
│   ├── empty-state.blade.php
│   ├── expandable-text.blade.php
│   ├── filter-pills.blade.php
│   ├── footer.blade.php
│   ├── hadith-language-toggle.blade.php
│   ├── header.blade.php
│   ├── login-modal.blade.php
│   ├── logo.blade.php
│   ├── mushaf-icon.blade.php
│   ├── pagination.blade.php
│   ├── sahaba-card.blade.php
│   ├── scripture-pane.blade.php
│   ├── share-button.blade.php
│   ├── skeleton.blade.php
│   └── view-all-link.blade.php
├── public/                         # Public-specific components
│   ├── genealogy/
│   │   └── spine-view.blade.php
│   ├── quran/
│   └── article/
├── quran/                          # Quran-specific components
│   └── answer-renderer.blade.php
└── icons/                          # Custom icon components
    ├── kaaba.blade.php
    └── quran-book.blade.php
```

---

## Component Categories

### 1. Form Components (`admin/form/*`)

Standardized form controls with multi-language support, validation, and dark mode.

**Available Components:**
- `text-input` - Single/multi-line text input with icon support
- `textarea` - Multi-line text area with character count
- `select` - Dropdown select with search
- `checkbox` - Checkbox input with label
- `radio` - Radio button group
- `date-picker` - Date picker with Hijri/Gregorian support
- `file-input` - File upload with preview
- `wysiwyg` - WYSIWYG editor (TinyMCE/similar)
- `rich-text` - Rich text editor

**Common Features:**
- Multi-language support via `multilang` prop
- Automatic RTL/LTR direction based on locale
- Validation error display
- Dark mode support
- Icon support (Lucide icons)
- Help text and character counters
- Focus states with glow effect

### 2. Layout Components

**Core Layout:**
- `admin.header` - Admin panel sticky header with breadcrumbs
- `admin.sidebar` - Collapsible navigation sidebar
- `site.header` - Public site header with navigation
- `site.footer` - Public site footer

### 3. Data Display Components

**Tables & Lists:**
- `admin.data-table` - Feature-rich data table with sorting, filtering, pagination
- `admin.table-header` - Table header row
- `admin.table-row` - Table body row
- `admin.list-card` - List item card

**Cards:**
- `admin.stats-card` - Statistics card with counter animation
- `admin.chart-card` - Chart container card
- `admin.media-card` - Media item card
- `site.article-card` - Article preview card
- `site.sahaba-card` - Companion profile card
- `site.bookmark-card` - Bookmark card

### 4. Modal Components

**Modals & Dialogs:**
- `admin.media-picker-modal` - Media library picker (1,161 lines)
- `admin.media-upload-modal` - Media upload dialog
- `admin.confirm-modal` - Confirmation dialog
- `admin.tag-merge-modal` - Tag merging interface
- `admin.module-selector-modal` - Module selection modal
- `admin.muslim-picker-modal` - Muslim/Scholar picker
- `admin.program-picker-modal` - Media program picker
- `site.login-modal` - User login modal

### 5. Specialized Components

**Content Blocks (Article Builder):**
- `admin.article.blocks.wysiwyg` - Rich text content block
- `admin.article.blocks.verse` - Quranic verse block
- `admin.article.blocks.hadith` - Hadith citation block
- `admin.article.blocks.sahabi` - Companion reference block
- `admin.article.blocks.seerah` - Seerah event block

**Media Management:**
- `admin.media.picker-header` - Media picker header
- `admin.media.picker-tabs` - Media picker tabs
- `admin.media.picker-grid` - Media grid display
- `admin.media.picker-upload` - Upload interface

**Utilities:**
- `admin.language-tabs` - Language tab switcher
- `admin.language-tab-panel` - Individual language panel
- `admin.hijri-date-picker` - Islamic calendar date picker
- `admin.color-picker` - Color selection tool
- `admin.gradient-picker` - Gradient builder
- `admin.icon-picker` - Icon selection tool
- `admin.place-picker` - Location picker
- `admin.toc-manager` - Table of contents manager
- `admin.repeatable-field` - Repeatable field group

---

## Common Patterns

### 1. Props Definition

All components define their props using the `@props` directive with default values:

```blade
@props([
    'name' => '',
    'label' => '',
    'required' => false,
    'disabled' => false,
    'multilang' => false,
    'helpText' => '',
])
```

### 2. Multi-Language Support

Components with `multilang` prop automatically generate inputs for all supported languages:

```blade
@php
    $supportedLanguages = config('translatable.locales');
    $languageNames = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
        'tr' => 'Türkçe',
        'ur' => 'اردو',
    ];
@endphp

@if($multilang)
    <!-- Generate tabs for each language -->
    @foreach($supportedLanguages as $lang)
        <input name="{{ $name }}[{{ $lang }}]" 
               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}" />
    @endforeach
@endif
```

### 3. Dark Mode Support

All components use Alpine.js `darkMode` reactive variable:

```blade
<div :class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'">
    <!-- Component content -->
</div>
```

### 4. RTL/LTR Direction

Direction is automatically set based on the active language:

```blade
dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
```

Use logical properties in Tailwind for RTL-aware spacing:
- `ms-4` instead of `ml-4` (margin-start)
- `me-2` instead of `mr-2` (margin-end)
- `ps-3` instead of `pl-3` (padding-start)
- `pe-3` instead of `pr-3` (padding-end)

### 5. Validation & Error Display

Components automatically display validation errors:

```blade
@if($errors->has($name))
    <div class="mt-2 flex items-start gap-2 text-red-500 text-sm">
        <i data-lucide="alert-circle" class="w-4 h-4"></i>
        <span>{{ $errors->first($name) }}</span>
    </div>
@endif
```

For multi-language fields:

```blade
@if($errors->has("{$name}.{$lang}"))
    <div class="mt-2 text-red-500 text-sm">
        {{ $errors->first("{$name}.{$lang}") }}
    </div>
@endif
```

### 6. Alpine.js Component Pattern

Components use Alpine.js for interactivity:

```blade
<div x-data="{
        isOpen: false,
        activeTab: 'content',
        init() {
            // Initialization logic
        },
        toggle() {
            this.isOpen = !this.isOpen;
        }
    }">
    <!-- Component markup -->
</div>
```

### 7. Icon Usage

Lucide icons are initialized via `lucide.createIcons()`:

```blade
<i data-lucide="edit" class="w-5 h-5"></i>
```

Icons are automatically converted to SVG on page load and after dynamic content changes.

---

## Usage Examples

### Example 1: Basic Text Input

```blade
<x-admin.form.text-input 
    name="email"
    label="البريد الإلكتروني"
    type="email"
    placeholder="example@example.com"
    required
    icon="mail"
    helpText="سيتم استخدام هذا البريد للتواصل معك"
/>
```

### Example 2: Multi-Language Text Input

```blade
<x-admin.form.text-input 
    name="title"
    label="العنوان"
    :multilang="true"
    required
    maxlength="200"
/>
```

This generates separate inputs for each language (ar, en, fr, tr, ur) with:
- Language tabs for switching
- RTL/LTR direction per language
- Error display per language
- Character counter per language

### Example 3: Language Tabs Container

```blade
<x-admin.language-tabs 
    :languages="['ar', 'en', 'fr', 'tr', 'ur']"
    :errors="$errors"
    defaultLanguage="ar"
    :showCompletionStatus="true"
    :showCopyButton="true">
    
    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
        <x-admin.language-tab-panel :language="$lang">
            <x-admin.form.text-input name="title_{{ $lang }}" label="العنوان" required />
            <x-admin.form.textarea name="excerpt_{{ $lang }}" label="الملخص" />
        </x-admin.language-tab-panel>
    @endforeach
</x-admin.language-tabs>
```

**Features:**
- Tabs with language flags and names
- Validation error indicators per tab
- Completion progress bar (% of required fields filled)
- "Copy from language" dropdown to duplicate content
- Mobile-responsive dropdown for language selection

### Example 4: Stats Card

```blade
{{-- Basic stats card --}}
<x-admin.stats-card 
    icon="users"
    title="إجمالي المستخدمين"
    :value="$totalUsers"
    change="+12%"
    changeType="up"
/>

{{-- Gradient stats card with link --}}
<x-admin.stats-card 
    icon="file-text"
    title="المقالات المنشورة"
    :value="$publishedArticles"
    :gradient="true"
    href="{{ route('admin.articles.index', ['status' => 'published']) }}"
/>

{{-- Compact horizontal layout --}}
<x-admin.stats-card 
    icon="eye"
    title="المشاهدات اليوم"
    :value="$todayViews"
    :compact="true"
/>
```

**Features:**
- Animated counter (counts up from 0 to value)
- Gradient background option
- Change indicator with up/down arrow
- Clickable variant with "View details" link
- Compact horizontal layout for dashboards

### Example 5: Data Table

```blade
<x-admin.data-table 
    :columns="[
        ['key' => 'title', 'label' => 'العنوان', 'sortable' => true],
        ['key' => 'category', 'label' => 'التصنيف'],
        ['key' => 'status', 'label' => 'الحالة'],
        ['key' => 'created_at', 'label' => 'تاريخ الإنشاء', 'sortable' => true],
    ]"
    :data="$articles"
    :actions="['edit', 'delete', 'view']"
    :bulkActions="['delete', 'publish']"
    searchable
    filterable
    exportable
    :perPageOptions="[10, 25, 50, 100]"
    emptyTitle="لا توجد مقالات"
/>
```

### Example 6: Media Picker Modal

```blade
{{-- Place modal component once in layout --}}
<x-admin.media-picker-modal collection="articles" />

{{-- Trigger from JavaScript --}}
<button onclick="window.MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (media) => {
        console.log('Selected:', media);
    }
})">
    اختر صورة
</button>
```

**JavaScript API:**
```javascript
// Open picker
window.MediaPicker.open({
    mode: 'single|multiple',      // Selection mode
    type: 'image|video|all',       // Media type filter
    collection: 'articles',        // Upload collection
    onSelect: (media) => { }       // Callback function
});

// Close picker
window.MediaPicker.close();

// Get selected media
const selected = window.MediaPicker.getSelected();
```

### Example 7: Content Block (Article Builder)

```blade
{{-- Hadith block --}}
<x-admin.article.blocks.hadith locale="ar" />

{{-- Verse block --}}
<x-admin.article.blocks.verse locale="ar" />

{{-- Sahabi block --}}
<x-admin.article.blocks.sahabi locale="ar" />
```

These are used within an Alpine.js data structure:

```javascript
blocks: [
    {
        type: 'hadith',
        text: 'متن الحديث',
        collection: 'صحيح البخاري',
        number: '1234',
        grade: 'sahih'
    },
    {
        type: 'verse',
        text: 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
        surah: 'الفاتحة',
        ayah: '1'
    }
]
```

---

## Props Documentation

### Common Props (Most Components)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | string | `''` | Input field name (required for form fields) |
| `label` | string | `''` | Field label |
| `required` | boolean | `false` | Mark field as required |
| `disabled` | boolean | `false` | Disable input |
| `readonly` | boolean | `false` | Make input read-only |
| `multilang` | boolean | `false` | Enable multi-language tabs |
| `helpText` | string | `''` | Helper text below field |

### Text Input Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `type` | string | `'text'` | Input type (text, email, password, url, etc.) |
| `value` | string | `''` | Initial value |
| `placeholder` | string | `''` | Placeholder text |
| `icon` | string | `null` | Lucide icon name (displays on start) |
| `maxlength` | int | `null` | Maximum character length |
| `minlength` | int | `null` | Minimum character length |
| `pattern` | string | `null` | Regex validation pattern |
| `autocomplete` | string | `null` | Autocomplete attribute |

### Textarea Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `rows` | int | `4` | Number of visible rows |
| `maxlength` | int | `null` | Maximum character length |
| `resizable` | boolean | `true` | Allow vertical resize |

### Select Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `options` | array | `[]` | Array of options `[['value' => '1', 'label' => 'Option 1']]` |
| `multiple` | boolean | `false` | Allow multiple selections |
| `searchable` | boolean | `false` | Enable search/filter |
| `placeholder` | string | `'Select...'` | Placeholder text |

### Stats Card Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `icon` | string | `'activity'` | Lucide icon name |
| `title` | string | `'Statistic'` | Card title/label |
| `value` | string\|number | `'0'` | Statistic value (auto-animates if numeric) |
| `change` | string | `null` | Change indicator (e.g., '+12%') |
| `changeType` | string | `'up'` | 'up' or 'down' (affects color) |
| `gradient` | boolean | `false` | Use gradient background |
| `compact` | boolean | `false` | Compact horizontal layout |
| `delay` | string | `''` | Animation delay CSS class |
| `href` | string | `null` | Make card clickable with link |

### Language Tabs Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `languages` | array | `['ar','en','fr','tr','ur']` | Languages to show |
| `errors` | ErrorBag | `null` | Validation error bag |
| `defaultLanguage` | string | `'ar'` | Initial active language |
| `showCompletionStatus` | boolean | `true` | Show completion progress bar |
| `showCopyButton` | boolean | `true` | Show "copy from language" button |

### Data Table Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | array | `[]` | Column definitions (required) |
| `data` | Collection\|array | `[]` | Table data (required) |
| `actions` | array | `[]` | Row actions: 'edit', 'delete', 'view', 'duplicate' |
| `bulkActions` | array | `[]` | Bulk actions: 'delete', 'publish', 'export' |
| `searchable` | boolean | `false` | Enable search |
| `filterable` | boolean | `false` | Enable filter sidebar |
| `exportable` | boolean | `false` | Enable export button |
| `perPageOptions` | array | `[10,25,50]` | Per-page options |
| `emptyTitle` | string | `''` | Empty state title |

---

## Migration Guide

### When to Split Large Templates into Components

**Indicators that a template needs refactoring:**

1. **File size > 500 lines** - Loses readability advantage of template engines
2. **Repeated code blocks** - Same pattern appears 3+ times
3. **Distinct sections** - Clear logical boundaries (tabs, steps, sections)
4. **Merge conflicts** - Frequent conflicts due to single-file changes
5. **Hard to maintain** - Difficult to find and fix issues

**Current Large Templates (As of 2026-09-13):**

| File | Lines | Status | Recommendation |
|------|-------|--------|----------------|
| `admin/articles/_form-wizard.blade.php` | 1,970 | ❌ Needs splitting | Split into 5+ tab components |
| `public/genealogy/index.blade.php` | 1,705 | ❌ Needs splitting | Extract tree view component |
| `public/articles/show.blade.php` | 1,443 | ❌ Needs splitting | Extract content renderer components |
| `admin/media/index.blade.php` | 1,339 | ❌ Needs splitting | Extract grid/filter components |
| `public/quran/surah.blade.php` | 1,309 | ❌ Needs splitting | Extract verse/tafsir components |
| `admin/articles/_form-new.blade.php` | 1,289 | ❌ Needs splitting | Consolidate with form-wizard |
| `components/admin/media-picker-modal.blade.php` | 1,161 | ⚠️ Already split | Further split if grows |

### Step-by-Step Migration Process

**Step 1: Identify Logical Boundaries**

For example, a 1,970-line form wizard likely has these sections:
- Basic Info (title, slug, category)
- Content (rich text editor, content blocks)
- Media (featured image, gallery)
- SEO (meta title, description, keywords)
- Scheduling (publish date, featured status)

**Step 2: Create Component Files**

```bash
# Create directory structure
mkdir -p resources/views/components/admin/article/tabs

# Create component files
touch resources/views/components/admin/article/tabs/basic-info.blade.php
touch resources/views/components/admin/article/tabs/content.blade.php
touch resources/views/components/admin/article/tabs/media.blade.php
touch resources/views/components/admin/article/tabs/seo.blade.php
touch resources/views/components/admin/article/tabs/scheduling.blade.php
```

**Step 3: Extract Code**

Move each section's code into its component file:

```blade
{{-- resources/views/components/admin/article/tabs/basic-info.blade.php --}}
@props([
    'article' => null,
    'categories' => [],
])

<div class="form-section">
    <h3 class="text-lg font-semibold mb-4">المعلومات الأساسية</h3>
    
    <x-admin.form.text-input 
        name="title"
        label="العنوان"
        :value="$article?->title"
        :multilang="true"
        required
    />
    
    <x-admin.form.select 
        name="category_id"
        label="التصنيف"
        :options="$categories"
        :value="$article?->category_id"
        required
    />
    
    <!-- More fields... -->
</div>
```

**Step 4: Replace in Original Template**

```blade
{{-- Before (1,970 lines) --}}
<form>
    <!-- 400 lines of basic info fields -->
    <!-- 600 lines of content editor -->
    <!-- 300 lines of media uploader -->
    <!-- 400 lines of SEO fields -->
    <!-- 270 lines of scheduling -->
</form>

{{-- After (< 100 lines) --}}
<form>
    <x-admin.language-tabs>
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <x-admin.language-tab-panel :language="$lang">
                <x-admin.article.tabs.basic-info 
                    :article="$article"
                    :categories="$categories"
                />
            </x-admin.language-tab-panel>
        @endforeach
    </x-admin.language-tabs>
    
    <x-admin.article.tabs.media :article="$article" />
    <x-admin.article.tabs.seo :article="$article" />
    <x-admin.article.tabs.scheduling :article="$article" />
</form>
```

**Step 5: Test Thoroughly**

- [ ] All form fields still present
- [ ] Validation still works
- [ ] Multi-language functionality intact
- [ ] Dark mode works correctly
- [ ] Mobile responsive
- [ ] No JavaScript errors

### Reuse Opportunities

Once extracted, components can be reused across different content types:

```blade
{{-- Reuse media tab in multiple forms --}}
<x-admin.article.tabs.media :model="$article" />      {{-- Articles --}}
<x-admin.article.tabs.media :model="$seerahEvent" />  {{-- Seerah Events --}}
<x-admin.article.tabs.media :model="$muslimProfile" />{{-- Muslim Profiles --}}
```

---

## Best Practices

### 1. Component Naming

**✅ Good:**
- `text-input.blade.php` - Descriptive, hyphen-separated
- `media-picker-modal.blade.php` - Clear purpose
- `article/tabs/content.blade.php` - Organized by domain

**❌ Bad:**
- `input.blade.php` - Too generic
- `TextInput.blade.php` - Wrong casing (use kebab-case)
- `media_picker.blade.php` - Use hyphens, not underscores

### 2. Props Documentation

Always document props at the top of the component:

```blade
{{--
/**
 * Component Name
 *
 * Brief description of what the component does.
 *
 * @props
 * - name: string (required) - Field name
 * - label: string (optional) - Field label
 * - required: boolean (default: false) - Mark as required
 *
 * @example
 * <x-admin.form.text-input name="email" label="Email" required />
 */
--}}

@props([
    'name' => '',
    'label' => '',
    'required' => false,
])
```

### 3. Multi-Language Support

Use the standardized multi-language pattern:

```blade
@if($multilang)
    @foreach(config('translatable.locales') as $lang)
        <input name="{{ $name }}[{{ $lang }}]"
               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
               value="{{ old("{$name}.{$lang}", $value[$lang] ?? '') }}" />
    @endforeach
@else
    <input name="{{ $name }}" value="{{ old($name, $value) }}" />
@endif
```

### 4. Dark Mode Support

Use Alpine.js reactive `darkMode` variable:

```blade
<div :class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'">
```

Or for static classes:

```blade
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
```

### 5. Validation Errors

Always display validation errors:

```blade
@error($name)
    <div class="mt-2 text-red-500 text-sm">
        <i data-lucide="alert-circle" class="w-4 h-4 inline"></i>
        {{ $message }}
    </div>
@enderror
```

### 6. Accessibility

- Always include labels for form fields
- Use semantic HTML (`<button>` not `<div onclick>`)
- Add ARIA labels for icon-only buttons
- Ensure keyboard navigation works
- Use sufficient color contrast

```blade
<button type="button" aria-label="إغلاق">
    <i data-lucide="x" class="w-5 h-5"></i>
</button>
```

### 7. Performance

- Use `@once` for scripts that should only load once
- Defer JavaScript execution when possible
- Lazy-load images with `loading="lazy"`
- Use `x-cloak` to prevent Alpine.js flicker

```blade
@once
    @push('scripts')
    <script>
        // Component JavaScript
    </script>
    @endpush
@endonce
```

### 8. Icon Initialization

Reinitialize Lucide icons after dynamic content changes:

```blade
<div x-data="{ showMore: false }">
    <button @click="showMore = !showMore; $nextTick(() => lucide.createIcons())">
        <i data-lucide="chevron-down"></i>
    </button>
    
    <div x-show="showMore">
        <!-- Dynamic content with icons -->
    </div>
</div>
```

### 9. Component Size Limits

**Recommended maximum sizes:**
- Simple components (inputs, buttons): < 150 lines
- Form sections: < 300 lines
- Modals: < 500 lines
- Complex components (data tables): < 800 lines

**If exceeding limits:**
- Split into sub-components
- Extract JavaScript to separate file
- Use slots for customizable sections

### 10. Testing Checklist

Before committing a new component:

- [ ] Works with Arabic (RTL)
- [ ] Works with English (LTR)
- [ ] Dark mode displays correctly
- [ ] Responsive on mobile (320px+)
- [ ] Validation errors display
- [ ] Icons render properly
- [ ] Alpine.js reactivity works
- [ ] No console errors
- [ ] Accessibility (keyboard navigation, screen readers)
- [ ] Props documented

---

## Additional Resources

### Related Documentation

- **Translation Patterns** - `docs/TRANSLATION_PATTERNS.md`
- **Form Builder Guide** - `docs/FORM-BUILDER-GUIDE.md`
- **Admin Design System** - `docs/ADMIN-DESIGN-SYSTEM.md`
- **DataTable API** - `docs/DATATABLE-API-DOCUMENTATION.md`
- **Media Picker Architecture** - `docs/MEDIA-PICKER-ARCHITECTURE.md`

### External References

- **Laravel Blade Components** - https://laravel.com/docs/13.x/blade#components
- **Alpine.js Documentation** - https://alpinejs.dev/
- **Tailwind CSS** - https://tailwindcss.com/docs
- **Lucide Icons** - https://lucide.dev/icons/

---

**Maintained by:** Development Team  
**For questions or updates:** Contact via project repository
