<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>المستخدمون - لوحة التحكم</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }

        .gradient-primary { background: linear-gradient(135deg, #0056b3 0%, #218838 100%); }
        .gradient-text { background: linear-gradient(135deg, #0056b3 0%, #218838 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(100, 204, 197, 0.3); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(100, 204, 197, 0.5); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }

        .sidebar-item.active {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
        }
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

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            @include('admin.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6"
                  x-data="usersTable()"
                  x-init="init()">

                <!-- Header Actions -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold transition-colors"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            إدارة المستخدمين
                        </h1>
                        <p class="text-sm mt-1 transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            عرض وإدارة جميع مستخدمي النظام
                        </p>
                    </div>

                    <a href="{{ route('admin.users.create') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 gradient-primary text-white rounded-xl hover:shadow-lg transition-all duration-300 hover:scale-105 font-semibold">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        <span>إضافة مستخدم جديد</span>
                    </a>
                </div>

                <!-- Filters & Search -->
                <div class="mb-6 p-6 rounded-2xl transition-colors shadow-lg"
                     :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white border border-gray-200'">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2 transition-colors"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                البحث
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    x-model="search"
                                    @input.debounce.500ms="loadUsers()"
                                    placeholder="البحث بالاسم أو البريد الإلكتروني..."
                                    class="w-full px-4 py-2 pr-10 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 border'"
                                >
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                            </div>
                        </div>

                        <!-- Verification Filter -->
                        <div>
                            <label class="block text-sm font-semibold mb-2 transition-colors"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                حالة التحقق
                            </label>
                            <select
                                x-model="filters.verified"
                                @change="loadUsers()"
                                class="w-full px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-300 text-gray-900 border'">
                                <option value="">الكل</option>
                                <option value="true">محقق</option>
                                <option value="false">غير محقق</option>
                            </select>
                        </div>

                        <!-- Per Page -->
                        <div>
                            <label class="block text-sm font-semibold mb-2 transition-colors"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                عدد النتائج
                            </label>
                            <select
                                x-model="perPage"
                                @change="loadUsers()"
                                class="w-full px-4 py-2 rounded-xl transition-all duration-300 focus:ring-2 focus:ring-blue-500/50"
                                :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-300 text-gray-900 border'">
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div x-show="selected.length > 0"
                     x-transition
                     class="mb-6 p-4 rounded-xl transition-colors flex items-center justify-between"
                     :class="darkMode ? 'bg-blue-500/10 border border-blue-500/30' : 'bg-blue-50 border border-blue-200'">
                    <span class="font-semibold transition-colors"
                          :class="darkMode ? 'text-blue-300' : 'text-blue-700'">
                        تم تحديد <span x-text="selected.length"></span> مستخدم
                    </span>
                    <button
                        @click="bulkDelete()"
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all duration-300 flex items-center gap-2 font-semibold">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>حذف المحدد</span>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="text-center py-12">
                    <div class="inline-block w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
                </div>

                <!-- Users Table -->
                <div x-show="!loading" class="rounded-2xl overflow-hidden shadow-lg transition-colors"
                     :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white border border-gray-200'">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="transition-colors"
                                    :class="darkMode ? 'bg-gray-700/50 border-b border-gray-700' : 'bg-gray-50 border-b border-gray-200'">
                                    <th class="px-6 py-4 text-right">
                                        <input
                                            type="checkbox"
                                            @change="toggleAll($event.target.checked)"
                                            :checked="selected.length === users.length && users.length > 0"
                                            class="w-5 h-5 rounded transition-colors"
                                            :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'">
                                    </th>
                                    <th class="px-6 py-4 text-right text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المستخدم</th>
                                    <th class="px-6 py-4 text-right text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">البريد الإلكتروني</th>
                                    <th class="px-6 py-4 text-right text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الأدوار</th>
                                    <th class="px-6 py-4 text-right text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التحقق</th>
                                    <th class="px-6 py-4 text-right text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">تاريخ الإنشاء</th>
                                    <th class="px-6 py-4 text-center text-sm font-bold transition-colors"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="user in users" :key="user.id">
                                    <tr class="transition-colors border-b"
                                        :class="darkMode ? 'border-gray-700 hover:bg-gray-700/30' : 'border-gray-200 hover:bg-gray-50'">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="user.id"
                                                x-model="selected"
                                                class="w-5 h-5 rounded transition-colors"
                                                :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full gradient-primary flex items-center justify-center text-white font-bold">
                                                    <span x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold transition-colors"
                                                       :class="darkMode ? 'text-white' : 'text-gray-900'"
                                                       x-text="user.name"></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 transition-colors"
                                            :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                            x-text="user.email"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="role in user.roles" :key="role.id">
                                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                          :class="getRoleBadgeClass(role.name)"
                                                          x-text="getRoleLabel(role.name)"></span>
                                                </template>
                                                <span x-show="user.roles.length === 0"
                                                      class="px-3 py-1 rounded-full text-xs font-semibold transition-colors"
                                                      :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                                                    لا يوجد
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span x-show="user.email_verified_at"
                                                  class="px-3 py-1 rounded-full text-xs font-semibold transition-colors"
                                                  :class="darkMode ? 'bg-green-500/20 text-green-400' : 'bg-green-100 text-green-700'">
                                                محقق
                                            </span>
                                            <span x-show="!user.email_verified_at"
                                                  class="px-3 py-1 rounded-full text-xs font-semibold transition-colors"
                                                  :class="darkMode ? 'bg-red-500/20 text-red-400' : 'bg-red-100 text-red-700'">
                                                غير محقق
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm transition-colors"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                            x-text="formatDate(user.created_at)"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <a :href="`{{ route('admin.users.index') }}/${user.id}/edit`"
                                                   class="p-2 rounded-lg transition-all duration-300 hover:scale-110"
                                                   :class="darkMode ? 'bg-blue-500/20 text-blue-400 hover:bg-blue-500/30' : 'bg-blue-100 text-blue-600 hover:bg-blue-200'"
                                                   title="تعديل">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </a>
                                                <button
                                                    @click="deleteUser(user.id)"
                                                    class="p-2 rounded-lg transition-all duration-300 hover:scale-110"
                                                    :class="darkMode ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30' : 'bg-red-100 text-red-600 hover:bg-red-200'"
                                                    title="حذف">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr x-show="users.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <i data-lucide="users" class="w-16 h-16 transition-colors"
                                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                            <p class="text-lg font-semibold transition-colors"
                                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                                لا توجد مستخدمين
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div x-show="pagination.last_page > 1"
                         class="px-6 py-4 transition-colors flex items-center justify-between border-t"
                         :class="darkMode ? 'bg-gray-700/30 border-gray-700' : 'bg-gray-50 border-gray-200'">
                        <div class="text-sm transition-colors"
                             :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            عرض <span x-text="pagination.from || 0"></span> إلى <span x-text="pagination.to || 0"></span>
                            من أصل <span x-text="pagination.total || 0"></span> مستخدم
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="loadUsers(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                                السابق
                            </button>

                            <template x-for="page in getPageNumbers()" :key="page">
                                <button
                                    @click="loadUsers(page)"
                                    class="px-4 py-2 rounded-lg font-semibold transition-all duration-300"
                                    :class="page === pagination.current_page ?
                                        'gradient-primary text-white shadow-lg' :
                                        (darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300')"
                                    x-text="page"></button>
                            </template>

                            <button
                                @click="loadUsers(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                                التالي
                            </button>
                        </div>
                    </div>
                </div>

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
        function usersTable() {
            return {
                users: [],
                loading: true,
                search: '',
                filters: {
                    verified: ''
                },
                perPage: 15,
                selected: [],
                pagination: {},
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                async init() {
                    await this.loadUsers();
                    lucide.createIcons();
                },

                async loadUsers(page = 1) {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            page: page,
                            per_page: this.perPage,
                            search: this.search,
                            verified: this.filters.verified
                        });

                        const response = await fetch(`/su/portal/v1/admin/users?${params}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.users = data.data;
                            this.pagination = data.meta;
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء تحميل البيانات', 'error');
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                async deleteUser(id) {
                    if (!confirm('هل أنت متأكد من حذف هذا المستخدم؟')) return;

                    try {
                        const response = await fetch(`/su/portal/v1/admin/users/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.showToast('تم حذف المستخدم بنجاح', 'success');
                            await this.loadUsers(this.pagination.current_page);
                        } else {
                            this.showToast(data.message || 'فشل حذف المستخدم', 'error');
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء الحذف', 'error');
                    }
                },

                async bulkDelete() {
                    if (!confirm(`هل أنت متأكد من حذف ${this.selected.length} مستخدم؟`)) return;

                    try {
                        const response = await fetch('/su/portal/v1/admin/users/bulk-delete', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ ids: this.selected })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.showToast(`تم حذف ${data.data.deleted} مستخدم بنجاح`, 'success');
                            this.selected = [];
                            await this.loadUsers(this.pagination.current_page);
                        } else {
                            this.showToast(data.message || 'فشل الحذف الجماعي', 'error');
                        }
                    } catch (error) {
                        this.showToast('حدث خطأ أثناء الحذف', 'error');
                    }
                },

                toggleAll(checked) {
                    if (checked) {
                        this.selected = this.users.map(user => user.id);
                    } else {
                        this.selected = [];
                    }
                },

                getRoleBadgeClass(role) {
                    const classes = {
                        'super_admin': this.darkMode ? 'bg-purple-500/20 text-purple-300' : 'bg-purple-100 text-purple-700',
                        'admin': this.darkMode ? 'bg-blue-500/20 text-blue-300' : 'bg-blue-100 text-blue-700',
                        'editor': this.darkMode ? 'bg-green-500/20 text-green-300' : 'bg-green-100 text-green-700',
                        'researcher': this.darkMode ? 'bg-cyan-500/20 text-cyan-300' : 'bg-cyan-100 text-cyan-700',
                        'user': this.darkMode ? 'bg-gray-500/20 text-gray-300' : 'bg-gray-100 text-gray-700',
                        'guest': this.darkMode ? 'bg-yellow-500/20 text-yellow-300' : 'bg-yellow-100 text-yellow-700'
                    };
                    return classes[role] || (this.darkMode ? 'bg-gray-500/20 text-gray-300' : 'bg-gray-100 text-gray-700');
                },

                getRoleLabel(role) {
                    const labels = {
                        'super_admin': 'مدير عام',
                        'admin': 'مدير',
                        'editor': 'محرر',
                        'researcher': 'باحث',
                        'user': 'مستخدم',
                        'guest': 'ضيف'
                    };
                    return labels[role] || role;
                },

                formatDate(date) {
                    return new Date(date).toLocaleDateString('ar-EG', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                getPageNumbers() {
                    const current = this.pagination.current_page;
                    const last = this.pagination.last_page;
                    const delta = 2;
                    const pages = [];

                    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                        pages.push(i);
                    }

                    if (current - delta > 2) pages.unshift('...');
                    if (current + delta < last - 1) pages.push('...');

                    pages.unshift(1);
                    if (last > 1) pages.push(last);

                    return pages.filter(p => p !== '...' || pages.indexOf(p) === pages.lastIndexOf(p));
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
