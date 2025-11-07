# Design System Quick Card

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    THE LAST MESSENGER ADMIN                             │
│                  NETFLIX-GRADE RTL DESIGN SYSTEM                        │
└─────────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════════╗
║ COLORS                                                                ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  PRIMARY BLUE      ████  #0056b3  rgb(0, 86, 179)                   ║
║  SECONDARY GREEN   ████  #218838  rgb(33, 136, 56)                  ║
║  ACCENT TEAL       ████  #64CCC5  rgb(100, 204, 197)                ║
║                                                                       ║
║  GRADIENT: from-[#0056b3] to-[#218838]                              ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ TYPOGRAPHY                                                            ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  FONT FAMILY                                                          ║
║  ├─ Primary:  Cairo (Arabic-optimized)                               ║
║  └─ Arabic:   Amiri (serif)                                          ║
║                                                                       ║
║  FONT WEIGHTS                                                         ║
║  ├─ font-light     300                                               ║
║  ├─ font-normal    400                                               ║
║  ├─ font-semibold  600                                               ║
║  ├─ font-bold      700                                               ║
║  └─ font-black     900                                               ║
║                                                                       ║
║  GRADIENT TEXT                                                        ║
║  ├─ text-gradient          (Blue → Green)                            ║
║  ├─ text-gradient-accent   (Teal → Blue)                             ║
║  └─ text-gradient-reverse  (Green → Blue)                            ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ BUTTONS                                                               ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  btn-primary      ▓▓▓▓▓▓▓  Gradient button                          ║
║  btn-secondary    ▒▒▒▒▒▒▒  Gray button                              ║
║  btn-ghost        ░░░░░░░  Outlined button                           ║
║  btn-icon         [ ⚙ ]    Icon-only button                          ║
║  btn-magnetic     ≋≋≋≋≋≋≋  Magnetic hover effect                    ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ BADGES                                                                ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  badge-primary    ▓▓▓  Gradient badge                               ║
║  badge-success    🟢   Green badge                                   ║
║  badge-warning    🟡   Yellow badge                                  ║
║  badge-danger     🔴   Red badge                                     ║
║  badge-info       🔵   Blue badge                                    ║
║  badge-pulse      ◉    Pulsing badge                                ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ CARDS                                                                 ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  premium-card     ╔═══════╗  Hover lift/scale effect                ║
║                   ║ Image ║                                          ║
║                   ╠═══════╣                                          ║
║                   ║ Title ║                                          ║
║                   ╚═══════╝                                          ║
║                                                                       ║
║  glass-card       ╔═══════╗  Glassmorphism effect                   ║
║                   ║ ░░░░░ ║  Backdrop blur                          ║
║                   ║ ░░░░░ ║  Semi-transparent                       ║
║                   ╚═══════╝                                          ║
║                                                                       ║
║  EFFECTS:                                                             ║
║  ├─ card-overlay   Dark overlay on hover                             ║
║  ├─ card-shimmer   Shimmer animation                                 ║
║  └─ card-glow      Glowing shadow                                    ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ FORMS                                                                 ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  input-custom      [___________________]  Text input                 ║
║  select-custom     [Select ▼]            Dropdown                    ║
║  checkbox-custom   [✓]                   Checkbox                    ║
║  radio-custom      (•)                   Radio                       ║
║  switch-toggle     [ON ──]               Toggle                      ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ GLASSMORPHISM                                                         ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  glass             ░░░░░░░░░  Base glass effect                      ║
║  glass-strong      ▒▒▒▒▒▒▒▒▒  Stronger blur                         ║
║  glass-subtle      ░░░░░░░░░  Subtle blur                           ║
║  glass-card        ░░░░░░░░░  With padding/shadow                   ║
║                                                                       ║
║  EFFECT: backdrop-blur-lg + semi-transparent bg                      ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ ANIMATIONS                                                            ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  DURATIONS:                                                           ║
║  ├─ duration-fast    300ms  ⚡                                       ║
║  ├─ duration-medium  500ms  ➜                                        ║
║  └─ duration-slow    800ms  ▶                                        ║
║                                                                       ║
║  KEYFRAMES:                                                           ║
║  ├─ animate-fade-in-up       ↑  Fade in upward                      ║
║  ├─ animate-fade-in          ○  Simple fade                         ║
║  ├─ animate-slide-in-right   →  Slide from left                     ║
║  ├─ animate-float            ~  Floating motion                      ║
║  ├─ animate-shimmer          ✨  Shimmer effect                      ║
║  ├─ animate-pulse-glow       ◉  Pulsing glow                        ║
║  └─ animate-ken-burns        ⊕  Zoom/pan effect                     ║
║                                                                       ║
║  STAGGER: stagger-1 to stagger-6 (0.1s - 0.6s delays)               ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ SCROLLBARS                                                            ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  custom-scrollbar  ┃   Themed scrollbar                             ║
║  scrollbar-thin    │   Thin variant                                  ║
║  scrollbar-hide        Hidden scrollbar                              ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ SKELETON LOADING                                                      ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  skeleton          ▓▒░▓▒░  Base skeleton                            ║
║  skeleton-text     ▓▒░▓▒░  Text line                                ║
║  skeleton-circle   ◉      Circle (avatar)                           ║
║  skeleton-card     ┌─────┐ Card skeleton                            ║
║                    │▓▒░▓▒│                                           ║
║                    └─────┘                                           ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ UTILITIES                                                             ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  TEXT TRUNCATION:                                                     ║
║  ├─ truncate-2     Truncate to 2 lines                               ║
║  └─ truncate-3     Truncate to 3 lines                               ║
║                                                                       ║
║  ASPECT RATIOS:                                                       ║
║  ├─ aspect-card      16:9                                            ║
║  ├─ aspect-square    1:1                                             ║
║  └─ aspect-portrait  3:4                                             ║
║                                                                       ║
║  DIRECTION:                                                           ║
║  ├─ rtl   Right-to-left (Arabic, Urdu)                               ║
║  └─ ltr   Left-to-right (English, etc.)                              ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ DARK MODE                                                             ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  TOGGLE:  @click="$store.admin.toggleDarkMode()"                    ║
║                                                                       ║
║  PATTERN:                                                             ║
║  <div class="bg-white dark:bg-gray-900                               ║
║              text-gray-900 dark:text-white">                         ║
║    Content                                                            ║
║  </div>                                                               ║
║                                                                       ║
║  COMMON COMBINATIONS:                                                 ║
║  ├─ bg-white dark:bg-gray-900                                        ║
║  ├─ bg-gray-50 dark:bg-black                                         ║
║  ├─ text-gray-900 dark:text-white                                    ║
║  └─ border-gray-200 dark:border-gray-700                             ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ RTL SUPPORT                                                           ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  USE LOGICAL PROPERTIES:                                              ║
║                                                                       ║
║  ❌ AVOID:          ✅ USE:                                          ║
║  ml-4              ms-4   (margin-start)                             ║
║  mr-4              me-4   (margin-end)                               ║
║  pl-4              ps-4   (padding-start)                            ║
║  pr-4              pe-4   (padding-end)                              ║
║  text-left         text-start                                        ║
║  text-right        text-end                                          ║
║                                                                       ║
║  AUTO RTL COMPONENTS:                                                 ║
║  ├─ Select dropdown arrow flips                                      ║
║  ├─ Switch toggle animation reverses                                 ║
║  ├─ Table text alignment changes                                     ║
║  └─ Sidebar position flips                                           ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ ALPINE.JS STORE                                                       ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  ACCESS: $store.admin.property                                        ║
║                                                                       ║
║  PROPERTIES:                                                          ║
║  ├─ darkMode              Boolean                                    ║
║  ├─ sidebarCollapsed      Boolean                                    ║
║  ├─ language              String (en, ar, etc.)                      ║
║  ├─ isRtl                 Boolean                                    ║
║  └─ notifications         Array                                      ║
║                                                                       ║
║  METHODS:                                                             ║
║  ├─ toggleDarkMode()                                                 ║
║  ├─ toggleSidebar()                                                  ║
║  ├─ setLanguage('ar')                                                ║
║  ├─ success('Message')                                               ║
║  ├─ error('Error')                                                   ║
║  ├─ openSearch()                                                     ║
║  └─ confirm({...})                                                   ║
║                                                                       ║
║  KEYBOARD SHORTCUTS:                                                  ║
║  ├─ Escape         Close modals/search                               ║
║  └─ Cmd/Ctrl + K   Open search                                       ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ COMMON PATTERNS                                                       ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  PRIMARY BUTTON:                                                      ║
║  <button class="bg-gradient-to-r from-[#0056b3] to-[#218838]        ║
║                 text-white px-6 py-3 rounded-xl                      ║
║                 shadow-lg hover:shadow-xl hover:scale-105            ║
║                 transition-all duration-300">                        ║
║    Click Me                                                           ║
║  </button>                                                            ║
║                                                                       ║
║  GLASS CARD:                                                          ║
║  <div class="glass backdrop-blur-lg rounded-2xl p-6 shadow-lg">     ║
║    <h3 class="text-gradient text-xl font-bold mb-4">Title</h3>      ║
║    <p class="text-gray-700 dark:text-gray-300">Content</p>          ║
║  </div>                                                               ║
║                                                                       ║
║  PREMIUM CARD:                                                        ║
║  <div class="premium-card group">                                    ║
║    <img class="group-hover:scale-110 transition-transform           ║
║                duration-500" />                                      ║
║    <h3 class="group-hover:text-[#64CCC5] transition-colors">        ║
║      Title                                                            ║
║    </h3>                                                              ║
║  </div>                                                               ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ LAYOUT CONSTANTS                                                      ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  --sidebar-width:           280px                                    ║
║  --sidebar-collapsed-width:  80px                                    ║
║  --header-height:            64px                                    ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ BUILD COMMANDS                                                        ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  npm run dev      Development mode                                   ║
║  npm run build    Production build                                   ║
║  npm run watch    Watch mode                                         ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ DOCUMENTATION                                                         ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  📖 ADMIN-DESIGN-SYSTEM.md     Full documentation (800+ lines)       ║
║  📋 ADMIN-DESIGN-TOKENS.md     Quick reference (500+ lines)          ║
║  📊 ADMIN-SETUP-SUMMARY.md     Setup summary                         ║
║  🎴 DESIGN-QUICK-CARD.md       This quick card                       ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ FILE PATHS                                                            ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  resources/css/admin.css              Admin CSS (35 KB)              ║
║  resources/js/admin/store.js          Alpine store (15 KB)          ║
║  resources/js/admin/app.js            App entry (2 KB)              ║
║  tailwind.config.js                   Tailwind config (5 KB)        ║
║  vite.config.js                       Vite config                   ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════════════╗
║ BROWSER SUPPORT                                                       ║
╠═══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  ✅ Chrome/Edge    Latest 2 versions                                 ║
║  ✅ Firefox        Latest 2 versions                                 ║
║  ✅ Safari         Latest 2 versions                                 ║
║  ✅ iOS Safari     12+                                               ║
║  ✅ Chrome Android Latest                                            ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────────────┐
│                         DESIGN SYSTEM v1.0                              │
│                    © 2024 The Last Messenger                            │
│              Netflix-Grade RTL-Ready Admin Dashboard                    │
└─────────────────────────────────────────────────────────────────────────┘
```
