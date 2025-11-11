@extends('layouts.admin')
@section('title', 'إضافة حديث')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.hadith.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
        <h1 class="text-3xl font-bold">إضافة حديث جديد</h1>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li class="text-red-600">{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.hadith.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block font-semibold mb-2">الكتاب *</label>
                <select name="book_id" required class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="">-- اختر الكتاب --</option>
                </select></div>

                <div><label class="block font-semibold mb-2">الباب</label>
                <select name="chapter_id" class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="">-- اختر الباب --</option>
                </select></div>

                <div><label class="block font-semibold mb-2">رقم الحديث *</label>
                <input type="number" name="hadith_number" value="{{ old('hadith_number') }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">الدرجة *</label>
                <select name="grade" required class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="sahih">صحيح</option>
                    <option value="hasan">حسن</option>
                    <option value="daif">ضعيف</option>
                    <option value="mawdu">موضوع</option>
                    <option value="unknown">غير معروف</option>
                </select></div>
            </div>

            <div><label class="block font-semibold mb-2">النص العربي *</label>
            <textarea name="arabic_text" rows="6" required dir="rtl" class="w-full px-4 py-3 rounded-lg border-2" placeholder="النص العربي للحديث">{{ old('arabic_text') }}</textarea></div>

            <div><label class="block font-semibold mb-2">الترجمة الإنجليزية</label>
            <textarea name="translations[en]" rows="4" class="w-full px-4 py-3 rounded-lg border-2" placeholder="English translation">{{ old('translations.en') }}</textarea></div>

            <div class="flex items-center"><input type="checkbox" name="active" value="1" checked class="w-4 h-4"><label class="ml-2">نشط</label></div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">إنشاء الحديث</button>
        </div>
    </form>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
