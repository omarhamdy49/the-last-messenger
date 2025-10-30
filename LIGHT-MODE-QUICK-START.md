# Light Mode - Quick Start Guide

## TL;DR

Light mode uses three glass morphism classes throughout the admin panel:

```blade
<!-- Premium modals/dropdowns -->
:class="darkMode ? 'glass-premium' : 'glass-light'"

<!-- Standard cards/sections -->
:class="darkMode ? 'bg-gray-800/50' : 'glass-light-soft'"

<!-- High-emphasis inputs/search -->
:class="darkMode ? 'bg-white/10' : 'glass-light-strong'"
```

---

## Copy-Paste Templates

### Basic Card
```blade
<div class="rounded-2xl p-6 transition-colors"
     :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border-0'">
    Content here
</div>
```

### Dropdown Menu
```blade
<div class="rounded-2xl shadow-2xl overflow-hidden transition-colors"
     :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'glass-light'">
    Menu items here
</div>
```

### Interactive Button
```blade
<button class="px-6 py-3 rounded-xl transition-colors duration-200"
        :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-blue-50/50'">
    Click me
</button>
```

### Search/Input Field
```blade
<input type="text"
       class="rounded-xl transition-all focus:ring-2 focus:ring-blue-500/50"
       :class="darkMode ? 'bg-white/10 border border-white/20' : 'glass-light-strong border-0'">
```

### Hover Card (Premium)
```blade
<div class="premium-card rounded-2xl p-6 transition-all hover:scale-105"
     :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'glass-light-soft border-0'">
</div>
```

---

## Color Quick Reference

### Light Mode Only
```
Text Primary:      text-gray-900
Text Secondary:    text-gray-600 or text-gray-700
Text Tertiary:     text-gray-500
Background:        linear-gradient(white to blue)
Hover Background:  hover:bg-blue-50/50
Focus Ring:        ring-blue-500/50
Border:            border-blue-100/50
Shadow:            shadow-md or shadow-lg
```

### Always Brand
```
Primary CTA:       from-[#0056b3] to-[#218838]
Accent:            #64CCC5
Hover Text:        hover:text-[#64CCC5]
Success:           text-emerald-500
Warning:           text-yellow-500
Error:             text-red-500
```

---

## Common Patterns

### 1. Header Component
```blade
class="glass-light-soft shadow-md"
```

### 2. Sidebar Component
```blade
class="glass-light-soft shadow-lg border-r border-blue-100/50"
```

### 3. Dropdown Component
```blade
class="glass-light shadow-2xl overflow-hidden"
```

### 4. Dashboard Cards
```blade
class="glass-light-soft rounded-2xl p-6"
```

### 5. Premium Sections
```blade
class="glass-light-strong rounded-3xl p-8"
```

---

## Light/Dark Toggle Pattern

### Standard Conditional
```blade
:class="darkMode ? 'dark-class' : 'light-class'"
```

### Example
```blade
<!-- Background -->
:class="darkMode ? 'bg-gray-800' : 'bg-white'"

<!-- Text -->
:class="darkMode ? 'text-white' : 'text-gray-900'"

<!-- Border -->
:class="darkMode ? 'border-gray-700' : 'border-gray-200'"

<!-- Hover -->
:class="darkMode ? 'hover:bg-white/10' : 'hover:bg-blue-50/50'"

<!-- Glass -->
:class="darkMode ? 'glass-dark' : 'glass-light-soft'"
```

---

## CSS Classes Reference

### Glass Effects

| Class | Light Mode | Usage |
|-------|-----------|-------|
| `.glass` | White 10% | (Legacy, use others) |
| `.glass-light` | White 60% | Modals, Dropdowns |
| `.glass-light-soft` | White 80% | Cards, Sidebar, Header |
| `.glass-light-strong` | White 95% | Search, Inputs |
| `.glass-dark` | Black 30% | Dark mode overlay |
| `.glass-premium` | Black 3% | Dark mode cards |

### Gradients

| Class | Purpose |
|-------|---------|
| `.gradient-primary` | Full opacity gradient `#0056b3 → #218838` |
| `.gradient-text` | Text gradient (same colors) |
| `.gradient-light` | Light transparent gradient |

### Cards

| Class | Effect |
|-------|--------|
| `.card-light` | Base light card styling |
| `.premium-card` | Hover scale + shimmer effect |
| `.card-hover` | Smooth hover transform |

---

## Common Modifications

### Need Different Hover Color?
```blade
<!-- Green for success context -->
:class="darkMode ? 'hover:bg-green-700/20' : 'hover:bg-green-50/50'"

<!-- Red for danger context -->
:class="darkMode ? 'hover:bg-red-700/20' : 'hover:bg-red-50/50'"
```

### Need Stronger Shadow?
```blade
<!-- Add custom shadow -->
:class="darkMode ? 'shadow-xl' : 'shadow-lg'"
```

### Need Different Border?
```blade
<!-- Accent border -->
:class="darkMode ? 'border-emerald-700/50' : 'border-emerald-100/50'"
```

### Need More Opacity?
```blade
<!-- If glass-light-soft is too transparent, use glass-light -->
:class="darkMode ? '...' : 'glass-light'"
```

---

## Debugging Tips

### If glass morphism doesn't show up:
1. Check `backdrop-filter: blur()` is supported (it is in all modern browsers)
2. Verify the element isn't inside `pointer-events: none` container
3. Check z-index isn't causing layering issues

