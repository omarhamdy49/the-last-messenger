<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تعديل مستخدم - لوحة التحكم</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }
        .gradient-primary { background: linear-gradient(135deg, #0056b3 0%, #218838 100%); }
    </style>
</head>
<body class="transition-colors duration-300"
      :class="darkMode ? 'bg-gray-950' : 'bg-gray-50'"
      x-data="{
          darkMode: localStorage.getItem('darkMode') !== 'false',
          sidebarOpen: true
      }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">

    <div class="flex h-screen overflow-hidden">
        @include('admin.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            @include('admin.partials.header')

            <main class="flex-1 overflow-y-auto p-6"
                  x-data="editUserForm({{ $id }})"
                  x-init="init()">

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.users.index') }}"
                           class="p-2 rounded-lg transition-all duration-300 hover:scale-110"
                           :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'">
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold transition-colors"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">
                                تعديل مستخدم
                            </h1>
                            <p class="text-sm mt-1 transition-colors"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                تحديث معلومات المستخدم
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="loadingData" class="text-center py-12">
                    <div class="inline-block w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري تحميل البيانات...</p>
                </div>

                <!-- Form -->
                <form x-show="!loadingData" @submit.prevent="submit" class="max-w-4xl">
                    <!-- Basic Information -->
                    <div class="mb-6 p-6 rounded-2xl transition-colors shadow-lg"
                         :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white border border-gray-200'">
                        <h2 class="text-xl font-bold mb-6 transition-colors"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            المعلومات الأساسية
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-semibold mb-2 transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    الاسم <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="form.name"
                                    required
                                    class="w-full px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 border'"
                                    placeholder="أدخل اسم المستخدم">
                                <p x-show="errors.name" class="text-red-500 text-xs mt-1" x-text="errors.name"></p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-semibold mb-2 transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    البريد الإلكتروني <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    x-model="form.email"
                                    required
                                    class="w-full px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 border'"
                                    placeholder="example@email.com">
                                <p x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                            </div>

                            <!-- Password (Optional) -->
                            <div>
                                <label class="block text-sm font-semibold mb-2 transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    كلمة المرور الجديدة
                                </label>
                                <div class="relative">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        x-model="form.password"
                                        class="w-full px-4 py-2 pr-10 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 border'"
                                        placeholder="اتركه فارغاً للاحتفاظ بالحالي">
                                    <button type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute left-3 top-1/2 -translate-y-1/2 transition-colors"
                                            :class="darkMode ? 'text-gray-400 hover:text-gray-300' : 'text-gray-500 hover:text-gray-700'">
                                        <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                        <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <p class="text-xs mt-1 transition-colors"
                                   :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                                    اترك فارغاً إذا كنت لا تريد تغيير كلمة المرور
                                </p>
                                <p x-show="errors.password" class="text-red-500 text-xs mt-1" x-text="errors.password"></p>
                            </div>

                            <!-- Password Confirmation -->
                            <div x-show="form.password">
                                <label class="block text-sm font-semibold mb-2 transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    تأكيد كلمة المرور
                                </label>
                                <input
                                    type="password"
                                    x-model="form.password_confirmation"
                                    class="w-full px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 border'"
                                    placeholder="أعد إدخال كلمة المرور">
                            </div>
                        </div>
                    </div>

                    <!-- Roles & Permissions -->
                    <div class="mb-6 p-6 rounded-2xl transition-colors shadow-lg"
                         :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white border border-gray-200'">
                        <h2 class="text-xl font-bold mb-6 transition-colors"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            الأدوار والصلاحيات
                        </h2>

                        <div>
                            <label class="block text-sm font-semibold mb-3 transition-colors"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                اختر الأدوار <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="role in availableRoles" :key="role.value">
                                    <label class="flex items-center gap-3 p-4 rounded-xl cursor-pointer transition-all duration-300 border"
                                           :class="form.roles.includes(role.value) ?
                                               'gradient-primary text-white border-transparent shadow-lg' :
                                               (darkMode ? 'bg-gray-700/50 border-gray-600 hover:bg-gray-700 text-gray-300' : 'bg-gray-50 border-gray-300 hover:bg-gray-100 text-gray-700')">
                                        <input
                                            type="checkbox"
                                            :value="role.value"
                                            x-model="form.roles"
                                            class="w-5 h-5 rounded transition-colors">
                                        <div>
                                            <p class="font-semibold" x-text="role.label"></p>
                                            <p class="text-xs opacity-80" x-text="role.description"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <p x-show="errors.roles" class="text-red-500 text-xs mt-2" x-text="errors.roles"></p>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="mb-6 p-6 rounded-2xl transition-colors shadow-lg"
                         :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white border border-gray-200'">
                        <h2 class="text-xl font-bold mb-6 transition-colors"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            إعدادات إضافية
                        </h2>

                        <div class="space-y-4">
                            <!-- Email Verified -->
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="form.email_verified"
                                    class="w-5 h-5 rounded transition-colors"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'">
                                <div>
                                    <p class="font-semibold transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        البريد الإلكتروني محقق
                                    </p>
                                    <p class="text-xs transition-colors"
                                       :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                                        حالة تحقق البريد الإلكتروني
                                    </p>
                                </div>
                            </label>

                            <!-- Language -->
                            <div>
                                <label class="block text-sm font-semibold mb-2 transition-colors"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    اللغة المفضلة
                                </label>
                                <select
                                    x-model="form.language"
                                    class="w-full md:w-1/2 px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-300 text-gray-900 border'">
                                    <option value="ar">العربية</option>
                                    <option value="en">English</option>
                                    <option value="fr">Français</option>
                                    <option value="tr">Türkçe</option>
                                    <option value="ur">اردو</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="px-6 py-3 gradient-primary text-white rounded-xl font-semibold hover:shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <i data-lucide="save" class="w-5 h-5"></i>
                            <span x-text="saving ? 'جاري الحفظ...' : 'حفظ التغييرات'"></span>
                        </button>

                        <button
                            type="button"
                            @click="confirmDelete()"
                            :disabled="saving"
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                            <span>حذف المستخدم</span>
                        </button>

                        <a href="{{ route('admin.users.index') }}"
                           class="px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105 flex items-center gap-2"
                           :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            <i data-lucide="x" class="w-5 h-5"></i>
                            <span>إلغاء</span>
                        </a>
                    </div>
                </form>

                <!-- Toast Notification -->
                <div x-show="toast.show"
                     x-transition
                     @click="toast.show = false"
                     class="fixed bottom-6 left-6 px-6 py-4 rounded-xl shadow-2xl cursor-pointer transition-colors z-50"
                     :class="toast.type === 'success' ?
                         (darkMode ? 'bg-green-500/20 border border-green-500/50 text-green-300' : 'bg-green-100 border border-green-500 text-green-800') :
                         (darkMode ? 'bg-red-500/20 border border-red-500/50 text-red-300' : 'bg-red-100 border border-red-500 text-red-800')">
                    <div class="flex items-center gap-3">
                        <i :data-lucide="toast.type === 'success' ? 'check-circle' : 'alert-circle'" class="w-5 h-5"></i>
                        <p class="font-semibold" x-text="toast.message"></p>
                    </div>
                </div>
            </main>

            @include('admin.partials.footer')
        </div>
    </div>

    <script>
        function editUserForm(userId) {
            return {
                id: userId,
                form: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    roles: [],
                    email_verified: false,
                    language: 'ar'
                },
                availableRoles: [
                    { value: 'super_admin', label: 'مدير عام', description: 'صلاحيات كاملة على النظام' },
                    { value: 'admin', label: 'مدير', description: 'إدارة المحتوى والمستخدمين' },
                    { value: 'editor', label: 'محرر', description: 'إنشاء وتعديل المحتوى' },
                    { value: 'researcher', label: 'باحث', description: 'الوصول للمحتوى البحثي' },
                    { value: 'user', label: 'مستخدم', description: 'مستخدم عادي' },
                    { value: 'guest', label: 'ضيف', description: 'وصول محدود' }
                ],
                showPassword: false,
                loadingData: true,
                saving: false,
                errors: {},
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                async init() {
                    await this.loadUser();
                    lucide.createIcons();
                },

                async loadUser() {
                    this.loadingData = true;
                    try {
                        const response = await fetch(`/su/portal/v1/admin/users/${this.id}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            const user = data.data;
                            this.form = {
                                name: user.name || '',
                                email: user.email || '',
                                password: '',
                                password_confirmation: '',
                                roles: user.roles?.map(r => r.name) || [],
                                email_verified: !!user.email_verified_at,
                                language: user.language || 'ar'
                            };
                        } else {
                            this.showToast('فشل تحميل بيانات المستخدم', 'error');
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء تحميل البيانات', 'error');
                    } finally {
                        this.loadingData = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                async submit() {
                    this.saving = true;
                    this.errors = {};

                    try {
                        const formData = {
                            name: this.form.name,
                            email: this.form.email,
                            roles: this.form.roles,
                            language: this.form.language,
                            email_verified_at: this.form.email_verified ? new Date().toISOString() : null
                        };

                        if (this.form.password) {
                            formData.password = this.form.password;
                            formData.password_confirmation = this.form.password_confirmation;
                        }

                        const response = await fetch(`/su/portal/v1/admin/users/${this.id}`, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.showToast('تم تحديث المستخدم بنجاح', 'success');
                            setTimeout(() => {
                                window.location.href = '/su/portal/v1/admin/users';
                            }, 1500);
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            }
                            this.showToast(data.message || 'فشل تحديث المستخدم', 'error');
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء الحفظ', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                async confirmDelete() {
                    if (!confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.')) return;

                    this.saving = true;
                    try {
                        const response = await fetch(`/su/portal/v1/admin/users/${this.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.showToast('تم حذف المستخدم بنجاح', 'success');
                            setTimeout(() => {
                                window.location.href = '/su/portal/v1/admin/users';
                            }, 1500);
                        } else {
                            this.showToast(data.message || 'فشل حذف المستخدم', 'error');
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء الحذف', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 3000);
                    this.$nextTick(() => lucide.createIcons());
                }
            };
        }
    </script>
</body>
</html>
