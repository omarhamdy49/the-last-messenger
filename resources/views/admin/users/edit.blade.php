@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.users.index') }}" class="hover:text-accent transition-colors">المستخدمون</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل مستخدم
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.users.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    user: null,
    loading: true,
    showPassword: false,
    selectedRoles: [],
    allRoles: [],
    errors: {},

    async init() {
        try {
            const [userRes, rolesRes] = await Promise.all([
                fetch('{{ route('admin.users.show', $id) }}', { headers: { 'Accept': 'application/json' } }),
                fetch('{{ route('admin.roles.index') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            ]);

            const userData = await userRes.json();
            const rolesData = await rolesRes.json();

            if (userData.success !== false) {
                this.user = userData.data || userData;
                this.selectedRoles = (this.user.roles || []).map(r => r.name || r);
            }

            this.allRoles = rolesData.data || [];
        } catch (e) {
            console.error('Failed to load user data:', e);
        } finally {
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    async submitForm() {
        this.errors = {};
        const formData = {
            name: this.user.name,
            email: this.user.email,
            roles: this.selectedRoles,
            _method: 'PUT'
        };

        if (this.$refs.password.value) {
            formData.password = this.$refs.password.value;
            formData.password_confirmation = this.$refs.passwordConfirmation.value;
        }

        try {
            const res = await fetch('{{ route('admin.users.update', $id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            });

            const data = await res.json();

            if (res.ok) {
                window.location.href = '{{ route('admin.users.index') }}';
            } else {
                this.errors = data.errors || {};
            }
        } catch (e) {
            console.error('Submit failed:', e);
        }
    },

    isRoleSelected(roleName) {
        return this.selectedRoles.includes(roleName);
    },

    toggleRole(roleName) {
        if (this.isRoleSelected(roleName)) {
            this.selectedRoles = this.selectedRoles.filter(r => r !== roleName);
        } else {
            this.selectedRoles.push(roleName);
        }
    }
}">

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
            <div class="w-12 h-12 border-4 border-accent border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري تحميل البيانات...</p>
        </div>
    </div>

    <!-- Form Content -->
    <div x-show="!loading" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">

                <!-- User Info -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="user" class="w-5 h-5 text-accent"></i>
                        بيانات المستخدم
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                الاسم <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="user.name"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   placeholder="أدخل اسم المستخدم..."
                                   required>
                            <template x-if="errors.name">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                البريد الإلكتروني <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   x-model="user.email"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   dir="ltr"
                                   placeholder="user@example.com"
                                   required>
                            <template x-if="errors.email">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.email[0]"></p>
                            </template>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                كلمة المرور الجديدة
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       x-ref="password"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                       dir="ltr"
                                       placeholder="اتركه فارغاً لعدم التغيير">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute end-3 top-1/2 -translate-y-1/2"
                                        :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                    <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                اتركه فارغاً إذا لم ترد تغيير كلمة المرور
                            </p>
                            <template x-if="errors.password">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.password[0]"></p>
                            </template>
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                تأكيد كلمة المرور
                            </label>
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-ref="passwordConfirmation"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   dir="ltr"
                                   placeholder="تأكيد كلمة المرور...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Roles -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="shield" class="w-5 h-5 text-accent"></i>
                        الأدوار
                    </h3>

                    <div class="space-y-3">
                        <template x-for="role in allRoles" :key="role.id">
                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                                   :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                                <input type="checkbox"
                                       :checked="isRoleSelected(role.name)"
                                       @change="toggleRole(role.name)"
                                       class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="role.name"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Info -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                        معلومات
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ التسجيل</span>
                            <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="user?.created_at ? new Date(user.created_at).toLocaleDateString('ar-EG') : '-'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">حالة التفعيل</span>
                            <span class="font-medium" :class="user?.email_verified_at ? 'text-green-500' : 'text-red-500'" x-text="user?.email_verified_at ? 'مفعّل' : 'غير مفعّل'"></span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <button @click="submitForm()"
                            type="button"
                            class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        {{ __('admin.save') }}
                    </button>

                    <a href="{{ route('admin.users.index') }}"
                       class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
