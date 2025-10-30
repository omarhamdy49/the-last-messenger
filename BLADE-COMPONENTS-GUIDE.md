# Blade Components Guide

## Overview

This guide documents the reusable Blade components created for the admin panel. All components follow the project's design system with blue-green gradient theme, dark mode support, and RTL compatibility.

## Components

### 1. Toast Notification Component

**File:** `resources/views/admin/components/toast.blade.php`

**Description:**
A toast notification system that displays temporary messages with automatic dismissal. Fully integrated with Laravel session messages and Alpine.js.

**Features:**
- 4 notification types: success, error, warning, info
- Auto-dismissal after 5 seconds
- Slide-in animation from left (RTL-aware)
- Manual close button
- Dark mode compatible
- Laravel session integration
- Global JavaScript function for easy access

**Usage in Layout:**
```blade
<!-- Include in your main layout (already added to admin layout) -->
@include('admin.components.toast')
```

**Usage in Controller:**
```php
// Success message
return redirect()->back()->with('success', 'تم الحفظ بنجاح');

// Error message
return redirect()->back()->with('error', 'حدث خطأ أثناء العملية');

// Warning message
return redirect()->back()->with('warning', 'يرجى التحقق من البيانات');

// Info message
return redirect()->back()->with('info', 'تم تحديث النظام');
```

**Usage in JavaScript:**
```javascript
// Show success toast
window.showToast('تم الحفظ بنجاح', 'success');

// Show error toast
window.showToast('حدث خطأ', 'error');

// Show warning toast
window.showToast('تحذير', 'warning');

// Show info toast
window.showToast('معلومة', 'info');
```

**Props:** None (automatically detects Laravel session messages)

---

### 2. File Uploader Component

**File:** `resources/views/admin/components/file-uploader.blade.php`

**Description:**
A drag-and-drop file uploader with preview support for images, progress bar, and file validation.

**Features:**
- Drag & drop interface with hover state
- Click to browse files
- Image preview thumbnails
- File type icons for non-images
- File size validation
- Multiple file support
- Progress bar during upload
- Delete uploaded files
- Dark mode compatible
- RTL support

**Usage:**
```blade
<!-- Basic image uploader -->
@include('admin.components.file-uploader', [
    'name' => 'images',
    'accept' => 'image/*',
    'multiple' => true,
    'maxSize' => 5,
    'label' => 'رفع الصور'
])

<!-- Video uploader (single file) -->
@include('admin.components.file-uploader', [
    'name' => 'video',
    'accept' => 'video/mp4,video/webm',
    'multiple' => false,
    'maxSize' => 50,
    'label' => 'رفع الفيديو'
])

<!-- Document uploader -->
@include('admin.components.file-uploader', [
    'name' => 'documents',
    'accept' => '.pdf,.doc,.docx',
    'multiple' => true,
    'maxSize' => 10,
    'label' => 'رفع المستندات'
])
```

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | string | `'files'` | Input name attribute |
| `accept` | string | `'image/*,video/*,.pdf'` | Accepted file types |
| `multiple` | boolean | `true` | Allow multiple files |
| `maxSize` | integer | `10` | Max file size in MB |
| `label` | string | `'رفع الملفات'` | Label text |

**Server-Side Integration:**
Currently implements simulated upload. To integrate with your backend:

1. Update the `uploadSingleFile()` function in the component
2. Replace the simulation code with actual fetch/axios call
3. Handle file upload in your controller

Example integration:
```javascript
async uploadSingleFile(file) {
    const formData = new FormData();
    formData.append(this.name, file);

    try {
        const response = await fetch('/api/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        fileObj.url = data.url; // Store returned URL
        window.showToast(`تم رفع ${file.name} بنجاح`, 'success');
    } catch (error) {
        window.showToast(`فشل رفع ${file.name}`, 'error');
        this.deleteFile(id);
    }
}
```

---

### 3. Skeleton Loading Components

Animated skeleton loaders to display while content is being fetched. Improves perceived performance and user experience.

