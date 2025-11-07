# Admin Dashboard Design System

Netflix-grade RTL-ready admin interface design system with comprehensive UI components and utilities.

## Table of Contents

1. [Color Palette](#color-palette)
2. [Typography](#typography)
3. [Spacing & Layout](#spacing--layout)
4. [Components](#components)
5. [Utilities](#utilities)
6. [Animations](#animations)
7. [Dark Mode](#dark-mode)
8. [RTL Support](#rtl-support)

---

## Color Palette

### Primary Colors

```css
/* Blue-Green Gradient */
Primary Blue:    #0056b3  (rgb(0, 86, 179))
Secondary Green: #218838  (rgb(33, 136, 56))
Accent Teal:     #64CCC5  (rgb(100, 204, 197))
```

### Tailwind Classes

```html
<!-- Primary Blue (50-900 scale) -->
<div class="bg-primary-500 text-primary-900"></div>

<!-- Secondary Green (50-900 scale) -->
<div class="bg-secondary-500 text-secondary-900"></div>

<!-- Accent Teal (50-900 scale) -->
<div class="bg-accent-500 text-accent-900"></div>
```

### Gradient Backgrounds

```html
<!-- Primary gradient (blue to green) -->
<div class="bg-gradient-primary"></div>
<!-- Linear gradient: #0056b3 → #218838 -->

<!-- Hero gradient (with opacity) -->
<div class="bg-gradient-hero"></div>
<!-- Linear gradient with alpha: rgba(0,86,179,0.95) → rgba(33,136,56,0.9) -->

<!-- Mesh background -->
<div class="bg-mesh-bg"></div>
<!-- Multiple radial gradients for depth -->
```

### Using Gradients in Tailwind

```html
<!-- Blue to Green -->
<div class="bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>

<!-- Teal to Blue -->
<div class="bg-gradient-to-r from-[#64CCC5] to-[#0056b3]"></div>

<!-- Reverse gradient -->
<div class="bg-gradient-to-r from-[#218838] to-[#0056b3]"></div>
```

---

## Typography

### Font Family

**Primary Font:** Cairo (Arabic-optimized)

```css
font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

**Arabic Font:** Amiri (for elegant Arabic text)

```html
<p class="font-arabic">النص العربي</p>
```

### Font Weights

| Class | Weight | Use Case |
|-------|--------|----------|
| `font-light` | 300 | Subtle text, captions |
| `font-normal` | 400 | Body text, paragraphs |
| `font-semibold` | 600 | Subheadings, emphasis |
| `font-bold` | 700 | Headings, CTAs |
| `font-black` | 900 | Hero titles, impact text |

### Gradient Text

```html
<!-- Primary gradient text -->
<h1 class="text-gradient">Gradient Heading</h1>

<!-- Teal accent gradient -->
<h2 class="text-gradient-accent">Accent Heading</h2>

<!-- Reverse gradient -->
<h3 class="text-gradient-reverse">Reverse Heading</h3>

<!-- Shimmer effect (for loading) -->
<p class="text-gradient-shimmer">Loading...</p>
```

### Text Utilities

```html
<!-- Truncate to 2 lines -->
<p class="truncate-2">Long text...</p>

<!-- Truncate to 3 lines -->
<p class="truncate-3">Very long text...</p>
```

---

## Spacing & Layout

### Custom Spacing

| Class | Size | Pixels |
|-------|------|--------|
| `space-18` | 4.5rem | 72px |
| `space-88` | 22rem | 352px |
| `space-100` | 25rem | 400px |
| `space-112` | 28rem | 448px |
| `space-128` | 32rem | 512px |

### Layout Variables

```css
--sidebar-width: 280px;
--sidebar-collapsed-width: 80px;
--header-height: 64px;
```

### Z-Index Scale

```html
<div class="z-60"><!-- 60 --></div>
<div class="z-70"><!-- 70 --></div>
<div class="z-80"><!-- 80 --></div>
<div class="z-90"><!-- 90 --></div>
<div class="z-100"><!-- 100 --></div>
```

---

## Components

### Buttons

#### Primary Button

```html
<button class="btn-primary">
  Primary Action
</button>
```

**Styles:**
- Blue-green gradient background
- White text, bold font
- Shadow with hover lift
- Scale animation on click

#### Secondary Button

```html
<button class="btn-secondary">
  Secondary Action
</button>
```

**Styles:**
- Gray background (light/dark mode)
- Subtle hover effect

#### Ghost Button

```html
<button class="btn-ghost">
  Ghost Action
</button>
```

**Styles:**
- Transparent with border
- Hover changes border color to primary

#### Icon Button

```html
<button class="btn-icon">
  <i data-lucide="settings"></i>
</button>
```

**Styles:**
- Square 40x40px
- Rounded corners
- Hover background

#### Magnetic Button

```html
<button class="btn-magnetic">
  Magnetic Effect
</button>
```

**Styles:**
- White overlay on hover
- Smooth scale animation

### Cards

#### Premium Card

```html
<div class="premium-card group">
  <div class="relative aspect-video">
    <img src="image.jpg" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

    <div class="card-overlay">
      <button class="btn-icon bg-white/90">
        <i data-lucide="play"></i>
      </button>
    </div>
  </div>

  <div class="p-5">
    <h3 class="font-bold text-lg group-hover:text-[#64CCC5] transition-colors">
      Card Title
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">
      Card description
    </p>
  </div>
</div>
```

**Features:**
- Hover lift and scale
- Image zoom on hover
- Overlay with play button
- Teal accent on text hover

#### Glass Card

```html
<div class="glass-card">
  <h3 class="text-lg font-bold mb-4">Glass Card</h3>
  <p>Content with glassmorphism effect</p>
</div>
```

**Features:**
- Backdrop blur
- Semi-transparent background
- Subtle border

#### Card with Shimmer

```html
<div class="premium-card card-shimmer">
  <div class="p-6">
    Content
  </div>
</div>
```

**Features:**
- Shimmer animation on hover
- Light sweep effect

#### Card with Glow

```html
<div class="premium-card card-glow">
  <div class="p-6">
    Content
  </div>
</div>
```

**Features:**
- Glowing shadow on hover
- Blue glow effect

### Form Elements

#### Input

```html
<input type="text" class="input-custom" placeholder="Enter text...">
```

**Features:**
- Rounded corners
- Border focus state (primary color)
- Ring effect on focus
- Dark mode support

#### Select

```html
<select class="select-custom">
  <option>Option 1</option>
  <option>Option 2</option>
</select>
```

**Features:**
- Custom dropdown arrow
- RTL support (arrow position flips)
- Dark mode support

#### Checkbox

```html
<input type="checkbox" class="checkbox-custom">
```

#### Radio

```html
<input type="radio" class="radio-custom">
```

#### Switch Toggle

```html
<button class="switch-toggle" :class="{ 'active': isActive }">
  <span></span>
</button>
```

**Usage with Alpine.js:**

```html
<div x-data="{ enabled: false }">
  <button class="switch-toggle" :class="{ 'active': enabled }" @click="enabled = !enabled">
    <span></span>
  </button>
</div>
```

### Badges

```html
<!-- Primary gradient badge -->
<span class="badge badge-primary">Premium</span>

<!-- Success badge -->
<span class="badge badge-success">Active</span>

<!-- Warning badge -->
<span class="badge badge-warning">Pending</span>

<!-- Danger badge -->
<span class="badge badge-danger">Error</span>

<!-- Info badge -->
<span class="badge badge-info">Info</span>

<!-- Pulsing badge -->
<span class="badge badge-primary badge-pulse">Live</span>
```

### Tables

```html
<table class="table-premium">
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Role</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John Doe</td>
      <td>john@example.com</td>
      <td>Admin</td>
    </tr>
  </tbody>
</table>
```

**Striped variant:**

```html
<table class="table-premium table-striped">
  <!-- ... -->
</table>
```

### Dropdown Menu

```html
<div x-data="{ open: false }" class="relative">
  <button @click="open = !open">
    Open Menu
  </button>

  <div class="dropdown-menu" :class="{ 'show': open }">
    <a href="#" class="dropdown-item">
      <i data-lucide="user"></i>
      <span>Profile</span>
    </a>
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item">
      <i data-lucide="settings"></i>
      <span>Settings</span>
    </a>
  </div>
</div>
```

### Tooltip

```html
<div class="relative has-tooltip">
  <button>Hover me</button>
  <div class="tooltip tooltip-top">
    Tooltip content
  </div>
</div>
```

**Positions:**
- `tooltip-top`
- `tooltip-bottom`

---

## Utilities

### Glassmorphism

```html
<!-- Base glass effect -->
<div class="glass">Content</div>

<!-- Strong glass -->
<div class="glass-strong">Content</div>

<!-- Subtle glass -->
<div class="glass-subtle">Content</div>

<!-- Glass card (with padding and shadow) -->
<div class="glass-card">Content</div>
```

### Scrollbars

```html
<!-- Custom scrollbar -->
<div class="overflow-auto custom-scrollbar">
  Long content...
</div>

<!-- Thin scrollbar -->
<div class="overflow-auto scrollbar-thin">
  Long content...
</div>

<!-- Hide scrollbar -->
<div class="overflow-auto scrollbar-hide">
  Long content...
</div>
```

### Loading Skeleton

```html
<!-- Text skeleton -->
<div class="skeleton-text"></div>

<!-- Circle skeleton (for avatars) -->
<div class="skeleton-circle w-12 h-12"></div>

<!-- Card skeleton -->
<div class="skeleton-card"></div>

<!-- Custom skeleton -->
<div class="skeleton h-20 w-full rounded-lg"></div>
```

### Aspect Ratios

```html
<!-- 16:9 card -->
<div class="aspect-card">
  <img src="image.jpg" class="w-full h-full object-cover">
</div>

<!-- 1:1 square -->
<div class="aspect-square">
  <img src="image.jpg" class="w-full h-full object-cover">
</div>

<!-- 3:4 portrait -->
<div class="aspect-portrait">
  <img src="image.jpg" class="w-full h-full object-cover">
</div>
```

### Safe Area (Mobile)

```html
<div class="safe-top safe-bottom">
  Content with safe areas
</div>
```

---

## Animations

### Available Animations

| Class | Duration | Description |
|-------|----------|-------------|
| `animate-fade-in-up` | 0.6s | Fade in with upward movement |
| `animate-fade-in` | 0.8s | Simple fade in |
| `animate-slide-in-right` | 0.5s | Slide from left |
| `animate-float` | 3s (infinite) | Floating motion |
| `animate-shimmer` | 2s (infinite) | Shimmer effect |
| `animate-pulse-glow` | 2s (infinite) | Pulsing glow |
| `animate-ken-burns` | 30s (infinite) | Ken Burns zoom/pan |
| `animate-mesh-move` | 20s (infinite) | Mesh background movement |
| `animate-particle-float` | Variable (infinite) | Particle floating |
| `animate-scroll-bounce` | 2s (infinite) | Scroll indicator bounce |

### Stagger Delays

```html
<div class="animate-fade-in-up stagger-1">Item 1</div>
<div class="animate-fade-in-up stagger-2">Item 2</div>
<div class="animate-fade-in-up stagger-3">Item 3</div>
<div class="animate-fade-in-up stagger-4">Item 4</div>
<div class="animate-fade-in-up stagger-5">Item 5</div>
<div class="animate-fade-in-up stagger-6">Item 6</div>
```

### Transition Durations

```html
<!-- Fast (300ms) -->
<div class="transition-all duration-fast">Fast transition</div>

<!-- Medium (500ms) -->
<div class="transition-all duration-medium">Medium transition</div>

<!-- Slow (800ms) -->
<div class="transition-all duration-slow">Slow transition</div>
```

### Transition Timing Functions

```html
<!-- Bounce effect -->
<div class="transition-all ease-bounce-in">Bouncy</div>

<!-- Smooth easing -->
<div class="transition-all ease-smooth">Smooth</div>
```

---

## Dark Mode

### Implementation

Dark mode is controlled via the Alpine.js store and CSS classes.

**Toggle Dark Mode:**

```html
<button @click="$store.admin.toggleDarkMode()">
  <i x-show="!$store.admin.darkMode" data-lucide="moon"></i>
  <i x-show="$store.admin.darkMode" data-lucide="sun"></i>
</button>
```

### Dark Mode Classes

All components automatically support dark mode using Tailwind's `dark:` variant.

```html
<!-- Background -->
<div class="bg-white dark:bg-gray-900">Content</div>

<!-- Text -->
<p class="text-gray-900 dark:text-white">Text</p>

<!-- Border -->
<div class="border border-gray-200 dark:border-gray-700">Content</div>
```

### CSS Variables

Dark mode automatically switches CSS variables:

```css
:root {
  --color-bg-primary: 249 250 251;     /* Light mode */
}

.dark {
  --color-bg-primary: 0 0 0;           /* Dark mode */
}
```

---

## RTL Support

### Setting Direction

The store automatically sets the document direction based on language:

```javascript
// Arabic or Urdu = RTL
$store.admin.setLanguage('ar'); // Sets dir="rtl"

// Other languages = LTR
$store.admin.setLanguage('en'); // Sets dir="ltr"
```

### RTL Classes

```html
<!-- Manual RTL -->
<div class="rtl">RTL content</div>

<!-- Manual LTR -->
<div class="ltr">LTR content</div>
```

### RTL-Aware Spacing

Use logical properties for RTL support:

```html
<!-- Use ms-* (margin-start) instead of ml-* (margin-left) -->
<div class="ms-4">Margin start</div>

<!-- Use me-* (margin-end) instead of mr-* (margin-right) -->
<div class="me-4">Margin end</div>

<!-- Use ps-* (padding-start) instead of pl-* (padding-left) -->
<div class="ps-4">Padding start</div>

<!-- Use pe-* (padding-end) instead of pr-* (padding-right) -->
<div class="pe-4">Padding end</div>
```

### RTL-Specific Styles

Some components automatically adjust for RTL:

- **Select dropdown arrow** flips position
- **Switch toggle** animation reverses
- **Table text alignment** changes to right
- **Sidebar** position flips to right side

---

## Alpine.js Store

### Accessing the Store

```javascript
// In Alpine components
$store.admin.darkMode
$store.admin.sidebarCollapsed
$store.admin.language
```

### Available Methods

#### Dark Mode

```javascript
$store.admin.toggleDarkMode()
$store.admin.setDarkMode(true)
```

#### Sidebar

```javascript
$store.admin.toggleSidebar()
$store.admin.closeSidebar()
$store.admin.openSidebar()
```

#### Language

```javascript
$store.admin.setLanguage('ar')
$store.admin.getDirection() // Returns 'rtl' or 'ltr'
```

#### Notifications

```javascript
$store.admin.addNotification({
  title: 'Success',
  message: 'Action completed',
  type: 'success',
  autoDismiss: true
})

$store.admin.markAsRead(notificationId)
$store.admin.markAllAsRead()
$store.admin.clearAllNotifications()
```

#### Search

```javascript
$store.admin.openSearch()
$store.admin.closeSearch()
$store.admin.performSearch('query')
```

#### Toasts

```javascript
$store.admin.success('Operation successful')
$store.admin.error('An error occurred')
$store.admin.warning('Warning message')
$store.admin.info('Information message')
```

#### Modals

```javascript
$store.admin.openModal('modalId')
$store.admin.closeModal('modalId')
$store.admin.isModalOpen('modalId')
```

#### Confirmation Dialog

```javascript
const confirmed = await $store.admin.confirm({
  title: 'Delete Item',
  message: 'Are you sure you want to delete this item?',
  confirmText: 'Delete',
  cancelText: 'Cancel',
  type: 'danger'
});

if (confirmed) {
  // Perform deletion
}
```

#### Table Selection

```javascript
$store.admin.toggleItem(itemId)
$store.admin.isSelected(itemId)
$store.admin.clearSelection()
```

---

## Usage Examples

### Complete Card Component

```html
<div class="premium-card group card-shimmer">
  <!-- Image with overlay -->
  <div class="relative aspect-video overflow-hidden">
    <img src="thumbnail.jpg"
         alt="Video thumbnail"
         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">

    <!-- Hover overlay -->
    <div class="card-overlay">
      <button class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
        <i data-lucide="play" class="text-primary-500"></i>
      </button>
    </div>

    <!-- Badge -->
    <span class="badge badge-primary absolute top-4 left-4">New</span>
  </div>

  <!-- Content -->
  <div class="p-5">
    <h3 class="font-bold text-lg mb-2 group-hover:text-[#64CCC5] transition-colors"
        :class="$store.admin.darkMode ? 'text-white' : 'text-gray-900'">
      Video Title
    </h3>
    <p class="text-sm mb-4"
       :class="$store.admin.darkMode ? 'text-gray-400' : 'text-gray-600'">
      Video description with truncate
    </p>

    <div class="flex items-center justify-between">
      <span class="text-xs"
            :class="$store.admin.darkMode ? 'text-gray-500' : 'text-gray-500'">
        2 hours ago
      </span>
      <button class="btn-icon">
        <i data-lucide="more-vertical"></i>
      </button>
    </div>
  </div>
</div>
```

### Form with Validation

```html
<form x-data="{ name: '', email: '', errors: {} }" @submit.prevent="submitForm">
  <!-- Name Input -->
  <div class="mb-6">
    <label class="block mb-2 font-semibold"
           :class="$store.admin.darkMode ? 'text-white' : 'text-gray-900'">
      Name
    </label>
    <input type="text"
           x-model="name"
           class="input-custom"
           :class="{ 'border-red-500': errors.name }"
           placeholder="Enter your name">
    <p x-show="errors.name"
       class="text-red-500 text-sm mt-1"
       x-text="errors.name"></p>
  </div>

  <!-- Email Input -->
  <div class="mb-6">
    <label class="block mb-2 font-semibold"
           :class="$store.admin.darkMode ? 'text-white' : 'text-gray-900'">
      Email
    </label>
    <input type="email"
           x-model="email"
           class="input-custom"
           :class="{ 'border-red-500': errors.email }"
           placeholder="Enter your email">
    <p x-show="errors.email"
       class="text-red-500 text-sm mt-1"
       x-text="errors.email"></p>
  </div>

  <!-- Submit Button -->
  <button type="submit" class="btn-primary w-full">
    Submit Form
  </button>
</form>
```

### Data Table with Selection

```html
<div x-data="{ items: [...], selectAll: false }">
  <table class="table-premium">
    <thead>
      <tr>
        <th>
          <input type="checkbox"
                 class="checkbox-custom"
                 x-model="selectAll"
                 @change="$store.admin.toggleSelectAll(items)">
        </th>
        <th>Name</th>
        <th>Email</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <template x-for="item in items" :key="item.id">
        <tr>
          <td>
            <input type="checkbox"
                   class="checkbox-custom"
                   :checked="$store.admin.isSelected(item.id)"
                   @change="$store.admin.toggleItem(item.id)">
          </td>
          <td x-text="item.name"></td>
          <td x-text="item.email"></td>
          <td>
            <button class="btn-icon">
              <i data-lucide="edit"></i>
            </button>
            <button class="btn-icon"
                    @click="deleteItem(item.id)">
              <i data-lucide="trash-2"></i>
            </button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
</div>
```

---

## Quick Reference

### Most Used Classes

```html
<!-- Gradient Button -->
<button class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-xl">

<!-- Glass Card -->
<div class="glass-card">

<!-- Premium Card -->
<div class="premium-card group">

<!-- Gradient Text -->
<h1 class="text-gradient text-4xl font-bold">

<!-- Dark Mode Aware -->
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">

<!-- Custom Input -->
<input class="input-custom">

<!-- Badge -->
<span class="badge badge-primary">

<!-- Icon Button -->
<button class="btn-icon">
```

### Color Quick Reference

```
Primary:   #0056b3  (Blue)
Secondary: #218838  (Green)
Accent:    #64CCC5  (Teal)
```

### Breakpoints

```
sm:  640px
md:  768px
lg:  1024px
xl:  1280px
2xl: 1536px
```

---

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Mobile Safari: iOS 12+
- Chrome Android: Latest

## Performance Tips

1. Use `will-change` sparingly for animations
2. Prefer CSS transitions over JavaScript
3. Use `loading="lazy"` for images
4. Debounce search and resize events
5. Use skeleton loading for better perceived performance

---

## Getting Started

### Include in Blade Template

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
      x-data
      :class="{ 'dark': $store.admin.darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>

    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])
</head>
<body class="antialiased">
    <!-- Your content -->
</body>
</html>
```

### Initialize Icons

```javascript
// Icons auto-initialize on page load
// Manually reinitialize after dynamic content:
lucide.createIcons();
```

---

For more examples and documentation, see the individual component files in `resources/views/components/admin/`.
