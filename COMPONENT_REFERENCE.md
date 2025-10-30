# Component Reference

**The Last Messenger** - Admin Panel Component Library
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 📚 Overview

The admin panel uses consistent component patterns built with Blade templates, Alpine.js, and Tailwind CSS. While not using separate component files, the patterns are reusable across all modules.

---

## 🎨 Design System

### Color Palette

**Primary Colors**:
- `#0056b3` - Primary Blue
- `#218838` - Secondary Green
- `#64CCC5` - Accent Teal/Cyan

**Gradients**:
```css
/* Main Gradient */
from-[#0056b3] to-[#218838]

/* Module-Specific */
from-blue-500 to-blue-600      /* Articles */
from-emerald-500 to-emerald-600 /* Hadith */
from-purple-500 to-purple-600   /* Media */
from-[#64CCC5] to-[#4BA5A3]    /* Users/Sahaba */
```

**Status Colors**:
- Published: `bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400`
- Draft: `bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400`
- Archived: `bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400`

---

## 📦 Component Patterns

### 1. Statistics Card

**Description**: Displays key metrics with icon, number, and growth indicator.

**Props**:
| Name | Type | Required | Description |
|------|------|----------|-------------|
| title | string | Yes | Card title |
| value | number | Yes | Main metric value |
| icon | string | Yes | Lucide icon name |
| color | string | Yes | Color scheme (blue, green, purple, cyan) |
| badge | string | No | Growth percentage |

**Usage**:
```blade
<div class="group relative overflow-hidden rounded-2xl transition-all duration-300 hover:scale-105 hover:shadow-2xl"
     :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">

    {{-- Gradient Accent --}}
    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-500/20 to-blue-600/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>

    <div class="relative z-10 p-6">
        <div class="flex items-center justify-between mb-4">
            {{-- Icon --}}
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/50">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>

            {{-- Badge --}}
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                +12%
            </span>
        </div>

        {{-- Value --}}
        <h3 class="text-3xl font-black mb-2 bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-400 dark:to-blue-200 bg-clip-text text-transparent">
            152
        </h3>

        {{-- Title --}}
        <p class="font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المقالات</p>

        {{-- Footer --}}
        <div class="mt-4 flex items-center text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <i data-lucide="trending-up" class="w-4 h-4 ml-1 text-emerald-500"></i>
            <span>نمو هذا الشهر</span>
        </div>
    </div>
</div>
```

**Variants**:
- Blue (Articles)
- Green (Hadith)
- Purple (Media)
- Cyan (Users)

**Accessibility**:
- ARIA labels for metrics
- Proper color contrast
- Hover states visible

---

### 2. Search Bar

**Description**: Debounced search input with icon.

**Props**:
| Name | Type | Required | Description |
|------|------|----------|-------------|
| placeholder | string | Yes | Input placeholder text |
| x-model | string | Yes | Alpine.js model binding |

**Usage**:
```blade
<div class="relative">
    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5"
       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
    <input
        type="text"
        x-model="searchQuery"
        @input.debounce.500ms="loadData()"
        placeholder="بحث في المقالات..."
        class="w-full pr-12 pl-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all"
        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-200 text-gray-900'"
    />
</div>
```

**Features**:
- 500ms debounce
- Dark/light mode adaptive
- Focus ring animation
- RTL compatible

---

### 3. Filter Select

**Description**: Dropdown filter with dark mode support.

**Props**:
| Name | Type | Required | Description |
|------|------|----------|-------------|
| x-model | string | Yes | Alpine.js model binding |
| options | array | Yes | Option values |

**Usage**:
```blade
<select
    x-model="filters.status"
    @change="loadData()"
    class="px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500"
    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-200 text-gray-900'"
>
    <option value="">كل الحالات</option>
    <option value="published">منشور</option>
    <option value="draft">مسودة</option>
    <option value="archived">أرشيف</option>
</select>
```

---

### 4. Primary Button

**Description**: Gradient button with hover effects.

**Props**:
| Name | Type | Required | Description |
|------|------|----------|-------------|
| text | string | Yes | Button text |
| icon | string | No | Lucide icon name |
| href | string | No | Link URL |

