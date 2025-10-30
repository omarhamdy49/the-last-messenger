@extends('layouts.admin')
@section('title', 'تعديل الشخص')
@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div><h1 class="text-3xl font-bold gradient-text">تعديل الشخص</h1></div>
        <a href="{{ route('admin.genealogy.index') }}" class="flex items-center gap-2 px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600">
            <i data-lucide="arrow-right" class="w-5 h-5"></i> العودة
        </a>
    </div>

    <form action="{{ route('admin.genealogy.update', $person) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
            <h2 class="text-xl font-bold text-white mb-6">المعلومات الأساسية</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" name="name[ar]" placeholder="الاسم (العربية)" required value="{{ old('name.ar', $person->name['ar'] ?? '') }}" class="px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white">
                    <input type="text" name="name[en]" placeholder="Name (English)" required value="{{ old('name.en', $person->name['en'] ?? '') }}" class="px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="date" name="birth_date" value="{{ old('birth_date', $person->birth_date?->format('Y-m-d')) }}" class="px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white">
                    <input type="date" name="death_date" value="{{ old('death_date', $person->death_date?->format('Y-m-d')) }}" class="px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white">
                </div>
                <select name="gender" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\'">
                    <option value="">اختر النوع</option>
                    <option value="male" {{ old('gender', $person->gender) === 'male' ? 'selected' : '' }}>ذكر</option>
                    <option value="female" {{ old('gender', $person->gender) === 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>
        </div>

        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\'">
            <h2 class="text-xl font-bold text-white mb-6">السيرة الذاتية</h2>
            <textarea name="description[ar]" rows="5" placeholder="السيرة الذاتية (العربية)" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' mb-4">{{ old('description.ar', $person->description['ar'] ?? '') }}</textarea>
            <textarea name="description[en]" rows="5" placeholder="Biography (English)" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\'">{{ old('description.en', $person->description['en'] ?? '') }}</textarea>
        </div>

        <div class="flex gap-4">
            <a href="{{ route('admin.genealogy.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">إلغاء</a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">حفظ</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>lucide.createIcons();</script>
@endpush
