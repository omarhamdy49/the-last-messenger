@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.roles.index') }}" class="hover:text-accent transition-colors">الأدوار</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل الدور
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.roles.index') }}"
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
    role: null,
    loading: true,
    permissions: [],
    selectedPermissions: [],
    searchQuery: '',
    errors: {},

    async init() {
        try {
            const [roleRes, permRes] = await Promise.all([
                fetch('{{ route('admin.roles.show', $id) }}', { headers: { 'Accept': 'application/json' } }),
                fetch('{{ route('admin.permissions.index') }}', { headers: { 'Accept': 'application/json' } })
            ]);

            const roleData = await roleRes.json();
            const permData = await permRes.json();

            if (roleData.success !== false) {
                this.role = roleData.data || roleData;
                this.selectedPermissions = (this.role.permissions || []).map(p => p.name || p);
            }

            this.permissions = permData.data || [];
        } catch (e) {
            console.error('Failed to load data:', e);
        } finally {
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        }
    },

    get filteredPermissions() {
        if (!this.searchQuery) return this.permissions;
        return this.permissions.filter(p =>
            p.name.toLowerCase().includes(this.searchQuery.toLowerCase())
        );
    },

    get groupedPermissions() {
        const groups = {};
        this.filteredPermissions.forEach(p => {
            const parts = p.name.split('.');
            const group = parts.length > 1 ? parts[0] : 'عام';
            if (!groups[group]) groups[group] = [];
            groups[group].push(p);
        });
        return groups;
    },

    togglePermission(name) {
        const idx = this.selectedPermissions.indexOf(name);
        if (idx > -1) {
            this.selectedPermissions.splice(idx, 1);
        } else {
            this.selectedPermissions.push(name);
        }
    },

    selectAll() {
        this.selectedPermissions = this.permissions.map(p => p.name);
    },

    deselectAll() {
        this.selectedPermissions = [];
    },

    async submitForm() {
        this.errors = {};
        try {
            const res = await fetch('{{ route('admin.roles.update', $id) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: this.role.name,
                    permissions: this.selectedPermissions
                })
            });

            const data = await res.json();

            if (res.ok) {
                window.location.href = '{{ route('admin.roles.index') }}';
            } else {
                this.errors = data.errors || {};
            }
        } catch (e) {
            console.error('Submit failed:', e);
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

                <!-- Role Name -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="shield" class="w-5 h-5 text-accent"></i>
                        بيانات الدور
                    </h2>

                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الدور <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="role.name"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="ltr"
                               placeholder="مثال: editor, moderator..."
                               required>
                        <template x-if="errors.name">
                            <p class="mt-1 text-sm text-red-500" x-text="errors.name[0]"></p>
                        </template>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="key" class="w-5 h-5 text-accent"></i>
                            الصلاحيات
                        </h2>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAll()"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                تحديد الكل
                            </button>
                            <button type="button" @click="deselectAll()"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                إلغاء الكل
                            </button>
                        </div>
                    </div>

                    <!-- Search Permissions -->
                    <div class="mb-4">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="ابحث في الصلاحيات..."
                               class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <!-- Selected Count -->
                    <div class="mb-4 p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الصلاحيات المحددة: <span class="font-bold text-accent" x-text="selectedPermissions.length"></span>
                            من <span x-text="permissions.length"></span>
                        </span>
                    </div>

                    <!-- Permissions Grid -->
                    <div class="space-y-6">
                        <template x-for="(perms, group) in groupedPermissions" :key="group">
                            <div>
                                <h3 class="text-sm font-semibold mb-3 uppercase" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="group"></h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <template x-for="perm in perms" :key="perm.id">
                                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                                               :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                                            <input type="checkbox"
                                                   :value="perm.name"
                                                   :checked="selectedPermissions.includes(perm.name)"
                                                   @change="togglePermission(perm.name)"
                                                   class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                                            <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="perm.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Info -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                        معلومات
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                            <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="role?.created_at ? new Date(role.created_at).toLocaleDateString('ar-EG') : '-'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                            <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="role?.updated_at ? new Date(role.updated_at).toLocaleDateString('ar-EG') : '-'"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد الصلاحيات</span>
                            <span class="font-bold text-accent" x-text="selectedPermissions.length"></span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <button type="button"
                            @click="submitForm()"
                            class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        {{ __('admin.save') }}
                    </button>

                    <a href="{{ route('admin.roles.index') }}"
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
