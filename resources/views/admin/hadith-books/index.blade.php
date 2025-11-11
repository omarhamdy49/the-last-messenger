@extends('admin.layouts.app')

@section('title', 'Hadith Books')

@section('content')
<div class="space-y-6" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                كتب الحديث
            </h1>
            <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Manage hadith books (Sahih al-Bukhari, Sahih Muslim, etc.)
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.hadith-books.create') }}"
               class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>New Book</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl p-6 shadow-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الكتب</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $stats['total'] ?? 0 }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center">
                    <i data-lucide="book" class="w-6 h-6 text-blue-500"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 shadow-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">نشط</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $stats['active'] ?? 0 }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-500"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 shadow-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">غير نشط</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $stats['inactive'] ?? 0 }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-gray-500/10 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-6 h-6 text-gray-500"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 shadow-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الأحاديث</p>
                    <p class="text-2xl font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ number_format($stats['total_hadith'] ?? 0) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-purple-500/10 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-6 h-6 text-purple-500"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-xl shadow-lg overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b" :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المؤلف
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المجموعة
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            عدد الأحاديث
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الحالة
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-700' : 'divide-gray-200'">
                    @forelse($books as $book)
                    <tr class="hover:bg-opacity-50 transition-colors" :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $book->getTranslation('name', 'ar') }}
                                </p>
                                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $book->getTranslation('name', 'en') }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $book->author_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $book->collection->getTranslation('name', 'ar') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ number_format($book->total_hadith_count) }}
                        </td>
                        <td class="px-6 py-4">
                            @if($book->active)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                                نشط
                            </span>
                            @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-500">
                                غير نشط
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.hadith-books.show', $book->id) }}"
                                   class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.hadith-books.edit', $book->id) }}"
                                   class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.hadith-books.destroy', $book->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-50">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            <p class="text-lg font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                لا توجد كتب
                            </p>
                            <a href="{{ route('admin.hadith-books.create') }}" class="text-accent hover:underline">
                                أضف كتابك الأول
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($books->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            {{ $books->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
