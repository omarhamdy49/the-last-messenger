# Media Picker - System Architecture

## Component Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Your Application View                         │
│  (Articles, Events, Users, etc.)                                     │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Alpine.js Data Context                                        │  │
│  │                                                                │  │
│  │  x-data="{ selectedImage: null }"                            │  │
│  │                                                                │  │
│  │  ┌────────────────────────────────────────────────────────┐  │  │
│  │  │ Trigger Button                                          │  │  │
│  │  │                                                          │  │  │
│  │  │  <button @click="MediaPicker.open({ ... })">          │  │  │
│  │  │    Choose Image                                         │  │  │
│  │  │  </button>                                              │  │  │
│  │  └────────────────────────────────────────────────────────┘  │  │
│  │                                                                │  │
│  │  ┌────────────────────────────────────────────────────────┐  │  │
│  │  │ Hidden Input (for form submission)                     │  │  │
│  │  │                                                          │  │  │
│  │  │  <input type="hidden" :value="selectedImage?.id">     │  │  │
│  │  └────────────────────────────────────────────────────────┘  │  │
│  │                                                                │  │
│  │  ┌────────────────────────────────────────────────────────┐  │  │
│  │  │ Preview (optional)                                      │  │  │
│  │  │                                                          │  │  │
│  │  │  <img :src="selectedImage?.url">                       │  │  │
│  │  └────────────────────────────────────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                   │
                                   │ Opens modal
                                   ▼
┌─────────────────────────────────────────────────────────────────────┐
│         <x-admin.media-picker-modal /> Component                     │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Modal Container (Alpine.js: x-data="mediaPickerModal()")    │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Header                                                   │ │  │
│  │  │  - Title: "Choose Media"                                │ │  │
│  │  │  - Close button                                         │ │  │
│  │  │  - Selection mode indicator                             │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Tabs                                                     │ │  │
│  │  │  [Browse Library] | [Upload New]                        │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Browse Tab                                               │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ Search & Filters                                  │   │ │  │
│  │  │  │  - Search input                                   │   │ │  │
│  │  │  │  - Type filter dropdown                           │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ Media Grid                                        │   │ │  │
│  │  │  │                                                    │   │ │  │
│  │  │  │  [img] [img] [img] [img] [img]                   │   │ │  │
│  │  │  │  [img] [img] [img] [img] [img]                   │   │ │  │
│  │  │  │  [img] [img] [img] [img] [img]                   │   │ │  │
│  │  │  │                                                    │   │ │  │
│  │  │  │  - Click to select/deselect                       │   │ │  │
│  │  │  │  - Checkmark on selected items                    │   │ │  │
│  │  │  │  - Accent border on selected                      │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  │                                                           │ │  │
│  │  │  [Load More Button]                                      │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Upload Tab                                               │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ Drop Zone                                         │   │ │  │
│  │  │  │                                                    │   │ │  │
│  │  │  │     [Upload Icon]                                 │   │ │  │
│  │  │  │     Drop files here or click to browse           │   │ │  │
│  │  │  │     [Choose Files Button]                         │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ Upload Queue                                      │   │ │  │
│  │  │  │                                                    │   │ │  │
│  │  │  │  [file.jpg] ████████░░ 80%                       │   │ │  │
│  │  │  │  [video.mp4] ██████░░░░ 60%                      │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Selected Items Sidebar (if items selected)              │ │  │
│  │  │                                                           │ │  │
│  │  │  <x-admin.selected-media-preview />                     │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ Selected (2)                    [Clear All]       │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ [≡] [thumbnail] image.jpg            [x]         │   │ │  │
│  │  │  │      Image | 250KB | 1920x1080                   │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  │                                                           │ │  │
│  │  │  ┌──────────────────────────────────────────────────┐   │ │  │
│  │  │  │ [≡] [thumbnail] photo.jpg            [x]         │   │ │  │
│  │  │  │      Image | 180KB | 1600x900                    │   │ │  │
│  │  │  └──────────────────────────────────────────────────┘   │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │                                                                │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ Footer Actions                                           │ │  │
│  │  │                                                           │ │  │
│  │  │  2 items selected                                        │ │  │
│  │  │                         [Cancel] [Confirm Selection]    │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                   │
                                   │ Confirms selection
                                   ▼
                           ┌───────────────────┐
                           │  onSelect callback │
                           │  Returns media     │
                           │  object(s)         │
                           └───────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        Your Application View                         │
