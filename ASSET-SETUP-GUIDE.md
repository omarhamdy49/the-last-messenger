# Asset Pipeline & Multi-Language Setup Guide

## Overview

This guide covers the complete Tailwind CSS configuration, asset pipeline, and Laravel multi-language system for The Last Messenger admin panel. The setup migrates from CDN-based assets to a compiled production-ready system.

## Features Implemented

### 1. Tailwind CSS Configuration
- ✅ Custom blue-green gradient design system
- ✅ Dark mode support via class strategy
- ✅ Custom animations (fade-in, slide, float, shimmer, Ken Burns, etc.)
- ✅ Premium card effects and glassmorphism
- ✅ RTL support for Arabic/Urdu
- ✅ Custom utility classes for admin panel
- ✅ Responsive breakpoints

### 2. Alpine.js Integration
- ✅ Smart carousel component (handles 1-2 items without repetition)
- ✅ Video player component with controls
- ✅ Enhanced dashboard component (dark mode, sidebar, notifications)
- ✅ Global helper functions

### 3. Multi-Language System
- ✅ 5 languages supported: Arabic (default), English, French, Turkish, Urdu
- ✅ Complete admin translations (`resources/lang/{locale}/admin.php`)
- ✅ Site translations (`resources/lang/{locale}/site.php`)
- ✅ Language switching middleware
- ✅ Session-based locale persistence

### 4. Asset Pipeline
- ✅ Vite bundler configuration
- ✅ Production build optimization
- ✅ Hot module replacement (HMR) for development
- ✅ Lucide icons integration

## Installation Steps

### Step 1: Install Node Dependencies

```bash
npm install
```

This will install:
- **Tailwind CSS 3.4** - Utility-first CSS framework
- **Alpine.js 3.14** - Lightweight JavaScript framework
- **Lucide Icons** - SVG icon library
- **Chart.js** - Charts for dashboard
- **Vite** - Fast build tool

### Step 2: Build Assets

For development (with hot reload):
```bash
npm run dev
```

For production (optimized):
```bash
npm run build
```

Watch mode (rebuild on changes):
```bash
npm run watch
```

### Step 3: Laravel Configuration

The middleware is already registered in `bootstrap/app.php`. No additional steps needed.

## File Structure

```
the-last-messenger-be/
├── resources/
│   ├── css/
│   │   └── app.css                      # Custom Tailwind styles
│   ├── js/
│   │   ├── app.js                       # Main JavaScript entry
│   │   ├── bootstrap.js                 # Axios setup
│   │   └── components/
│   │       ├── smart-carousel.js        # Carousel component
│   │       ├── video-player.js          # Video player component
│   │       └── enhanced-dashboard.js    # Dashboard component
│   ├── lang/
│   │   ├── ar/
│   │   │   ├── admin.php               # Arabic admin translations
│   │   │   └── site.php                # Arabic site translations
│   │   ├── en/
│   │   │   ├── admin.php               # English admin translations
│   │   │   └── site.php                # English site translations
│   │   ├── fr/
│   │   │   ├── admin.php               # French admin translations
│   │   │   └── site.php                # French site translations
│   │   ├── tr/
│   │   │   ├── admin.php               # Turkish admin translations
│   │   │   └── site.php                # Turkish site translations
│   │   └── ur/
│   │       ├── admin.php               # Urdu admin translations
│   │       └── site.php                # Urdu site translations
│   └── views/                           # Blade templates
├── app/
│   └── Http/
│       ├── Middleware/
│       │   └── SetLocale.php           # Language middleware
│       └── Controllers/
│           └── LanguageController.php   # Language switching
├── tailwind.config.js                   # Tailwind configuration
├── vite.config.js                       # Vite configuration
├── package.json                         # Node dependencies
└── routes/
    └── web.php                          # Language route registered
```

## Design System

### Color Palette

**Primary Blue-Green Gradient:**
```css
from-[#0056b3] to-[#218838]
```