### If text is hard to read:
1. Use `text-gray-900` for primary text in light mode
2. Use `text-gray-600` for secondary text
3. Increase text weight if needed

### If hover effect doesn't work:
1. Ensure `transition-colors` or `transition-all` is applied
2. Check duration isn't `duration-0`
3. Verify Alpine.js `:class` binding is correct

### If scrollbar doesn't show:
1. Check element has `overflow-y-auto` or `custom-scrollbar` class
2. Verify not on Windows with overlay scrollbars
3. Use Firefox to test - always shows scrollbars

---

## Performance Checklist

- ✓ Use `transition-colors` instead of `transition-all` when possible
- ✓ Keep blur values between 10px-20px
- ✓ Avoid heavy filters on large elements
- ✓ Use GPU-accelerated properties (transform, opacity)
- ✓ Limit shadow depth (avoid `shadow-2xl` on light mode)
- ✓ Use `backdrop-filter: blur()` not `filter: blur()`

---

## Testing on Different Screens

### Mobile Light Mode
- Check text isn't too small
- Verify touch targets are >= 44px
- Ensure glass effect doesn't cause readability issues

### Tablet Light Mode
- Verify layout is centered
- Check card sizing
- Ensure hover states work

### Desktop Light Mode
- Test full glass effect
- Verify shadows look natural
- Check scrollbar styling

### High Contrast Mode
- Test with Windows High Contrast enabled
- Verify focus indicators visible
- Check text contrast ratio

---

## Quick Wins for Existing Components

### Make a card premium:
```blade
<!-- Before -->
:class="darkMode ? '...' : 'bg-white shadow-xl'"

<!-- After -->
:class="darkMode ? '...' : 'glass-light-soft'"
```

### Improve a dropdown:
```blade
<!-- Before -->
:class="darkMode ? '...' : 'bg-white border border-gray-200'"

<!-- After -->
:class="darkMode ? '...' : 'glass-light'"
```

### Better hover feedback:
```blade
<!-- Before -->
:class="darkMode ? '...' : 'hover:bg-gray-100'"

<!-- After -->
:class="darkMode ? '...' : 'hover:bg-blue-50/50'"
```

---

## When to Use Each Glass Class

```
glass-light
    ├─ Modals
    ├─ Notification dropdowns
    ├─ User menu dropdowns
    └─ Prominent cards

glass-light-soft
    ├─ Sidebar
    ├─ Header
    ├─ Dashboard cards
    ├─ Content sections
    └─ Standard components

glass-light-strong
    ├─ Search bars
    ├─ Input fields
    ├─ Hero sections
    └─ High-emphasis areas
```

---

## Brand Colors Quick Copy

```html
Primary Blue:       #0056b3
Secondary Green:    #218838
Accent Teal:        #64CCC5

Dark Gray:          #111827 (gray-900)
Medium Gray:        #374151 (gray-600)
Light Gray:         #9CA3AF (gray-500)

Success:            #10b981 (emerald-500)
Warning:            #f59e0b (yellow-500)
Error:              #ef4444 (red-500)
Info:               #3b82f6 (blue-500)
```

---

## Tailwind Classes Cheat Sheet

```
Spacing:            p-4 m-4 gap-4
Rounded:            rounded-lg rounded-xl rounded-2xl rounded-3xl
Shadow:             shadow-sm shadow-md shadow-lg shadow-xl shadow-2xl
Transitions:        transition-colors transition-all duration-200
Opacity:            opacity-50 opacity-100
Text:               text-gray-900 text-gray-600 text-gray-500
Background:         bg-white bg-blue-50 bg-blue-100
Border:             border border-blue-100 border-blue-200
```

---

## Emergency Fallbacks

If glass effect doesn't work:

```blade
<!-- Fallback: Use solid white with shadow -->
:class="darkMode ? 'glass-dark' : 'bg-white shadow-lg border border-gray-100'"
```

If hover color doesn't work:

```blade
<!-- Fallback: Use darker gray -->
:class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'"
```

If backdrop-filter not supported:

```blade
<!-- Fallback: Use solid color -->
:class="darkMode ? '...' : 'bg-white/80'"
```

---

## One-Liners

Add light mode to any element:
```blade
:class="darkMode ? 'existing-dark' : 'glass-light-soft'"
```

---

## Common Questions

**Q: Why three glass classes?**
A: Different contexts need different opacity levels. Glass-light for prominence, glass-light-soft for standards, glass-light-strong for inputs.

**Q: Should I use borders with glass?**
A: No, the glass effect provides boundary. Use borders only for emphasis.

**Q: Can I mix glass classes?**
A: No, pick one. Use glass-light-soft as default, glass-light for dropdowns, glass-light-strong for search.

**Q: What if text is hard to read?**
A: Use darker text (text-gray-900) or switch to glass-light-strong for more opacity.

**Q: How do I test dark/light mode?**
A: Click the theme toggle button in the header. localStorage persists preference.

---

## Next Steps

1. Apply light mode to any new components
2. Migrate existing components from `bg-white shadow-xl` to `glass-light-soft`
3. Test on light mode before shipping
4. Check text contrast with WCAG AA standards
5. Verify focus indicators are visible

---

**For detailed information, see**: `LIGHT-MODE-UX-ENHANCEMENTS.md`