**Usage**:
```blade
<a href="{{ route('admin.articles.create') }}"
   class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all flex items-center gap-2 whitespace-nowrap">
    <i data-lucide="plus" class="w-5 h-5"></i>
    <span>مقال جديد</span>
</a>
```

**Variants**:
- Primary (blue gradient)
- Success (green gradient)
- Danger (red solid)
- Ghost (transparent with border)

---

### 5. Data Table

**Description**: Responsive table with hover effects.

**Features**:
- Dark/light mode
- Hover row highlight
- Sticky header
- Responsive scroll

**Usage**:
```blade
<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b" :class="darkMode ? 'border-gray-700 bg-gray-700/50' : 'border-gray-200 bg-gray-50'">
                <th class="px-6 py-4 text-right text-sm font-bold"
                    :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                <th class="px-6 py-4 text-right text-sm font-bold"
                    :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العنوان</th>
                <!-- More headers -->
            </tr>
        </thead>
        <tbody>
            <template x-for="item in items" :key="item.id">
                <tr class="border-b"
                    :class="darkMode ? 'border-gray-700 hover:bg-gray-700/30' : 'border-gray-100 hover:bg-gray-50'">
                    <td class="px-6 py-4 text-sm"
                        :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                        x-text="item.id"></td>
                    <!-- More cells -->
                </tr>
            </template>
        </tbody>
    </table>
</div>
```

---

### 6. Status Badge

**Description**: Colored badge for status display.

**Props**:
| Name | Type | Required | Description |
|------|------|----------|-------------|
| status | string | Yes | Status value |

**Usage**:
```blade
<span class="px-3 py-1 rounded-full text-xs font-bold"
      :class="{
          'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400': status === 'published',
          'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400': status === 'draft',
          'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400': status === 'archived'
      }"
      x-text="getStatusText(status)">
</span>
```

---

### 7. Action Buttons

**Description**: Edit/Delete action buttons.

**Usage**:
```blade
<div class="flex items-center justify-center gap-2">
    {{-- Edit Button --}}
    <a :href="`{{ route('admin.articles.edit', '') }}/${item.id}`"
       class="p-2 rounded-lg transition-colors"
       :class="darkMode ? 'hover:bg-blue-500/20 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
        <i data-lucide="edit-2" class="w-4 h-4"></i>
    </a>

    {{-- Delete Button --}}
    <button @click="deleteItem(item.id)"
            class="p-2 rounded-lg transition-colors"
            :class="darkMode ? 'hover:bg-red-500/20 text-red-400' : 'hover:bg-red-50 text-red-600'">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
    </button>
</div>
```

---

### 8. Loading Skeleton

**Description**: Placeholder while data loads.

**Usage**:
```blade
<div x-show="loading" class="p-12 text-center">
    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2"
         :class="darkMode ? 'border-white' : 'border-blue-500'"></div>
    <p class="mt-4 font-bold"
       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
</div>
```

---

### 9. Empty State

**Description**: Display when no data available.

**Usage**:
```blade
<div class="text-center py-12">
    <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center"
         :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
        <i data-lucide="inbox" class="w-10 h-10"
           :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
    </div>
    <p class="font-semibold"
       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد بيانات</p>
    <p class="text-sm mt-2"
       :class="darkMode ? 'text-gray-500' : 'text-gray-500'">ابدأ بإضافة عنصر جديد</p>
</div>
```

---

### 10. Form Input

**Description**: Text input with label and error display.

**Usage**:
```blade
<div class="mb-4">
    <label class="block text-sm font-bold mb-2"
           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        العنوان *
    </label>
    <input
        type="text"
        x-model="form.title"
        class="w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all"
        :class="{
            'bg-gray-700 border-gray-600 text-white': darkMode,
            'bg-white border-gray-300 text-gray-900': !darkMode,
            'border-red-500': errors.title
        }"
    />
    <p x-show="errors.title"
       class="text-red-500 text-sm mt-1"
       x-text="errors.title"></p>
</div>
```

---

### 11. Form Textarea

**Description**: Multi-line text input.

**Usage**:
```blade
<div class="mb-4">
    <label class="block text-sm font-bold mb-2"
           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        الوصف
    </label>
    <textarea
        x-model="form.description"
        rows="4"
        class="w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all"
        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
    ></textarea>
</div>
```