**Usage:**
- Primary actions: `bg-gradient-to-r from-[#0056b3] to-[#218838]`
- Hover states: `hover:text-[#64CCC5]` (teal accent)
- Shadows: `shadow-soft` (blue glow)

**Color Classes:**
- `primary-{50-900}` - Blue shades (#0056b3)
- `secondary-{50-900}` - Green shades (#218838)
- `accent-{50-900}` - Teal shades (#64CCC5)

### Custom Utility Classes

**Cards:**
```html
<div class="module-card">
  <!-- Auto-applies hover lift effect -->
</div>

<div class="premium-card">
  <!-- Netflix-style card with scale on hover -->
</div>

<div class="admin-card">
  <!-- Admin panel card style -->
</div>
```

**Buttons:**
```html
<button class="btn btn-gradient">Action</button>
<button class="btn btn-outline">Secondary</button>
<button class="btn btn-danger">Delete</button>
```

**Badges:**
```html
<span class="badge badge-success">Published</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-danger">Rejected</span>
<span class="badge badge-gradient">Featured</span>
```

**Forms:**
```html
<label class="form-label">Title</label>
<input type="text" class="form-input">
```

**Tables:**
```html
<table class="admin-table">
  <thead>
    <tr>
      <th>Column</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Data</td>
    </tr>
  </tbody>
</table>
```

### Animations

**Available Keyframes:**
- `animate-fade-in-up` - Fade in with upward motion
- `animate-fade-in` - Simple fade in
- `animate-slide-in-right` - Slide from left
- `animate-float` - Floating animation
- `animate-shimmer` - Skeleton loading effect
- `animate-pulse-glow` - Glowing pulse
- `animate-ken-burns` - Subtle zoom and pan
- `animate-mesh-move` - Animated mesh background

**Stagger Classes:**
```html
<div class="stagger-1 animate-fade-in-up">Item 1</div>
<div class="stagger-2 animate-fade-in-up">Item 2</div>
<div class="stagger-3 animate-fade-in-up">Item 3</div>
<div class="stagger-4 animate-fade-in-up">Item 4</div>
```

## Alpine.js Components

### 1. Smart Carousel

Handles edge cases with 1-2 items without repetition.

**Usage:**
```html
<div class="relative" x-data="smartCarousel()">
  <div class="flex overflow-x-auto custom-scrollbar gap-4" x-ref="scrollContainer">
    <!-- Your cards here -->
  </div>

  <!-- Navigation buttons -->
  <button x-show="showNavigation && canScrollLeft" @click="scrollLeft">
    <i data-lucide="chevron-left"></i>
  </button>
  <button x-show="showNavigation && canScrollRight" @click="scrollRight">
    <i data-lucide="chevron-right"></i>
  </button>
</div>
```

### 2. Video Player

Full-featured video player with custom controls.

**Usage:**
```html
<div x-data="videoPlayer()" x-ref="container">
  <video x-ref="video" src="video.mp4"></video>

  <button @click="togglePlay()">
    <i :data-lucide="playing ? 'pause' : 'play'"></i>
  </button>

  <div @click="seek($event)">
    <div :style="`width: ${(currentTime / duration) * 100}%`"></div>
  </div>
</div>
```

### 3. Enhanced Dashboard

**Usage:**
```html
<div x-data="enhancedDashboard()">
  <!-- Dark mode toggle -->
  <button @click="toggleDarkMode()">
    <i x-show="darkMode" data-lucide="sun"></i>
    <i x-show="!darkMode" data-lucide="moon"></i>
  </button>

  <!-- Sidebar toggle -->
  <button @click="toggleSidebar()">
    <i data-lucide="menu"></i>
  </button>

  <!-- Search -->
  <input x-model="searchQuery" @keydown.enter="search()">
</div>
```

## Language System

### Using Translations in Blade

```blade
<!-- Simple translation -->
<h1>{{ __('admin.dashboard') }}</h1>

<!-- With variables -->
<p>{{ __('admin.showing', ['count' => 10]) }}</p>

<!-- Pluralization -->
<span>{{ trans_choice('admin.articles_count', $count) }}</span>

<!-- In attributes -->
<input placeholder="{{ __('admin.search') }}">
```

### Language Switching

**Form:**
```html
<form action="{{ route('language.switch') }}" method="POST">
  @csrf
  <select name="locale" onchange="this.form.submit()">
    <option value="ar" {{ app()->getLocale() == 'ar' ? 'selected' : '' }}>العربية</option>
    <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>English</option>
    <option value="fr" {{ app()->getLocale() == 'fr' ? 'selected' : '' }}>Français</option>
    <option value="tr" {{ app()->getLocale() == 'tr' ? 'selected' : '' }}>Türkçe</option>
    <option value="ur" {{ app()->getLocale() == 'ur' ? 'selected' : '' }}>اردو</option>
  </select>
</form>
```

**Get Current Locale:**
```php
$locale = app()->getLocale(); // 'ar', 'en', 'fr', 'tr', 'ur'
```

### RTL Support

The middleware automatically handles RTL for Arabic and Urdu. In your layout:

```blade
<html lang="{{ app()->getLocale() }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
```

## Using Vite in Blade Templates

### Main Layout

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @yield('content')
</body>
</html>
```

### Initialize Lucide Icons

Icons are automatically initialized on page load. For dynamic content:

```javascript
// After adding dynamic content
window.refreshIcons();
```

## Helper Functions

Global helpers available in Alpine components:

```javascript
// Format date
window.alpineHelpers.formatDate('2025-01-15', 'ar');

// Format number
window.alpineHelpers.formatNumber(1234567, 'ar');

// Truncate text
window.alpineHelpers.truncate('Long text here...', 100);

// Debounce function
const debouncedSearch = window.alpineHelpers.debounce(() => {
  // Search logic
}, 300);
```

## Dark Mode Implementation

### Blade Template

```blade
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
     x-init="darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
     :class="darkMode ? 'dark' : ''">

  <button @click="darkMode = !darkMode;
                  localStorage.setItem('darkMode', darkMode);
                  darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')">
    Toggle Dark Mode
  </button>

  <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content here
  </div>
</div>
```

## Production Deployment

### Build for Production

```bash
npm run build
```

This will:
1. Minify CSS and JavaScript
2. Tree-shake unused Tailwind classes
3. Optimize assets
4. Generate manifest for Laravel

### .gitignore

Make sure these are in `.gitignore`:
```
/node_modules
/public/build
/public/hot
```

### Environment

In production `.env`:
```env
APP_ENV=production
APP_DEBUG=false
VITE_APP_URL=https://yourdomain.com
```

## Troubleshooting

### Issue: Assets not loading

**Solution:**
```bash
npm run build
php artisan optimize:clear
```

### Issue: Dark mode not working

**Solution:** Make sure `darkMode: 'class'` is in `tailwind.config.js` and the `dark` class is applied to `<html>` element.

### Issue: Translations not showing

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: Icons not showing

**Solution:**
```javascript
// Call after dynamic content loads
window.refreshIcons();
```

### Issue: Build errors

**Solution:**
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

## Additional Resources

- **Tailwind CSS Docs:** https://tailwindcss.com/docs
- **Alpine.js Docs:** https://alpinejs.dev
- **Lucide Icons:** https://lucide.dev
- **Vite Docs:** https://vitejs.dev
- **Laravel Vite:** https://laravel.com/docs/vite

## Next Steps

1. ✅ Run `npm install` to install dependencies
2. ✅ Run `npm run dev` to start development server
3. ✅ Create your first Blade template with `@vite` directive
4. ✅ Use translations with `{{ __('admin.key') }}`
5. ✅ Apply custom utility classes from the design system
6. ✅ Test language switching functionality
7. ✅ Test dark mode toggle
8. ✅ Build for production with `npm run build`

## Support

If you encounter any issues:
1. Check this guide first
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check browser console for JavaScript errors
4. Verify Node version: `node -v` (should be 18+)
5. Clear all caches: `php artisan optimize:clear`
