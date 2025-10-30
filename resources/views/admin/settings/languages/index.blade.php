<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إدارة اللغات - لوحة التحكم</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-light {
            background: rgba(0, 0, 0, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .gradient-primary {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        }

        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 86, 179, 0.2);
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #f3f4f6;
        }

        .sortable-drag {
            opacity: 1;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .drag-handle {
            cursor: move;
        }
    </style>
</head>
<body x-data="languageSettings()" :class="darkMode ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-900'" class="min-h-screen">

    <!-- Header -->
    <header :class="darkMode ? 'glass' : 'glass-light'" class="sticky top-0 z-50 border-b" :style="darkMode ? 'border-color: rgba(255,255,255,0.1)' : 'border-color: rgba(0,0,0,0.05)'">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/su/portal/v1/admin/dashboard" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        <span class="font-bold">العودة للوحة التحكم</span>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <button @click="toggleDarkMode"
                            class="p-2 rounded-lg transition-all hover:scale-110"
                            :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-black/5'">
                        <i x-show="!darkMode" data-lucide="moon" class="w-5 h-5"></i>
                        <i x-show="darkMode" data-lucide="sun" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="gradient-primary p-3 rounded-xl">
                    <i data-lucide="languages" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">إدارة اللغات</h1>
                    <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-sm mt-1">
                        تحكم في اللغات المتاحة في النظام وترتيبها
                    </p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div x-show="message"
             x-transition
             @click="message = ''"
             :class="messageType === 'success' ? 'bg-green-500/10 border-green-500 text-green-500' : 'bg-red-500/10 border-red-500 text-red-500'"
             class="mb-6 p-4 rounded-lg border cursor-pointer">
            <div class="flex items-center gap-2">
                <i :data-lucide="messageType === 'success' ? 'check-circle' : 'alert-circle'" class="w-5 h-5"></i>
                <span x-text="message"></span>
            </div>
        </div>

        <!-- Add Language Section -->
        <div :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
             class="rounded-xl border p-6 mb-6 hover-lift">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                <span>إضافة لغة جديدة</span>
            </h2>

            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block mb-2 font-semibold">اختر اللغة</label>
                    <select x-model="newLanguageCode"
                            :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-50 border-gray-300'"
                            class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- اختر لغة --</option>
                        <template x-for="lang in availableToAdd" :key="lang.code">
                            <option :value="lang.code" x-text="`${lang.flag} ${lang.native_name} (${lang.name})`"></option>
                        </template>
                    </select>
                </div>

                <button @click="addLanguage"
                        :disabled="!newLanguageCode"
                        class="gradient-primary text-white px-6 py-2 rounded-lg font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-lg">
                    <div class="flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>إضافة</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Languages List -->
        <div :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
             class="rounded-xl border overflow-hidden">

            <!-- Table Header -->
            <div :class="darkMode ? 'bg-gray-900/50' : 'bg-gray-50'" class="px-6 py-4 border-b"
                 :style="darkMode ? 'border-color: rgba(255,255,255,0.1)' : 'border-color: rgba(0,0,0,0.05)'">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i data-lucide="list" class="w-5 h-5"></i>
                    <span>اللغات المتاحة</span>
                    <span :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"
                          class="text-sm px-3 py-1 rounded-full"
                          x-text="languages.length"></span>
                </h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-900/30' : 'bg-gray-100/50'">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">
                                <i data-lucide="grip-vertical" class="w-4 h-4 inline"></i>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">العلم</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الكود</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الاسم</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الاسم المحلي</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الاتجاه</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الحالة</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">افتراضي</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-languages" :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'" class="divide-y">
                        <template x-for="(lang, index) in languages" :key="lang.code">
                            <tr :data-code="lang.code" class="hover:bg-opacity-50 transition-colors"
                                :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'">
                                <!-- Drag Handle -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="drag-handle text-gray-400 hover:text-gray-600">
                                        <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                                    </div>
                                </td>

                                <!-- Flag -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-2xl" x-text="lang.flag"></span>
                                </td>

                                <!-- Code -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-sm" x-text="lang.code.toUpperCase()"></span>
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold" x-text="lang.name"></span>
                                </td>

                                <!-- Native Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold" x-text="lang.native_name"></span>
                                </td>

                                <!-- Direction -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="lang.direction === 'rtl' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500'"
                                          class="px-3 py-1 rounded-full text-xs font-bold">
                                        <span x-text="lang.direction === 'rtl' ? 'RTL' : 'LTR'"></span>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="toggleLanguage(lang.code)"
                                            :class="lang.active ? 'bg-green-500/10 text-green-500 hover:bg-green-500/20' : 'bg-gray-500/10 text-gray-500 hover:bg-gray-500/20'"
                                            class="px-3 py-1 rounded-full text-xs font-bold transition-all">
                                        <span x-text="lang.active ? 'نشط' : 'غير نشط'"></span>
                                    </button>
                                </td>

                                <!-- Default -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-show="lang.code === defaultLanguage"
                                         class="gradient-primary text-white px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                        <span>افتراضي</span>
                                    </div>
                                    <button x-show="lang.code !== defaultLanguage && lang.active"
                                            @click="setDefaultLanguage(lang.code)"
                                            :class="darkMode ? 'bg-gray-700 hover:bg-gray-600' : 'bg-gray-200 hover:bg-gray-300'"
                                            class="px-3 py-1 rounded-full text-xs font-bold transition-all">
                                        جعله افتراضي
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <button x-show="lang.code !== defaultLanguage"
                                                @click="deleteLanguage(lang.code)"
                                                class="p-2 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-all">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        function languageSettings() {
            return {
                darkMode: localStorage.getItem('darkMode') !== 'false',
                languages: @json(array_values($languages)),
                defaultLanguage: @json($defaultLanguage),
                newLanguageCode: '',
                message: '',
                messageType: 'success',

                availableLanguages: [
                    { code: 'ar', name: 'Arabic', native_name: 'العربية', direction: 'rtl', flag: '🇸🇦' },
                    { code: 'en', name: 'English', native_name: 'English', direction: 'ltr', flag: '🇬🇧' },
                    { code: 'fr', name: 'French', native_name: 'Français', direction: 'ltr', flag: '🇫🇷' },
                    { code: 'tr', name: 'Turkish', native_name: 'Türkçe', direction: 'ltr', flag: '🇹🇷' },
                    { code: 'ur', name: 'Urdu', native_name: 'اردو', direction: 'rtl', flag: '🇵🇰' }
                ],

                get availableToAdd() {
                    const activeCodes = this.languages.map(l => l.code);
                    return this.availableLanguages.filter(l => !activeCodes.includes(l.code));
                },

                init() {
                    // Initialize Sortable
                    const el = document.getElementById('sortable-languages');
                    Sortable.create(el, {
                        animation: 150,
                        handle: '.drag-handle',
                        onEnd: (evt) => {
                            this.reorderLanguages();
                        }
                    });

                    // Initialize icons
                    this.$nextTick(() => lucide.createIcons());
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                    this.$nextTick(() => lucide.createIcons());
                },

                async addLanguage() {
                    if (!this.newLanguageCode) return;

                    try {
                        const response = await fetch('/su/portal/v1/admin/settings/languages', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ code: this.newLanguageCode })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.languages.push(data.data);
                            this.newLanguageCode = '';
                            this.showMessage(data.message, 'success');
                            this.$nextTick(() => lucide.createIcons());
                        } else {
                            this.showMessage(data.message, 'error');
                        }
                    } catch (error) {
                        this.showMessage('حدث خطأ أثناء إضافة اللغة', 'error');
                    }
                },

                async toggleLanguage(code) {
                    try {
                        const response = await fetch(`/su/portal/v1/admin/settings/languages/${code}/toggle`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            const index = this.languages.findIndex(l => l.code === code);
                            if (index !== -1) {
                                this.languages[index] = data.data;
                            }
                            this.showMessage(data.message, 'success');
                        } else {
                            this.showMessage(data.message, 'error');
                        }
                    } catch (error) {
                        this.showMessage('حدث خطأ أثناء تحديث حالة اللغة', 'error');
                    }
                },

                async setDefaultLanguage(code) {
                    if (!confirm('هل أنت متأكد من تعيين هذه اللغة كافتراضية؟')) return;

                    try {
                        const response = await fetch(`/su/portal/v1/admin/settings/languages/${code}/set-default`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.defaultLanguage = code;
                            this.showMessage(data.message, 'success');
                        } else {
                            this.showMessage(data.message, 'error');
                        }
                    } catch (error) {
                        this.showMessage('حدث خطأ أثناء تعيين اللغة الافتراضية', 'error');
                    }
                },

                async deleteLanguage(code) {
                    if (!confirm('هل أنت متأكد من حذف هذه اللغة؟')) return;

                    try {
                        const response = await fetch(`/su/portal/v1/admin/settings/languages/${code}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.languages = this.languages.filter(l => l.code !== code);
                            this.showMessage(data.message, 'success');
                            this.$nextTick(() => lucide.createIcons());
                        } else {
                            this.showMessage(data.message, 'error');
                        }
                    } catch (error) {
                        this.showMessage('حدث خطأ أثناء حذف اللغة', 'error');
                    }
                },

                async reorderLanguages() {
                    const rows = document.querySelectorAll('#sortable-languages tr');
                    const orderedCodes = Array.from(rows).map(row => row.dataset.code);

                    try {
                        const response = await fetch('/su/portal/v1/admin/settings/languages/reorder', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ languages: orderedCodes })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.languages = Object.values(data.data);
                            this.showMessage(data.message, 'success');
                        }
                    } catch (error) {
                        this.showMessage('حدث خطأ أثناء إعادة الترتيب', 'error');
                    }
                },

                showMessage(message, type) {
                    this.message = message;
                    this.messageType = type;
                    setTimeout(() => {
                        this.message = '';
                    }, 5000);
                }
            }
        }

        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