---

### 12. Form Select

**Description**: Dropdown select input.

**Usage**:
```blade
<div class="mb-4">
    <label class="block text-sm font-bold mb-2"
           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        التصنيف *
    </label>
    <select
        x-model="form.category_id"
        class="w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500"
        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
    >
        <option value="">اختر التصنيف</option>
        <template x-for="category in categories" :key="category.id">
            <option :value="category.id" x-text="category.name.ar"></option>
        </template>
    </select>
</div>
```

---

### 13. Language Tabs

**Description**: Multi-language input tabs.

**Usage**:
```blade
<div class="mb-6">
    {{-- Tab Navigation --}}
    <div class="flex gap-2 mb-4 border-b"
         :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
        <template x-for="lang in ['ar', 'en', 'fr', 'tr', 'ur']" :key="lang">
            <button
                @click="currentLang = lang"
                class="px-4 py-2 font-bold transition-all"
                :class="currentLang === lang ?
                    'text-blue-500 border-b-2 border-blue-500' :
                    (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                x-text="getLanguageName(lang)">
            </button>
        </template>
    </div>

    {{-- Tab Content --}}
    <div x-show="currentLang === 'ar'">
        <input type="text" x-model="form.title.ar" placeholder="العنوان بالعربية" />
    </div>
    <div x-show="currentLang === 'en'">
        <input type="text" x-model="form.title.en" placeholder="Title in English" />
    </div>
    {{-- More language tabs --}}
</div>
```

---

### 14. Modal

**Description**: Overlay dialog for confirmations.

**Usage**:
```blade
<div x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center"
     @click.self="showModal = false">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    {{-- Modal --}}
    <div class="relative z-10 p-6 rounded-2xl max-w-md w-full mx-4"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white shadow-2xl'">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold"
                :class="darkMode ? 'text-white' : 'text-gray-900'">تأكيد الحذف</h3>
            <button @click="showModal = false"
                    class="p-2 rounded-lg transition-colors"
                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Body --}}
        <p class="mb-6"
           :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            هل أنت متأكد من حذف هذا العنصر؟
        </p>

        {{-- Footer --}}
        <div class="flex gap-3">
            <button @click="confirmDelete()"
                    class="flex-1 px-4 py-3 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition-colors">
                حذف
            </button>
            <button @click="showModal = false"
                    class="flex-1 px-4 py-3 font-bold rounded-xl transition-colors"
                    :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                إلغاء
            </button>
        </div>
    </div>
</div>
```

---

### 15. Toast Notification

**Description**: Temporary notification message.

**Usage**:
```blade
<div x-show="toast.show"
     x-transition
     class="fixed bottom-6 right-6 z-50 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 max-w-md"
     :class="{
         'bg-green-500 text-white': toast.type === 'success',
         'bg-red-500 text-white': toast.type === 'error',
         'bg-yellow-500 text-white': toast.type === 'warning',
         'bg-blue-500 text-white': toast.type === 'info'
     }">

    <i :data-lucide="getToastIcon(toast.type)" class="w-5 h-5"></i>
    <p class="font-bold flex-1" x-text="toast.message"></p>
    <button @click="toast.show = false">
        <i data-lucide="x" class="w-5 h-5"></i>
    </button>
</div>
```

---

### 16. Pagination

**Description**: Page navigation controls.

**Usage**:
```blade
<div class="flex items-center justify-between p-6 border-t"
     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">

    {{-- Info --}}
    <p class="text-sm"
       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        عرض <span class="font-bold" x-text="meta.from"></span>
        إلى <span class="font-bold" x-text="meta.to"></span>
        من <span class="font-bold" x-text="meta.total"></span> نتيجة
    </p>

    {{-- Navigation --}}
    <div class="flex gap-2">
        <button @click="loadPage(meta.current_page - 1)"
                :disabled="!meta.prev"
                class="px-4 py-2 rounded-lg font-bold transition-all"
                :class="meta.prev ?
                    (darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-900 hover:bg-gray-300') :
                    'opacity-50 cursor-not-allowed'">
            السابق
        </button>
        <button @click="loadPage(meta.current_page + 1)"
                :disabled="!meta.next"
                class="px-4 py-2 rounded-lg font-bold transition-all"
                :class="meta.next ?
                    (darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-900 hover:bg-gray-300') :
                    'opacity-50 cursor-not-allowed'">
            التالي
        </button>
    </div>
</div>
```

