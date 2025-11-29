# Admin Design Tokens - Quick Reference

## Colors

### Brand Colors (HEX)
```
Primary Blue:    #0056b3
Secondary Green: #218838
Accent Teal:     #64CCC5
```

### Brand Colors (RGB)
```
Primary:   rgb(0, 86, 179)
Secondary: rgb(33, 136, 56)
Accent:    rgb(100, 204, 197)
```

### Tailwind Utilities
```html
<!-- Backgrounds -->
bg-primary-500      #0056b3
bg-secondary-500    #218838
bg-accent-500       #64CCC5

<!-- Text -->
text-primary-500
text-secondary-500
text-accent-500

<!-- Borders -->
border-primary-500
border-secondary-500
border-accent-500
```

### Gradients
```html
<!-- Primary gradient (most used) -->
<div class="bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>

<!-- Teal accent gradient -->
<div class="bg-gradient-to-r from-[#64CCC5] to-[#0056b3]"></div>

<!-- Reverse gradient -->
<div class="bg-gradient-to-r from-[#218838] to-[#0056b3]"></div>

<!-- Pre-defined gradients -->
<div class="bg-gradient-primary"></div>
<div class="bg-gradient-hero"></div>
<div class="bg-mesh-bg"></div>
```

---

## Typography

### Font Family
```css
font-sans    'Cairo', system-ui
font-arabic  'Amiri', serif
```

### Font Weights
```
font-light     300
font-normal    400
font-semibold  600
font-bold      700
font-black     900
```

### Gradient Text
```html
<h1 class="text-gradient">Gradient Text</h1>
<h2 class="text-gradient-accent">Accent Gradient</h2>
<h3 class="text-gradient-reverse">Reverse Gradient</h3>
```

---

## Spacing

### Custom Values
```
space-18   4.5rem   (72px)
space-88   22rem    (352px)
space-100  25rem    (400px)
space-112  28rem    (448px)
space-128  32rem    (512px)
```

### Layout Constants
```css
--sidebar-width: 280px
--sidebar-collapsed-width: 80px
--header-height: 64px
```

---

## Shadows

```html
shadow-sm        Subtle shadow
shadow-md        Medium shadow
shadow-lg        Large shadow
shadow-xl        Extra large shadow
shadow-soft      Soft blue shadow (0 20px 40px rgba(0, 86, 179, 0.2))
shadow-glow      Glowing effect (0 0 20px rgba(0, 86, 179, 0.3))
shadow-glow-lg   Large glow (0 0 40px rgba(0, 86, 179, 0.6))
```

---

## Animations

### Durations
```html
duration-fast    300ms
duration-medium  500ms
duration-slow    800ms
```

### Timing Functions
```html
ease-bounce-in   cubic-bezier(0.68, -0.55, 0.265, 1.55)
ease-smooth      cubic-bezier(0.4, 0, 0.2, 1)
```

### Keyframe Animations
```html
animate-fade-in-up       Fade in with upward movement (0.6s)
animate-fade-in          Simple fade (0.8s)
animate-slide-in-right   Slide from left (0.5s)
animate-float            Floating motion (3s infinite)
animate-shimmer          Shimmer effect (2s infinite)
animate-pulse-glow       Pulsing glow (2s infinite)
animate-ken-burns        Zoom/pan effect (30s infinite)
animate-mesh-move        Mesh movement (20s infinite)
animate-particle-float   Particle floating (variable infinite)
animate-scroll-bounce    Scroll indicator (2s infinite)
```

### Stagger Delays
```html
stagger-1   0.1s delay
stagger-2   0.2s delay
stagger-3   0.3s delay
stagger-4   0.4s delay
stagger-5   0.5s delay
stagger-6   0.6s delay
```

---

## Components

### Buttons
```html
btn-primary      Gradient button
btn-secondary    Gray button
btn-ghost        Outlined button
btn-icon         Icon-only button
btn-magnetic     Magnetic hover effect
```

### Badges
```html
badge badge-primary    Gradient badge
badge badge-success    Green badge
badge badge-warning    Yellow badge
badge badge-danger     Red badge
badge badge-info       Blue badge
badge badge-pulse      Pulsing badge
```

