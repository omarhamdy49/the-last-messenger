@extends('layouts.admin')

@section('title', 'Hadith Book Details')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="book-open" class="w-8 h-8 inline ml-2"></i>
                Hadith Book Details
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                View comprehensive information about this hadith book
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.hadith-books.edit', $book->id) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i data-lucide="pencil" class="w-4 h-4 inline ml-2"></i>
                Edit
            </a>
            <form action="{{ route('admin.hadith-books.destroy', $book->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Are you sure you want to delete this book and all its {{ $book->chapters_count ?? 0 }} chapters and {{ $book->hadith_count ?? 0 }} hadith?')"
                        class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4 inline ml-2"></i>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 space-x-reverse">
            <li><a href="{{ route('admin.hadith-books.index') }}" class="text-[#0056b3] hover:text-blue-700">Hadith Books</a></li>
            <li><i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i></li>
            <li><span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $book->name['en'] ?? $book->name['ar'] }}</span></li>
        </ol>
    </nav>

    <!-- Book Information Card -->
    <div class="glass-light rounded-lg p-8 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="flex items-start gap-6">
            <div class="flex-shrink-0">
                <div class="w-24 h-24 rounded-lg flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                    <i data-lucide="book-open" class="w-12 h-12 text-white"></i>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $book->name['en'] ?? $book->name['ar'] }}
                </h2>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Book Type</p>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold mt-1 inline-block
                            @if($book->book_type === 'sahih') bg-green-100 text-green-800
                            @elseif($book->book_type === 'sunan') bg-blue-100 text-blue-800
                            @elseif($book->book_type === 'musnad') bg-teal-100 text-teal-800
                            @elseif($book->book_type === 'jami') bg-purple-100 text-purple-800
                            @elseif($book->book_type === 'tafsir') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($book->book_type ?? 'N/A') }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Status</p>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold mt-1 inline-block
                            @if($book->status === 'published') bg-green-100 text-green-800
                            @elseif($book->status === 'draft') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($book->status ?? 'Draft') }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Chapters</p>
                        <p class="text-2xl font-bold text-[#0056b3]">{{ $book->chapters_count ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Hadith</p>
                        <p class="text-2xl font-bold text-[#64CCC5]">{{ $book->hadith_count ?? 0 }}</p>
                    </div>
                </div>

                <!-- Additional Metadata -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    @if($book->author && !empty($book->author['en'] ?? $book->author['ar']))
                        <div>
                            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Author</p>
                            <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $book->author['en'] ?? $book->author['ar'] }}</p>
                        </div>
                    @endif
                    @if($book->year_of_compilation)
                        <div>
                            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Year of Compilation (Hijri)</p>
                            <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $book->year_of_compilation }} AH</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Display Order</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $book->display_order ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Created At</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $book->created_at?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- All Language Names -->
                <div class="border-t pt-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h3 class="text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <i data-lucide="languages" class="w-4 h-4 inline ml-1"></i>
                        Names in All Languages
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach(['ar' => 'Arabic', 'en' => 'English', 'fr' => 'French', 'tr' => 'Turkish', 'ur' => 'Urdu'] as $lang => $label)
                            @if(!empty($book->name[$lang]))
                                <div>
                                    <span class="text-xs font-semibold" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $label }}:</span>
                                    <p :class="darkMode ? 'text-white' : 'text-gray-900'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ $book->name[$lang] }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Description -->
                @if(!empty($book->description['en']) || !empty($book->description['ar']))
                    <div class="border-t pt-4 mt-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        <h3 class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Description</h3>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $book->description['en'] ?? $book->description['ar'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Chapters Section -->
    @if($book->chapters && $book->chapters->count() > 0)
        <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="list" class="w-5 h-5 inline ml-1"></i>
                    Chapters ({{ $book->chapters->count() }})
                </h3>
                <a href="{{ route('admin.hadith-chapters.create', ['book_id' => $book->id]) }}"
                   class="bg-[#0056b3] text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition text-sm">
                    <i data-lucide="plus" class="w-4 h-4 inline"></i>
                    Add Chapter
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Chapter Name</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Hadith Count</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($book->chapters as $chapter)
                            <tr class="border-t" :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                                <td class="px-4 py-3 font-semibold text-[#0056b3]">{{ $chapter->chapter_number }}</td>
                                <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $chapter->name['en'] ?? $chapter->name['ar'] }}
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    <span class="font-bold text-[#64CCC5]">{{ $chapter->hadith_count ?? 0 }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.hadith-chapters.show', $chapter->id) }}" class="text-[#0056b3] hover:text-blue-700 text-sm font-semibold ml-3">
                                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                        View
                                    </a>
                                    <a href="{{ route('admin.hadith-chapters.edit', $chapter->id) }}" class="text-[#64CCC5] hover:text-teal-700 text-sm font-semibold ml-3">
                                        <i data-lucide="pencil" class="w-4 h-4 inline"></i>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="glass-light rounded-lg p-8 mb-6 text-center" :class="darkMode ? 'glass-dark' : ''">
            <i data-lucide="list" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">No Chapters Yet</h3>
            <p class="mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                This book doesn't have any chapters. Start by adding the first chapter.
            </p>
            <a href="{{ route('admin.hadith-chapters.create', ['book_id' => $book->id]) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition inline-block">
                <i data-lucide="plus" class="w-4 h-4 inline ml-2"></i>
                Create First Chapter
            </a>
        </div>
    @endif

    <!-- Recent Hadith Section -->
    @if($book->hadith && $book->hadith->count() > 0)
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="scroll-text" class="w-5 h-5 inline ml-1"></i>
                    Recent Hadith (Showing {{ min(10, $book->hadith->count()) }} of {{ $book->hadith_count ?? 0 }})
                </h3>
                <a href="{{ route('admin.hadith.index', ['book_id' => $book->id]) }}"
                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm">
                    View All
                    <i data-lucide="arrow-left" class="w-4 h-4 inline"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Number</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Chapter</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Text (Preview)</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Grade</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($book->hadith->take(10) as $hadith)
                            <tr class="border-t" :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                                <td class="px-4 py-3 font-semibold text-[#0056b3]">{{ $hadith->hadith_number }}</td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ $hadith->chapter->name['en'] ?? $hadith->chapter->name['ar'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <div class="max-w-md truncate" dir="rtl">
                                        {{ Str::limit($hadith->arabic_text ?? 'N/A', 80) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($hadith->grade)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @if($hadith->grade === 'sahih') bg-green-100 text-green-800
                                            @elseif($hadith->grade === 'hasan') bg-blue-100 text-blue-800
                                            @elseif($hadith->grade === 'daif') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($hadith->grade) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.hadith.show', $hadith->id) }}" class="text-[#0056b3] hover:text-blue-700 text-sm font-semibold ml-3">
                                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                        View
                                    </a>
                                    <a href="{{ route('admin.hadith.edit', $hadith->id) }}" class="text-[#64CCC5] hover:text-teal-700 text-sm font-semibold ml-3">
                                        <i data-lucide="pencil" class="w-4 h-4 inline"></i>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="glass-light rounded-lg p-8 text-center" :class="darkMode ? 'glass-dark' : ''">
            <i data-lucide="scroll-text" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <h3 class="text-lg font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">No Hadith Yet</h3>
            <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                This book doesn't have any hadith entries yet.
            </p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
