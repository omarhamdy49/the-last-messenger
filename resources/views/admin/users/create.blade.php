@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.users.index') }}" class="hover:text-accent transition-colors">المستخدمون</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>إضافة مستخدم جديد</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            إضافة مستخدم جديد
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
<form action="{{ route('admin.users.store') }}" method="POST" x-data="{ showPassword: false }">
    @csrf

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
                               name="name"
                               value="{{ old('name') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="أدخل اسم المستخدم..."
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            البريد الإلكتروني <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="ltr"
                               placeholder="user@example.com"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            كلمة المرور <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                   dir="ltr"
                                   placeholder="********"
                                   required>
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute end-3 top-1/2 -translate-y-1/2"
                                    :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            تأكيد كلمة المرور <span class="text-red-500">*</span>
                        </label>
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password_confirmation"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="ltr"
                               placeholder="********"
                               required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Roles -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
                 x-data="{ roles: [] }"
                 x-init="
                    fetch('{{ route('admin.roles.index') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(d => roles = d.data || [])
                        .catch(() => roles = [])
                 ">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="shield" class="w-5 h-5 text-accent"></i>
                    الأدوار
                </h3>

                <div class="space-y-3">
                    <template x-for="role in roles" :key="role.id">
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all"
                               :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                            <input type="checkbox"
                                   name="roles[]"
                                   :value="role.name"
                                   class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                            <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="role.name"></span>
                        </label>
                    </template>
                    <p x-show="roles.length === 0" class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        جاري تحميل الأدوار...
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
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
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