### Cards
```html
premium-card      Premium hover effects
glass-card        Glassmorphism card
card-overlay      Overlay on hover
card-shimmer      Shimmer effect
card-glow         Glow effect
```

### Forms
```html
input-custom      Custom input
select-custom     Custom select
checkbox-custom   Custom checkbox
radio-custom      Custom radio
switch-toggle     Toggle switch
```

### Tables
```html
table-premium        Premium table
table-striped        Striped rows
```

### Menus
```html
dropdown-menu        Dropdown container
dropdown-item        Menu item
dropdown-divider     Divider line
```

---

## Glassmorphism

```html
glass              Base glass effect
glass-strong       Strong glass
glass-subtle       Subtle glass
glass-card         Glass with padding/shadow
```

**Effect:**
- Light mode: `bg-white/80` with blur
- Dark mode: `bg-white/5` with blur

---

## Scrollbars

```html
custom-scrollbar   Themed scrollbar
scrollbar-thin     Thin scrollbar
scrollbar-hide     Hidden scrollbar
```

---

## Skeleton Loading

```html
skeleton           Base skeleton
skeleton-text      Text line skeleton
skeleton-circle    Circle skeleton (avatars)
skeleton-card      Card skeleton
```

---

## Utilities

### Text Truncation
```html
truncate-2    Truncate to 2 lines
truncate-3    Truncate to 3 lines
```

### Aspect Ratios
```html
aspect-card       16:9
aspect-square     1:1
aspect-portrait   3:4
```

### Safe Areas (Mobile)
```html
safe-top       Safe area top padding
safe-bottom    Safe area bottom padding
safe-left      Safe area left padding
safe-right     Safe area right padding
```

### Direction
```html
rtl   Right-to-left
ltr   Left-to-right
```

---

## Alpine.js Store

### Access
```javascript
$store.admin.property
```

### Properties
```javascript
darkMode              Boolean
sidebarCollapsed      Boolean
sidebarOpen           Boolean (mobile)
language              String (en, ar, etc.)
isRtl                 Boolean
notifications         Array
unreadNotificationsCount  Number
searchOpen            Boolean
searchQuery           String
userMenuOpen          Boolean
```

### Methods
```javascript
// Dark mode
toggleDarkMode()
setDarkMode(value)

// Sidebar
toggleSidebar()
closeSidebar()
openSidebar()

// Language
setLanguage(lang)
getDirection()

// Notifications
addNotification(notification)
removeNotification(id)
markAsRead(id)
markAllAsRead()
clearAllNotifications()

// Search
openSearch()
closeSearch()
performSearch(query)

// Toasts
success(message)
error(message)
warning(message)
info(message)

// Modals
openModal(id)
closeModal(id)
isModalOpen(id)

// Confirmation
confirm(options)

// Selection
toggleItem(id)
isSelected(id)
clearSelection()
```

---

## Dark Mode Classes

### Pattern
```html
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
  Dark mode aware content
</div>
```

### Common Combinations
```html
<!-- Background -->
bg-white dark:bg-gray-900
bg-gray-50 dark:bg-black
bg-gray-100 dark:bg-gray-800

<!-- Text -->
text-gray-900 dark:text-white
text-gray-600 dark:text-gray-300
text-gray-400 dark:text-gray-500

<!-- Border -->
border-gray-200 dark:border-gray-700
border-gray-300 dark:border-gray-600
```

---

## RTL Classes

### Logical Properties (RTL-aware)
```html
<!-- Margin -->
ms-4    margin-start (left in LTR, right in RTL)
me-4    margin-end (right in LTR, left in RTL)

<!-- Padding -->
ps-4    padding-start
pe-4    padding-end

<!-- Text Alignment -->
text-start    text-left in LTR, text-right in RTL
text-end      text-right in LTR, text-left in RTL
```

---

## Z-Index Scale

```
z-60    60
z-70    70
z-80    80
z-90    90
z-100   100
```

**Default Tailwind:**
```
z-0     0
z-10    10
z-20    20
z-30    30
z-40    40
z-50    50
```

---

## Backdrop Blur