│  Updates Alpine.js reactive data                                     │
│  selectedImage = media                                               │
│  Hidden input updates: <input :value="media.id">                    │
│  Preview updates: <img :src="media.url">                            │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow

```
User Action → JavaScript API → Alpine.js Component → Backend API → Response
     │              │                    │                │            │
     │              │                    │                │            │
     ▼              ▼                    ▼                ▼            ▼
Click Button   MediaPicker.open()   mediaPickerModal()  GET/POST    JSON
                                         │              /admin/media  Response
                                         │                   │
                                         │                   │
                                         ▼                   ▼
                                    Load Media          Returns
                                    Display Grid        Media Array
                                         │
                                         │
                                    User Selects
                                    Items in Grid
                                         │
                                         │
                                         ▼
                                    Click Confirm
                                         │
                                         │
                                         ▼
                                    onSelect(media)
                                         │
                                         │
                                         ▼
                                    Update Parent
                                    Component Data
```

## Component Hierarchy

```
Page View (Articles/Events/etc)
│
├── Alpine.js Data Context
│   ├── selectedImage: null
│   ├── galleryImages: []
│   └── Functions
│       ├── chooseFeaturedImage()
│       └── chooseGalleryImages()
│
└── Media Picker Modal Component
    │
    ├── mediaPickerModal() [Alpine.js]
    │   ├── State
    │   │   ├── isOpen: boolean
    │   │   ├── activeTab: 'browse' | 'upload'
    │   │   ├── selectionMode: 'single' | 'multiple'
    │   │   ├── filterType: 'all' | 'image' | 'video'
    │   │   ├── searchQuery: string
    │   │   ├── mediaItems: array
    │   │   ├── selectedItems: array
    │   │   └── onSelectCallback: function
    │   │
    │   └── Methods
    │       ├── open(options)
    │       ├── close()
    │       ├── loadMedia(page)
    │       ├── searchMedia()
    │       ├── toggleSelection(media)
    │       ├── isSelected(mediaId)
    │       ├── confirmSelection()
    │       └── formatFileSize(bytes)
    │
    ├── fileUploader() [Alpine.js]
    │   ├── State
    │   │   ├── dragOver: boolean
    │   │   └── uploadQueue: array
    │   │
    │   └── Methods
    │       ├── handleDrop(event)
    │       ├── handleFileSelect(event)
    │       ├── uploadFiles(files)
    │       └── uploadFile(file, queueItem)
    │
    └── Selected Media Preview Component
        └── Displays selectedItems array
            ├── Thumbnail
            ├── Metadata
            ├── Remove button
            └── Expand/collapse details
```

## API Integration

```
Frontend                         Backend
────────────────────────────────────────────────────────────

MediaPicker.open()
     │
     ├─► GET /admin/media?page=1&type=image&search=...
     │                                              │
     │                                              ▼
     │                                   MediaController@index
     │                                              │
     │                                              ▼
     │                                   Query Media model
     │                                   Apply filters
     │                                   Paginate results
     │                                              │
     │   ◄──────────────────────────────────────────
     │   JSON Response:
     │   {
     │     "data": [...],
     │     "current_page": 1,
     │     "last_page": 5
     │   }
     │
     ▼
Display in grid
User selects items
Click confirm
     │
     ├─► onSelect callback
     │   Returns selected media object(s)
     │
     └─► Update parent component


Upload Tab
     │
     ├─► POST /admin/media
     │   FormData: file, collection_name, etc.
     │                                              │
     │                                              ▼
     │                                   MediaController@store
     │                                              │
     │                                              ▼
     │                                   MediaService->upload()
     │                                   Store file
     │                                   Create Media record
     │                                   Optimize if needed
     │                                              │
     │   ◄──────────────────────────────────────────
     │   JSON Response:
     │   {
     │     "success": true,
     │     "data": { media object }
     │   }
     │
     ▼
Add to grid
Auto-select (single mode)
Update UI
```

## File Structure