#### 3.1 Table Skeleton

**File:** `resources/views/admin/components/skeleton-table.blade.php`

**Description:**
Skeleton loader for data tables with animated shimmer effect.

**Usage:**
```blade
<!-- Default 5 rows -->
@include('admin.components.skeleton-table')

<!-- Custom number of rows -->
@include('admin.components.skeleton-table', ['rows' => 10])
```

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `rows` | integer | `5` | Number of skeleton rows |

**Example with Alpine.js:**
```blade
<div x-data="{ loading: true }">
    <!-- Show skeleton while loading -->
    <div x-show="loading">
        @include('admin.components.skeleton-table', ['rows' => 8])
    </div>

    <!-- Show actual table when loaded -->
    <div x-show="!loading">
        <table>
            <!-- Your table content -->
        </table>
    </div>
</div>
```

#### 3.2 Card Skeleton

**File:** `resources/views/admin/components/skeleton-card.blade.php`

**Description:**
Skeleton loader for card-based layouts with image placeholder.

**Usage:**
```blade
<!-- Single card skeleton -->
@include('admin.components.skeleton-card')

<!-- Multiple cards in grid -->
<div class="grid md:grid-cols-3 gap-6">
    @for ($i = 0; $i < 6; $i++)
        @include('admin.components.skeleton-card')
    @endfor
</div>
```

**Props:** None

#### 3.3 Form Skeleton

**File:** `resources/views/admin/components/skeleton-form.blade.php`

**Description:**
Skeleton loader for forms with various field types.

**Usage:**
```blade
<!-- Default 4 fields -->
@include('admin.components.skeleton-form')

<!-- Custom number of fields -->
@include('admin.components.skeleton-form', ['fields' => 6])
```

**Props:**
| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `fields` | integer | `4` | Number of skeleton fields |

---

## Design System

All components use the project's unified design system:

### Colors

**Primary Gradient:**
```css
background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
```

**Accent Color:**
```css
color: #64CCC5; /* Teal accent for hover states */
```

**Usage in Tailwind:**
```html
<!-- Primary gradient -->
<div class="bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>

<!-- Accent color -->
<div class="text-[#64CCC5] hover:text-[#64CCC5]"></div>
```

### Dark Mode

All components support dark mode using Alpine.js:

```html
<div :class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'">
    <!-- Content -->
</div>
```

### RTL Support

Components use logical properties for RTL compatibility:

```html
<!-- Use start/end instead of left/right -->
<div class="ms-4 me-2">  <!-- NOT: ml-4 mr-2 -->
<div class="ps-3 pe-3">  <!-- NOT: pl-3 pr-3 -->
```

### Typography

**Font Family:** Cairo (Arabic-optimized)
```css
font-family: 'Cairo', sans-serif;
```

**Font Weights:**
- Light: 300
- Regular: 400
- Semibold: 600
- Bold: 700
- Black: 900

### Icons

**Library:** Lucide Icons

**Usage:**
```html
<i data-lucide="icon-name" class="w-5 h-5"></i>
```

**Reinitialize after dynamic content:**
```javascript
// After Alpine updates DOM
this.$nextTick(() => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
```

### Animations

**Available Animations:**
```css
animate-fade-in-up    /* Fade in with upward movement */
animate-slide-in      /* Slide from left/right (RTL-aware) */
animate-shimmer       /* Shimmer loading effect */
animate-pulse-glow    /* Pulsing glow effect */
```

**Custom Keyframes:**
```javascript
// Already configured in Tailwind config
fadeInUp: {
    '0%': { opacity: '0', transform: 'translateY(20px)' },
    '100%': { opacity: '1', transform: 'translateY(0)' }
},
shimmer: {
    '0%': { backgroundPosition: '-1000px 0' },
    '100%': { backgroundPosition: '1000px 0' }
}
```

---

## Demo Page

**URL:** `/su/portal/v1/admin/components-demo`

**File:** `resources/views/admin/components-demo.blade.php`

