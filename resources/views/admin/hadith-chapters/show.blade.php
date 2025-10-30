@extends('layouts.admin')

@section('title', 'Chapter Details')

@section('content')
<div x-data="chapterView()" x-init="init()">
    {{-- Breadcrumb --}}
    <nav class="flex items-center space-x-2 space-x-reverse text-sm mb-6">
        <a href="{{ route('admin.hadith-chapters.index') }}" class="text-gray-400 hover:text-white transition">
            Hadith Chapters
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-600"></i>
        <a href="{{ route('admin.hadith-books.show', $chapter->book) }}" class="text-gray-400 hover:text-white transition">
            {{ $chapter->book->name['en'] ?? $chapter->book->name['ar'] }}
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-600"></i>
        <span class="text-white">Chapter {{ $chapter->chapter_number }}</span>
    </nav>

    {{-- Header --}}
    <div class="rounded-2xl p-8 mb-8 transition-colors relative overflow-hidden"
         :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/20 to-green-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>

        <div class="relative z-10">
            <div class="flex items-start justify-between mb-6">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 space-x-reverse mb-4">
                        <span class="px-4 py-2 rounded-full bg-blue-500/20 text-blue-400 font-semibold text-sm">
                            Chapter {{ $chapter->chapter_number }}
                        </span>
                        @if($chapter->status === 'published')
                            <span class="px-4 py-2 rounded-full bg-green-500/20 text-green-400 font-semibold text-sm">
                                Published
                            </span>
                        @elseif($chapter->status === 'draft')
                            <span class="px-4 py-2 rounded-full bg-yellow-500/20 text-yellow-400 font-semibold text-sm">
                                Draft
                            </span>
                        @else
                            <span class="px-4 py-2 rounded-full bg-gray-500/20 text-gray-400 font-semibold text-sm">
                                Archived
                            </span>
                        @endif
                    </div>

                    <h1 class="text-4xl font-black text-white mb-3">
                        {{ $chapter->name['en'] ?? $chapter->name['ar'] }}
                    </h1>

                    <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-400 mb-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i data-lucide="book" class="w-4 h-4"></i>
                            <span>Book:</span>
                            <a href="{{ route('admin.hadith-books.show', $chapter->book) }}"
                               class="text-[#64CCC5] hover:text-teal-400 transition font-semibold">
                                {{ $chapter->book->name['en'] ?? $chapter->book->name['ar'] }}
                            </a>
                        </div>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>{{ $chapter->hadiths_count }} Hadith(s)</span>
                        </div>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>Updated {{ $chapter->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3 space-x-reverse">
                    <a href="{{ route('admin.hadith-chapters.edit', $chapter) }}"
                       class="px-6 py-3 bg-[#0056b3] hover:bg-blue-700 text-white rounded-lg font-semibold transition flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        <span>Edit</span>
                    </a>
                    <button @click="confirmDelete()"
                            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition flex items-center space-x-2 space-x-reverse">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </div>

            {{-- Description in All Languages --}}
            @if($chapter->description)
                <div class="prose max-w-none">
                    @foreach(['en', 'ar', 'fr', 'tr', 'ur'] as $lang)
                        @if(!empty($chapter->description[$lang]))
                            <div class="mb-4 p-4 rounded-lg"
                                 :class="darkMode ? 'bg-white/5' : 'bg-gray-100'">
                                <p class="text-sm font-semibold text-gray-400 mb-2">
                                    {{ strtoupper($lang) }}
                                </p>
                                <p class="text-gray-300 leading-relaxed" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                    {{ $chapter->description[$lang] }}
                                </p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Total Hadiths</p>
                    <p class="text-3xl font-black text-white">{{ $chapter->hadiths_count }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Created</p>
                    <p class="text-lg font-bold text-white">{{ $chapter->created_at->format('M d, Y') }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Last Modified</p>
                    <p class="text-lg font-bold text-white">{{ $chapter->updated_at->format('M d, Y') }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl p-6 transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Display Order</p>
                    <p class="text-3xl font-black text-white">{{ $chapter->display_order ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#64CCC5] to-teal-600 flex items-center justify-center">
                    <i data-lucide="arrow-up-down" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Hadiths in This Chapter --}}
    <div class="rounded-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
        <div class="p-6 border-b" :class="darkMode ? 'border-white/10' : 'border-gray-200'">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">
                    Hadiths in This Chapter
                    <span class="text-[#64CCC5] ml-2">({{ $chapter->hadiths_count }})</span>
                </h2>
                @if($chapter->hadiths_count > 0)
                    <a href="{{ route('admin.hadith.create', ['chapter_id' => $chapter->id]) }}"
                       class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg font-semibold hover:shadow-lg transition">
                        <i data-lucide="plus" class="w-4 h-4 inline-block mr-2"></i>
                        Add Hadith
                    </a>
                @endif
            </div>
        </div>

        @if($chapter->hadiths_count > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-white/5' : 'bg-gray-50'">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Hadith #</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Arabic Text</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Primary Narrator</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Grade</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chapter->hadiths()->limit(20)->get() as $hadith)
                            <tr class="border-t transition-colors hover:bg-white/5"
                                :class="darkMode ? 'border-white/10' : 'border-gray-200'">
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-semibold text-sm">
                                        #{{ $hadith->hadith_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-md">
                                    <p class="text-gray-300 line-clamp-2" dir="rtl">
                                        {{ Str::limit($hadith->arabic_text, 100) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-gray-300">
                                    {{ $hadith->primary_narrator ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($hadith->grade === 'sahih')
                                        <span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 font-semibold text-xs">
                                            Sahih
                                        </span>
                                    @elseif($hadith->grade === 'hasan')
                                        <span class="px-3 py-1 rounded-full bg-yellow-500/20 text-yellow-400 font-semibold text-xs">
                                            Hasan
                                        </span>
                                    @elseif($hadith->grade === 'daif')
                                        <span class="px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 font-semibold text-xs">
                                            Daif
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-gray-500/20 text-gray-400 font-semibold text-xs">
                                            {{ ucfirst($hadith->grade ?? 'Unknown') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.hadith.show', $hadith) }}"
                                           class="text-[#0056b3] hover:text-blue-400 transition"
                                           title="View Hadith">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="{{ route('admin.hadith.edit', $hadith) }}"
                                           class="text-[#64CCC5] hover:text-teal-400 transition"
                                           title="Edit Hadith">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($chapter->hadiths_count > 20)
                <div class="p-6 border-t text-center"
                     :class="darkMode ? 'border-white/10' : 'border-gray-200'">
                    <p class="text-gray-400">
                        Showing first 20 of {{ $chapter->hadiths_count }} hadiths.
                        <a href="{{ route('admin.hadith.index', ['chapter_id' => $chapter->id]) }}"
                           class="text-[#64CCC5] hover:text-teal-400 font-semibold ml-2">
                            View all hadiths in this chapter →
                        </a>
                    </p>
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                <p class="text-gray-400 text-lg mb-4">No hadiths in this chapter yet</p>
                <a href="{{ route('admin.hadith.create', ['chapter_id' => $chapter->id]) }}"
                   class="inline-flex items-center space-x-2 space-x-reverse px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg font-semibold hover:shadow-lg transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add First Hadith</span>
                </a>
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-75" @click="showDeleteModal = false"></div>

            <div class="relative bg-gray-900 rounded-lg max-w-md w-full p-6 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-4">Delete Chapter?</h3>
                <p class="text-gray-400 mb-6">
                    Are you sure you want to delete this chapter?
                    @if($chapter->hadiths_count > 0)
                        <span class="text-red-400 font-semibold">
                            This will affect {{ $chapter->hadiths_count }} hadith(s).
                        </span>
                    @else
                        This action cannot be undone.
                    @endif
                </p>

                <div class="flex gap-3">
                    <form action="{{ route('admin.hadith-chapters.destroy', $chapter) }}"
                          method="POST"
                          class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                            Yes, Delete
                        </button>
                    </form>
                    <button @click="showDeleteModal = false"
                            class="flex-1 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function chapterView() {
    return {
        showDeleteModal: false,

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        confirmDelete() {
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