---

### 17. Card Container

**Description**: Standard card wrapper.

**Usage**:
```blade
<div class="rounded-2xl p-6 transition-all duration-300"
     :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
    {{-- Card content --}}
</div>
```

---

### 18. Section Header

**Description**: Page section title.

**Usage**:
```blade
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-1"
            :class="darkMode ? 'text-white' : 'text-gray-900'">إدارة المقالات</h2>
        <p class="text-sm"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عرض وإدارة جميع المقالات</p>
    </div>
    <a href="{{ route('admin.articles.create') }}"
       class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
        مقال جديد
    </a>
</div>
```

---

### 19. Breadcrumbs

**Description**: Navigation breadcrumb trail.

**Usage**:
```blade
<nav class="flex items-center gap-2 mb-6 text-sm">
    <a href="{{ route('admin.dashboard.index') }}"
       class="transition-colors"
       :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
        لوحة التحكم
    </a>
    <i data-lucide="chevron-left" class="w-4 h-4"
       :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
    <span class="font-bold"
          :class="darkMode ? 'text-white' : 'text-gray-900'">المقالات</span>
</nav>
```

---

### 20. File Upload

**Description**: Drag & drop file upload.

**Usage**:
```blade
<div class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
     :class="darkMode ? 'border-gray-600 hover:border-blue-500' : 'border-gray-300 hover:border-blue-500'"
     @dragover.prevent
     @drop.prevent="handleDrop">

    <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto mb-4"
       :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>

    <p class="font-bold mb-2"
       :class="darkMode ? 'text-white' : 'text-gray-900'">
        اسحب الملفات هنا أو انقر للتحديد
    </p>

    <p class="text-sm"
       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        الحد الأقصى: 10 ميجابايت
    </p>

    <input type="file" class="hidden" @change="handleFileSelect" />
</div>
```

---

## 🎨 Utility Classes

### Spacing
```css
p-6      /* padding: 1.5rem */
mb-4     /* margin-bottom: 1rem */
gap-4    /* gap: 1rem */
```

### Typography
```css
text-sm      /* font-size: 0.875rem */
text-xl      /* font-size: 1.25rem */
font-bold    /* font-weight: 700 */
font-black   /* font-weight: 900 */
```

### Colors
```css
text-gray-900      /* Dark mode: auto-adjusts */
bg-white           /* Light mode background */
bg-gray-800        /* Dark mode background */
```

### Borders
```css
rounded-xl    /* border-radius: 0.75rem */
rounded-2xl   /* border-radius: 1rem */
border        /* border-width: 1px */
```

### Effects
```css
shadow-lg                /* box-shadow: large */
hover:scale-105          /* transform: scale(1.05) on hover */
transition-all           /* transition: all 150ms */
backdrop-blur-lg         /* backdrop-filter: blur(16px) */
```

---

## 🌗 Dark Mode Support

All components support dark mode via Alpine.js reactive classes:

```blade
:class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'"
```

**Dark Mode State**:
```javascript
darkMode: localStorage.getItem('darkMode') !== 'false'
```

---

## ♿ Accessibility

**Best Practices**:
- ✅ Proper ARIA labels
- ✅ Keyboard navigation support
- ✅ Focus states visible
- ✅ Color contrast WCAG AA compliant
- ✅ Screen reader friendly
- ✅ Touch targets 44x44px minimum

---

## 📱 Responsive Design

**Breakpoints**:
```css
sm:   /* @media (min-width: 640px) */
md:   /* @media (min-width: 768px) */
lg:   /* @media (min-width: 1024px) */
xl:   /* @media (min-width: 1280px) */
2xl:  /* @media (min-width: 1536px) */
```

**Example**:
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    {{-- Responsive grid --}}
</div>
```

---

## 🎉 Conclusion

These component patterns provide a consistent, accessible, and beautiful admin interface. Mix and match components to build new features while maintaining design consistency.

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger UI Team
