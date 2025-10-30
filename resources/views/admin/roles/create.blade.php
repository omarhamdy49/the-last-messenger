@extends('layouts.admin')
@section('title', 'إضافة دور')
@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div><h1 class="text-3xl font-bold gradient-text">إضافة دور جديد</h1></div>
        <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">
            <i data-lucide="arrow-right" class="w-5 h-5"></i> العودة
        </a>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="shield" class="w-6 h-6"></i> معلومات الدور
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2"><span class="text-red-400">*</span> اسم الدور</label>
                    <input type="text" name="name" placeholder="مثال: editor" required value="{{ old('name') }}" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\'">
                    @error('name')<p class="mt-1 text-red-400 text-sm">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="lock" class="w-6 h-6"></i> الصلاحيات
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($permissions ?? [] as $permission)
                    <label class="flex items-center gap-3 p-3 bg-white/5 rounded-lg border border-white/10 cursor-pointer hover:border-blue-500/50">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="w-4 h-4" {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                        <span class="text-white flex-1">{{ $permission->name }}</span>
                    </label>
                @empty
                    <p class="text-gray-400">لا توجد صلاحيات متاحة</p>
                @endforelse
            </div>
            @error('permissions')<p class="mt-2 text-red-400 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-4">
            <a href="{{ route('admin.roles.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">إلغاء</a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">إضافة الدور</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>lucide.createIcons();</script>
@endpush
