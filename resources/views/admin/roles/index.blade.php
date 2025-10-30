@extends('layouts.admin')

@section('title', 'إدارة الأدوار والصلاحيات')

@section('content')
<div x-data="rolesTable()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2 transition-colors duration-300"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    إدارة الأدوار والصلاحيات
                </h1>
                <p class="transition-colors duration-300"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    إدارة أدوار المستخدمين وصلاحياتهم في النظام
                </p>
            </div>
            <a href="/su/portal/v1/admin/roles/create"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 group">
                <i data-lucide="plus" class="w-5 h-5 ml-2 group-hover:rotate-90 transition-transform duration-300"></i>
                إضافة دور جديد
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Roles -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الأدوار</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="pagination.total || 0"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="shield" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>

        <!-- Total Permissions -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الصلاحيات</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="totalPermissions"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="key" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>

        <!-- System Roles -->
        <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl"
             :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm mb-1 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">أدوار النظام</p>
                    <p class="text-3xl font-bold transition-colors duration-300"
                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                       x-text="systemRoles"></p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="lock" class="w-7 h-7 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="rounded-2xl p-6 mb-6 transition-colors duration-300"
         :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
        <div class="relative">
            <i data-lucide="search"
               class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2 transition-colors duration-300"
               :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
            <input type="text"
                   x-model="search"
                   @input.debounce.500ms="loadRoles(1)"
                   placeholder="ابحث عن دور..."
                   class="w-full pr-12 pl-4 py-3 rounded-xl border transition-all duration-300 focus:ring-2 focus:ring-[#64CCC5] focus:outline-none"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400'">
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="col-span-full">
                <div class="rounded-2xl p-12 text-center transition-colors duration-300"
                     :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-t-[#64CCC5] transition-colors duration-300"
                         :class="darkMode ? 'border-gray-700' : 'border-gray-200'"></div>
                    <p class="mt-4 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!loading && roles.length === 0">
            <div class="col-span-full">
                <div class="rounded-2xl p-12 text-center transition-colors duration-300"
                     :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
                    <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center transition-colors duration-300"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <i data-lucide="shield" class="w-10 h-10 transition-colors duration-300"
                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 transition-colors duration-300"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">لا توجد أدوار</h3>
                    <p class="transition-colors duration-300"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لم يتم العثور على أي أدوار</p>
                </div>
            </div>
        </template>

        <!-- Role Cards -->
        <template x-for="role in roles" :key="role.id">
            <div class="rounded-2xl p-6 transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                 :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
                <!-- Role Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg">
                            <i data-lucide="shield" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg transition-colors duration-300"
                                :class="darkMode ? 'text-white' : 'text-gray-900'"
                                x-text="role.name"></h3>
                            <p class="text-sm transition-colors duration-300"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <span x-text="role.permissions?.length || 0"></span> صلاحية
                            </p>
                        </div>
                    </div>

                    <!-- System Role Badge -->
                    <span x-show="isSystemRole(role.name)"
                          class="px-2 py-1 rounded-lg text-xs font-semibold transition-colors duration-300"
                          :class="darkMode ? 'bg-purple-500/20 text-purple-300' : 'bg-purple-100 text-purple-700'">
                        نظام
                    </span>
                </div>

                <!-- Permissions List -->
                <div class="mb-4">
                    <p class="text-sm font-semibold mb-2 transition-colors duration-300"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الصلاحيات:</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-if="role.permissions && role.permissions.length > 0">
                            <template x-for="(permission, index) in role.permissions.slice(0, 4)" :key="permission.id">
                                <span class="px-2 py-1 rounded-lg text-xs transition-colors duration-300"
                                      :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-700'"
                                      x-text="permission.name"></span>
                            </template>
                        </template>
                        <template x-if="!role.permissions || role.permissions.length === 0">
                            <span class="text-xs transition-colors duration-300"
                                  :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد صلاحيات</span>
                        </template>
                        <span x-show="role.permissions && role.permissions.length > 4"
                              class="px-2 py-1 rounded-lg text-xs transition-colors duration-300"
                              :class="darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            +<span x-text="role.permissions.length - 4"></span>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 pt-4 border-t transition-colors duration-300"
                     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <a :href="`/su/portal/v1/admin/roles/${role.id}/edit`"
                       class="flex-1 px-4 py-2 rounded-lg text-center transition-all duration-300 hover:scale-105"
                       :class="darkMode ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'">
                        <i data-lucide="edit" class="w-4 h-4 inline ml-1"></i>
                        تعديل
                    </a>
                    <button @click="deleteRole(role.id, role.name)"
                            :disabled="isSystemRole(role.name)"
                            class="flex-1 px-4 py-2 rounded-lg text-center transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            :class="darkMode ? 'bg-red-600/20 hover:bg-red-600/30 text-red-400' : 'bg-red-50 hover:bg-red-100 text-red-600'">
                        <i data-lucide="trash-2" class="w-4 h-4 inline ml-1"></i>
                        حذف
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && roles.length > 0"
         class="mt-8 rounded-2xl p-6 transition-colors duration-300"
         :class="darkMode ? 'glass-dark' : 'bg-white shadow-soft'">
        <div class="flex items-center justify-between">
            <div class="text-sm transition-colors duration-300"
                 :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                عرض <span x-text="pagination.from || 0"></span> إلى <span x-text="pagination.to || 0"></span>
                من <span x-text="pagination.total || 0"></span> دور
            </div>
            <div class="flex gap-2">
                <button @click="loadRoles(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    السابق
                </button>
                <button @click="loadRoles(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-4 py-2 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    التالي
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function rolesTable() {
    return {
        roles: [],
        search: '',
        loading: true,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },
        totalPermissions: 0,
        systemRoles: 6, // super_admin, admin, editor, researcher, user, guest
        perPage: 15,

        init() {
            this.loadRoles();
            this.loadPermissions();
            this.$nextTick(() => lucide.createIcons());
        },

        async loadRoles(page = 1) {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.perPage,
                    search: this.search
                });

                const response = await fetch(`/su/portal/v1/admin/roles?${params}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.roles = data.data;
                    this.pagination = data.meta;

                    this.$nextTick(() => lucide.createIcons());
                }
            } catch (error) {
                console.error('Error loading roles:', error);
                this.showToast('حدث خطأ أثناء تحميل الأدوار', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadPermissions() {
            try {
                const response = await fetch('/su/portal/v1/admin/permissions', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.totalPermissions = data.data.length;
                }
            } catch (error) {
                console.error('Error loading permissions:', error);
            }
        },

        isSystemRole(name) {
            const systemRoles = ['super_admin', 'admin', 'editor', 'researcher', 'user', 'guest'];
            return systemRoles.includes(name);
        },

        async deleteRole(id, name) {
            if (this.isSystemRole(name)) {
                this.showToast('لا يمكن حذف أدوار النظام', 'error');
                return;
            }

            if (!confirm(`هل أنت متأكد من حذف دور "${name}"؟`)) {
                return;
            }

            try {
                const response = await fetch(`/su/portal/v1/admin/roles/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('تم حذف الدور بنجاح', 'success');
                    this.loadRoles(this.pagination.current_page);
                } else {
                    this.showToast(data.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            } catch (error) {
                console.error('Error deleting role:', error);
                this.showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 left-4 px-6 py-3 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                'bg-blue-600'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    };
}
</script>
@endsection