```html
backdrop-blur-xs    2px
backdrop-blur-sm    4px
backdrop-blur       8px
backdrop-blur-md    12px
backdrop-blur-lg    16px
backdrop-blur-xl    24px
backdrop-blur-2xl   40px
backdrop-blur-3xl   64px
```

---

## Common Patterns

### Primary Button
```html
<button class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold px-6 py-3 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
  Click Me
</button>
```

### Card with Hover
```html
<div class="premium-card group bg-white dark:bg-gray-900 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
  <img src="image.jpg" class="w-full aspect-video object-cover group-hover:scale-110 transition-transform duration-500">
  <div class="p-5">
    <h3 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-[#64CCC5] transition-colors">
      Title
    </h3>
  </div>
</div>
```

### Glass Card
```html
<div class="glass backdrop-blur-lg rounded-2xl p-6 shadow-lg">
  <h3 class="text-gradient text-xl font-bold mb-4">Title</h3>
  <p class="text-gray-700 dark:text-gray-300">Content</p>
</div>
```

### Input Field
```html
<input type="text"
       class="input-custom"
       placeholder="Enter text..."
       :class="$store.admin.darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'">
```

### Badge
```html
<span class="badge badge-primary animate-pulse-glow">New</span>
```

### Skeleton Loading
```html
<div class="space-y-4">
  <div class="skeleton h-12 w-48"></div>
  <div class="skeleton-text"></div>
  <div class="skeleton-card"></div>
</div>
```

---

## File Paths

```
CSS:        resources/css/admin.css
JS Store:   resources/js/admin/store.js
JS App:     resources/js/admin/app.js
Config:     tailwind.config.js
Vite:       vite.config.js
```

---

## Build Commands

```bash
# Development
npm run dev

# Production build
npm run build

# Watch mode
npm run watch
```

---

## Browser DevTools

### Check Dark Mode State
```javascript
// In console
Alpine.store('admin').darkMode
```

### Toggle Dark Mode
```javascript
Alpine.store('admin').toggleDarkMode()
```

### Check Language
```javascript
Alpine.store('admin').language
Alpine.store('admin').isRtl
```

### Show Toast
```javascript
Alpine.store('admin').success('Test message')
```

---

## Accessibility

### Focus States
All interactive elements have focus states via Tailwind's `focus:` variant.

### Keyboard Navigation
- `Escape`: Close modals, search, dropdowns
- `Cmd/Ctrl + K`: Open search

### Screen Readers
Use semantic HTML and ARIA labels:

```html
<button aria-label="Close modal">
  <i data-lucide="x"></i>
</button>
```

---

## Performance

### Image Optimization
```html
<img src="image.jpg"
     loading="lazy"
     alt="Description"
     class="w-full h-full object-cover">
```

### Debounce Events
```javascript
let timeout;
input.addEventListener('input', () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    // Perform search
  }, 300);
});
```

### GPU Acceleration
```html
<div class="transform-gpu">
  <!-- Animated content -->
</div>
```

---

## Tips

1. Always use logical properties (ms-, me-, ps-, pe-) for RTL support
2. Always include dark mode classes for all colored elements
3. Use semantic color names (primary, secondary, accent) over specific colors
4. Prefer CSS transitions over JavaScript animations
5. Test in both light/dark mode and LTR/RTL
6. Use skeleton loading for better perceived performance
7. Keep animations under 500ms for snappy feel
8. Use backdrop-blur sparingly (performance impact)
9. Always include alt text for images
10. Test keyboard navigation

---

## Common Issues

### Icons not showing
```javascript
// Reinitialize after Alpine updates
lucide.createIcons();
```

### Dark mode not persisting
Check localStorage:
```javascript
localStorage.getItem('adminDarkMode') // Should be 'true' or 'false'
```

### RTL not working
Check document direction:
```javascript
document.documentElement.getAttribute('dir') // Should be 'rtl' or 'ltr'
```

### Store not accessible
Ensure Alpine is initialized:
```javascript
document.addEventListener('alpine:init', () => {
  // Store is ready
});
```

---

## Support

For more detailed documentation, see `ADMIN-DESIGN-SYSTEM.md`.