```
the-last-messenger-be/
│
├── resources/
│   ├── views/
│   │   ├── components/
│   │   │   └── admin/
│   │   │       ├── media-picker-modal.blade.php       (667 lines)
│   │   │       └── selected-media-preview.blade.php   (185 lines)
│   │   │
│   │   ├── admin/
│   │   │   ├── examples/
│   │   │   │   └── media-picker-demo.blade.php        (427 lines)
│   │   │   │
│   │   │   └── articles/
│   │   │       └── _form-example.blade.php            (423 lines)
│   │   │
│   │   └── layouts/
│   │       └── admin.blade.php                        (includes component)
│   │
│   └── js/
│       └── admin/
│           └── media-picker.js                        (364 lines)
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Admin/
│   │           └── MediaController.php                (existing)
│   │
│   ├── Models/
│   │   └── Media.php                                  (existing)
│   │
│   └── Services/
│       └── MediaService.php                           (existing)
│
└── routes/
    └── web.php                                        (existing routes)


Documentation Files:
├── MEDIA-PICKER-INTEGRATION.md                        (789 lines)
├── MEDIA-PICKER-QUICK-REFERENCE.md                    (231 lines)
├── MEDIA-PICKER-IMPLEMENTATION-SUMMARY.md             (this summary)
└── MEDIA-PICKER-ARCHITECTURE.md                       (this file)
```

## State Management

```
┌─────────────────────────────────────────────────────────┐
│              Alpine.js Reactive State                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Parent Component (Your View)                           │
│  ┌────────────────────────────────────────────────┐    │
│  │ x-data="{                                       │    │
│  │   selectedImage: null,      // Single item     │    │
│  │   galleryImages: []         // Multiple items  │    │
│  │ }"                                              │    │
│  └────────────────────────────────────────────────┘    │
│                        ▲                                 │
│                        │ Updates via callback            │
│                        │                                 │
│  Media Picker Modal                                     │
│  ┌────────────────────────────────────────────────┐    │
│  │ x-data="mediaPickerModal()" {                  │    │
│  │   isOpen: false,                               │    │
│  │   activeTab: 'browse',                         │    │
│  │   selectionMode: 'single',                     │    │
│  │   filterType: 'all',                           │    │
│  │   searchQuery: '',                             │    │
│  │   mediaItems: [],          // From API         │    │
│  │   selectedItems: [],       // User selection   │    │
│  │   onSelectCallback: null   // Parent callback  │    │
│  │ }                                               │    │
│  └────────────────────────────────────────────────┘    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Event Flow

```
User Interaction Events
───────────────────────

1. Open Modal
   Button Click → @click="MediaPicker.open(options)"
                → dispatch('media-picker-open')
                → Alpine receives event
                → Modal opens (isOpen = true)
                → Load media from API

2. Select Media
   Click Image → @click="toggleSelection(media)"
                → Update selectedItems array
                → UI updates (checkmark, border)
                → Preview panel updates

3. Search
   Type in Input → @input.debounce.300ms="searchMedia()"
                 → Wait 300ms
                 → API call with search query
                 → Update mediaItems array
                 → Grid re-renders

4. Upload
   Drop File → @drop.prevent="handleDrop(event)"
             → Add to uploadQueue
             → POST to /admin/media
             → Progress updates
             → Add to mediaItems
             → Auto-select (if single mode)

5. Confirm
   Click Button → @click="confirmSelection()"
                → Call onSelectCallback(selectedItems)
                → Parent component updates
                → dispatch('media-selected')
                → Modal closes (isOpen = false)

6. Cancel
   Click Cancel → @click="close()"
   Press ESC → @keydown.escape.window="close()"
              → Clear selection
              → Modal closes
```

## Integration Patterns

### Pattern 1: Single Selection with Preview

```blade
<div x-data="{ media: null }">
    <!-- Hidden input for form -->
    <input type="hidden" name="media_id" :value="media?.id">

    <!-- Trigger button -->
    <button @click="MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (m) => { media = m; }
    })">
        Choose Image
    </button>

    <!-- Preview -->
    <img x-show="media" :src="media?.url">
</div>
```

### Pattern 2: Multiple Selection with List

```blade
<div x-data="{ items: [] }">
    <!-- Hidden inputs for each item -->
    <template x-for="item in items">
        <input type="hidden" name="media[]" :value="item.id">
    </template>

    <!-- Trigger button -->
    <button @click="MediaPicker.open({
        mode: 'multiple',
        onSelect: (selected) => { items = selected; }
    })">
        Select Media
    </button>

    <!-- List -->
    <div class="grid grid-cols-4 gap-4">
        <template x-for="item in items">
            <img :src="item.url">
        </template>
    </div>