A comprehensive demo page showcasing all components with:
- Live interactive examples
- Usage code snippets
- Feature lists
- Integration examples

**Access:** Login to admin panel and navigate to the Components Demo route.

---

## Best Practices

### 1. Component Reusability
Always pass configuration via props instead of hardcoding:
```blade
<!-- Good -->
@include('admin.components.file-uploader', ['maxSize' => 10])

<!-- Bad -->
<!-- Hardcoded maxSize in component -->
```

### 2. Dark Mode Consistency
Always use Alpine.js `darkMode` variable for conditional styling:
```html
:class="darkMode ? 'bg-gray-800' : 'bg-white'"
```

### 3. Icon Reinitialization
Reinitialize Lucide icons after dynamic content changes:
```javascript
this.$nextTick(() => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
```

### 4. Loading States
Use skeleton loaders for better perceived performance:
```blade
<div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 2000)">
    <div x-show="loading">
        @include('admin.components.skeleton-table')
    </div>
    <div x-show="!loading">
        <!-- Actual content -->
    </div>
</div>
```

### 5. Toast Notifications
Use appropriate toast types for user actions:
- **Success:** Completed actions (saved, deleted, updated)
- **Error:** Failed operations, validation errors
- **Warning:** Important notices, confirmations needed
- **Info:** System messages, status updates

---

## File Structure

```
resources/views/admin/
├── components/
│   ├── toast.blade.php              # Toast notification system
│   ├── file-uploader.blade.php      # Drag & drop file uploader
│   ├── skeleton-table.blade.php     # Table skeleton loader
│   ├── skeleton-card.blade.php      # Card skeleton loader
│   └── skeleton-form.blade.php      # Form skeleton loader
├── components-demo.blade.php         # Demo page showcasing all components
└── ...
```

---

## Integration Checklist

When creating a new admin page:

- [ ] Include toast component in layout (already done globally)
- [ ] Add loading skeleton for initial data fetch
- [ ] Use file uploader for image/file inputs
- [ ] Show success/error toasts for user actions
- [ ] Ensure dark mode compatibility
- [ ] Test RTL layout
- [ ] Reinitialize Lucide icons after dynamic updates
- [ ] Follow design system colors and typography

---

## Browser Support

All components are tested and work on:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

**Requirements:**
- Modern browser with CSS Grid support
- JavaScript enabled
- Support for CSS backdrop-filter (for glassmorphism)

---

## Troubleshooting

### Icons not showing
```javascript
// Reinitialize Lucide icons
lucide.createIcons();
```

### Toast not appearing
```javascript
// Ensure toast component is included in layout
// Check browser console for errors
// Verify window.showToast is defined
```

### File uploader not working
```javascript
// Check Alpine.js is loaded
// Verify file input has correct accept attribute
// Check maxSize validation
// Look for errors in browser console
```

### Skeleton not animating
```css
/* Ensure shimmer keyframe animation is defined in CSS */
@keyframes shimmer { ... }
```

---

## Future Enhancements

Potential improvements for future versions:

1. **Pagination Component** - Reusable pagination with various styles
2. **Modal Component** - Customizable modal dialogs
3. **Dropdown Menu** - Multi-level dropdown menus
4. **Data Table Component** - Full-featured data table with sorting, filtering
5. **Form Builder** - Dynamic form generator from configuration
6. **Chart Components** - Reusable chart wrappers for Chart.js
7. **Search Component** - Advanced search with filters
8. **Timeline Component** - Visual timeline for events
9. **Calendar Component** - Date picker and event calendar
10. **Rich Text Editor** - WYSIWYG editor integration

---

## Contributing

When adding new components:

1. Follow the existing naming convention
2. Include comprehensive documentation
3. Support dark mode and RTL
4. Use project design system colors
5. Add usage examples
6. Test on multiple browsers
7. Update this guide

---

## License

These components are part of The Last Messenger project and follow the same license.

---

**Last Updated:** 2025-10-29
**Version:** 1.0.0