</div>
```

### Pattern 3: Vanilla JavaScript

```html
<button id="choose-btn">Choose Media</button>
<div id="preview"></div>

<script>
document.getElementById('choose-btn').addEventListener('click', () => {
    MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (media) => {
            document.getElementById('preview').innerHTML = `
                <img src="${media.url}">
            `;
        }
    });
});
</script>
```

## Technical Stack

```
┌─────────────────────────────────────────┐
│           Frontend Layer                 │
├─────────────────────────────────────────┤
│ Alpine.js 3.x     (Reactivity)          │
│ Tailwind CSS 4    (Styling)             │
│ Lucide Icons      (Icons)               │
│ JavaScript ES6    (Logic)               │
└─────────────────────────────────────────┘
                    ▼
┌─────────────────────────────────────────┐
│           Backend Layer                  │
├─────────────────────────────────────────┤
│ Laravel 10+       (Framework)           │
│ Blade Templates   (Views)               │
│ Eloquent ORM      (Database)            │
│ Storage System    (Files)               │
└─────────────────────────────────────────┘
                    ▼
┌─────────────────────────────────────────┐
│           Data Layer                     │
├─────────────────────────────────────────┤
│ MySQL/PostgreSQL  (Database)            │
│ File System       (Storage)             │
│ Redis (optional)  (Cache)               │
└─────────────────────────────────────────┘
```

## Security Flow

```
Request → CSRF Token Check → Authentication → Authorization → Process
   │            │                  │               │             │
   │            │                  │               │             │
   ▼            ▼                  ▼               ▼             ▼
POST      Token Valid?      User Logged In?   Has Permission?  Execute
Upload    ✓ Required        ✓ Required        (Optional)       Upload
          ✗ 419 Error       ✗ 401 Error       ✗ 403 Error     Store
                                                                Return
```

## Performance Optimization Points

1. **Lazy Loading**: Grid images use `loading="lazy"`
2. **Debounced Search**: 300ms delay prevents excessive API calls
3. **Pagination**: Load 20 items at a time
4. **Efficient Rendering**: Alpine.js only updates changed elements
5. **Image Optimization**: Backend optimizes uploaded images
6. **Caching**: Browser caches static assets
7. **Minimal DOM**: Only render visible elements

## Error Handling

```
┌─────────────────────────────────────────────────────────┐
│                   Error Handling Flow                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  API Request                                             │
│       │                                                  │
│       ├─► Success (200-299)                             │
│       │       └─► Update UI, show data                  │
│       │                                                  │
│       ├─► Client Error (400-499)                        │
│       │       ├─► 401: Redirect to login                │
│       │       ├─► 403: Show permission error            │
│       │       ├─► 404: Show "not found" message         │
│       │       └─► 422: Show validation errors           │
│       │                                                  │
│       ├─► Server Error (500-599)                        │
│       │       └─► Show generic error message            │
│       │           Log to console                        │
│       │                                                  │
│       └─► Network Error                                 │
│               └─► Show "connection failed" message      │
│                   Retry button                          │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Browser Compatibility

```
┌──────────────────────────────────────────────────┐
│ Required Browser Features                         │
├──────────────────────────────────────────────────┤
│ ✓ ES6 JavaScript (const, let, arrow functions)   │
│ ✓ Fetch API                                       │
│ ✓ FormData with file upload                      │
│ ✓ CSS Grid                                        │
│ ✓ CSS Flexbox                                     │
│ ✓ CSS Custom Properties                           │
│ ✓ CSS Transforms                                  │
│ ✓ CSS Transitions                                 │
│ ✓ CSS Backdrop Filter (for glassmorphism)        │
│ ✓ File API                                        │
│ ✓ Drag and Drop API                              │
│ ✓ XMLHttpRequest.upload (for progress)          │
└──────────────────────────────────────────────────┘

Supported Browsers:
✓ Chrome/Edge 90+
✓ Firefox 88+
✓ Safari 14+
✓ iOS Safari 14+
✓ Chrome Android 90+
```

---

**Architecture Documentation Complete**

This architecture supports:
- Modular design
- Easy integration
- Scalability
- Maintainability
- Performance
- Security
- Accessibility
