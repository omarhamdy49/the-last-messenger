@extends('layouts.admin')

@php
use Illuminate\Support\Str;
@endphp

@section('title', __('admin.hadiths'))

@section('content')
<div class="min-h-screen transition-colors duration-300" :class="darkMode ? 'bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900' : 'bg-gradient-to-br from-gray-50 via-white to-gray-50'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="mb-12">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.hadiths') }}
                    </h1>
                    <p class="text-base sm:text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('admin.manage_hadiths') }}
                    </p>
                </div>
                <a href="{{ route('admin.hadith.create') }}" class="inline-flex items-center justify-center gap-2 px-6 sm:px-8 py-3 sm:py-4 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold rounded-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 w-full sm:w-auto group">
                    <i data-lucide="plus" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span>{{ __('admin.add_hadith') }}</span>
                </a>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="group p-6 sm:p-8 rounded-2xl backdrop-blur-xl" :class="darkMode ? 'bg-gray-800/40 border border-gray-700/50 hover:bg-gray-800/60' : 'bg-white/40 border border-white/50 hover:bg-white/60'" style="transition: all 0.3s ease;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm uppercase tracking-widest font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.total_hadiths') }}
                        </p>
                        <p class="text-3xl sm:text-4xl font-black mt-3 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                            {{ number_format($hadiths->total()) }}
                        </p>
                    </div>
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gradient-to-br from-blue-900/20 to-blue-800/20' : 'bg-gradient-to-br from-blue-100/50 to-blue-50/50'">
                        <i data-lucide="book-open" class="w-8 h-8 text-[#0056b3]"></i>
                    </div>
                </div>
            </div>

            <div class="group p-6 sm:p-8 rounded-2xl backdrop-blur-xl" :class="darkMode ? 'bg-gray-800/40 border border-gray-700/50 hover:bg-gray-800/60' : 'bg-white/40 border border-white/50 hover:bg-white/60'" style="transition: all 0.3s ease;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs sm:text-sm uppercase tracking-widest font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.current_page') }}
                        </p>
                        <p class="text-3xl sm:text-4xl font-black mt-3">{{ $hadiths->currentPage() }} / {{ $hadiths->lastPage() }}</p>
                    </div>
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gradient-to-br from-green-900/20 to-green-800/20' : 'bg-gradient-to-br from-green-100/50 to-green-50/50'">
                        <i data-lucide="layout-grid" class="w-8 h-8 text-[#218838]"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filters Section -->
        <div class="space-y-4 mb-8">
            <!-- Search Bar -->
            <div class="group p-6 sm:p-7 rounded-2xl backdrop-blur-xl" :class="darkMode ? 'bg-gray-800/40 border border-gray-700/50 hover:border-gray-600/50 focus-within:border-blue-500/50' : 'bg-white/40 border border-white/50 hover:border-white/60 focus-within:border-blue-400/50'" style="transition: all 0.3s ease;">
                <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-xs sm:text-sm uppercase tracking-widest font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            🔍 {{ __('admin.search') }}
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="ابحث عن: رقم، نص، أو كتاب..."
                            class="w-full px-4 sm:px-6 py-3 sm:py-4 rounded-xl border text-sm sm:text-base transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0"
                            :class="darkMode ? 'bg-gray-700/50 border-gray-600/50 text-white placeholder-gray-500 focus:border-blue-500/50 focus:ring-blue-500/20' : 'bg-white/60 border-white/80 text-gray-900 placeholder-gray-500 focus:border-blue-400/50 focus:ring-blue-400/20'">
                    </div>
                    <button type="submit" class="px-6 sm:px-8 py-3 sm:py-4 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-semibold text-sm sm:text-base w-full sm:w-auto group/btn">
                        <i data-lucide="search" class="w-5 h-5 inline group-hover/btn:scale-110 transition-transform"></i>
                    </button>
                </form>
            </div>

            <!-- Filter Bar -->
            <div class="p-6 sm:p-7 rounded-2xl backdrop-blur-xl" :class="darkMode ? 'bg-gray-800/40 border border-gray-700/50' : 'bg-white/40 border border-white/50'">
                <form method="GET" x-data="{ collectionId: '{{ request('collection_id') }}' }" class="space-y-4">
                    <input type="hidden" name="search" value="{{ request('search') }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Collection Filter -->
                        <div>
                            <label class="block text-xs sm:text-sm uppercase tracking-widest font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">📚 {{ __('admin.collection') }}</label>
                            <select name="collection_id"
                                x-model="collectionId"
                                @change="document.querySelector('form').submit()"
                                class="w-full px-4 py-2.5 sm:py-3 rounded-xl border text-sm transition-all duration-200"
                                :class="darkMode ? 'bg-gray-700/50 border-gray-600/50 text-white focus:border-blue-500/50' : 'bg-white/60 border-white/80 text-gray-900 focus:border-blue-400/50'">
                                <option value="">{{ __('admin.all_collections') }}</option>
                                @foreach($collections as $collection)
                                    <option value="{{ $collection->id }}">{{ $collection->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Book Filter -->
                        <div>
                            <label class="block text-xs sm:text-sm uppercase tracking-widest font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">📖 {{ __('admin.book') }}</label>
                            <select name="book_id" class="w-full px-4 py-2.5 sm:py-3 rounded-xl border text-sm transition-all duration-200"
                                :class="darkMode ? 'bg-gray-700/50 border-gray-600/50 text-white focus:border-blue-500/50' : 'bg-white/60 border-white/80 text-gray-900 focus:border-blue-400/50'">
                                <option value="">{{ __('admin.all_books') }}</option>
                                @foreach($books as $book)
                                    <option value="{{ $book->id }}" @selected(request('book_id') == $book->id)>{{ $book->title_ar }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Grade Filter -->
                        <div>
                            <label class="block text-xs sm:text-sm uppercase tracking-widest font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">⭐ {{ __('admin.grading') }}</label>
                            <select name="grading_status" class="w-full px-4 py-2.5 sm:py-3 rounded-xl border text-sm transition-all duration-200"
                                :class="darkMode ? 'bg-gray-700/50 border-gray-600/50 text-white focus:border-blue-500/50' : 'bg-white/60 border-white/80 text-gray-900 focus:border-blue-400/50'">
                                <option value="">{{ __('admin.all_grades') }}</option>
                                @foreach($gradingLevels as $level)
                                    <option value="{{ $level->id }}" @selected(request('grading_status') == $level->id)>{{ $level->grade_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button type="submit" class="px-6 py-2.5 sm:py-3 bg-[#0056b3] hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-semibold text-sm">
                            {{ __('admin.filter') }}
                        </button>
                        <a href="{{ route('admin.hadith.index') }}" class="px-6 py-2.5 sm:py-3 border rounded-lg font-semibold text-sm text-center transition-all duration-200"
                            :class="darkMode ? 'border-gray-600/50 text-gray-300 hover:bg-gray-700/50' : 'border-gray-300 text-gray-700 hover:bg-gray-100/50'">
                            {{ __('admin.reset') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hadiths Grid -->
        @if($hadiths->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 mb-8">
                @foreach($hadiths as $hadith)
                    <a href="{{ route('admin.hadith.show', $hadith) }}"
                       class="group hadith-card flex flex-col rounded-2xl backdrop-blur-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                       :class="darkMode ? 'bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700/50 hover:border-blue-500/30' : 'bg-gradient-to-br from-white to-gray-50 border border-gray-200/50 hover:border-blue-400/30'">

                        <!-- Header with Collection & Number -->
                        <div class="px-6 pt-6 pb-4 border-b" :class="darkMode ? 'border-gray-700/30' : 'border-gray-200/30'">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1">
                                    <p class="text-xs uppercase tracking-widest font-bold" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                        {{ $hadith->book?->collection?->name_ar ?? '—' }}
                                    </p>
                                </div>
                                <div class="px-3 py-1.5 rounded-lg backdrop-blur-sm" :class="darkMode ? 'bg-blue-900/30 text-blue-200' : 'bg-blue-100/60 text-blue-700'">
                                    <span class="text-xs font-bold">#{{ $hadith->hadith_number }}</span>
                                </div>
                            </div>
                            <p class="text-xs uppercase tracking-widest font-semibold" :class="darkMode ? 'text-gray-600' : 'text-gray-500'">
                                📖 {{ $hadith->book?->title_ar ?? '—' }}
                            </p>
                        </div>

                        <!-- Hadith Text (Hero) -->
                        <div class="flex-1 px-6 py-6">
                            <p class="text-sm sm:text-base leading-relaxed font-serif line-clamp-5 transition-colors duration-200 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-[#0056b3] group-hover:to-[#218838]"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
                               title="{{ $hadith->translations->where('language_code', 'ar')->first()?->text ?? '' }}">
                                {{ Str::limit($hadith->translations->where('language_code', 'ar')->first()?->text ?? '—', 180) }}
                            </p>
                        </div>

                        <!-- Footer with Grade & Actions -->
                        <div class="px-6 pb-6 pt-4 border-t flex items-center justify-between gap-3" :class="darkMode ? 'border-gray-700/30' : 'border-gray-200/30'">
                            <!-- Grade -->
                            <div>
                                @if($hadith->grading_status)
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-full whitespace-nowrap transition-all"
                                          :class="darkMode ? 'bg-emerald-900/40 text-emerald-200 border border-emerald-700/50' : 'bg-emerald-100/60 text-emerald-700 border border-emerald-300/50'">
                                        ⭐ {{ $hadith->grading_status }}
                                    </span>
                                @else
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-full"
                                          :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('admin.ungraded') }}
                                    </span>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.hadith.show', $hadith) }}"
                                   class="p-2 sm:p-2.5 rounded-lg transition-all duration-200 group/icon"
                                   :class="darkMode ? 'text-blue-400 hover:bg-blue-900/30' : 'text-blue-600 hover:bg-blue-100/50'">
                                    <i data-lucide="eye" class="w-5 h-5 group-hover/icon:scale-110 transition-transform"></i>
                                </a>
                                <a href="{{ route('admin.hadith.edit', $hadith) }}"
                                   class="p-2 sm:p-2.5 rounded-lg transition-all duration-200 group/icon"
                                   :class="darkMode ? 'text-amber-400 hover:bg-amber-900/30' : 'text-amber-600 hover:bg-amber-100/50'">
                                    <i data-lucide="edit-3" class="w-5 h-5 group-hover/icon:scale-110 transition-transform"></i>
                                </a>
                                <form action="{{ route('admin.hadith.destroy', $hadith) }}" method="POST" class="inline-block"
                                      @click.stop @submit.prevent="if (confirm('{{ __('admin.confirm_delete') }}')) { this.submit(); }">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 sm:p-2.5 rounded-lg transition-all duration-200 group/icon"
                                            :class="darkMode ? 'text-red-400 hover:bg-red-900/30' : 'text-red-600 hover:bg-red-100/50'">
                                        <i data-lucide="trash-2" class="w-5 h-5 group-hover/icon:scale-110 transition-transform"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $hadiths->links('pagination::tailwind') }}
            </div>
        @else
            <!-- Empty State -->
            <div class="p-12 sm:p-16 rounded-2xl text-center backdrop-blur-xl" :class="darkMode ? 'bg-gray-800/40 border border-gray-700/50' : 'bg-white/40 border border-white/50'">
                <div class="mb-4">
                    <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.no_hadiths_found') }}</h3>
                <p class="text-sm sm:text-base" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">حاول تغيير معايير البحث أو الفلترة</p>
            </div>
        @endif
    </div>
</div>

<style>
    /* Font Improvements */
    .font-serif {
        font-family: 'Georgia', 'Garamond', serif;
        letter-spacing: 0.3px;
    }

    /* Smooth Animations */
    .hadith-card {
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Backdrop Blur Support */
    @supports (backdrop-filter: blur(10px)) {
        .backdrop-blur-xl {
            backdrop-filter: blur(20px);
        }
    }

    /* Smooth Transitions */
    * {
        @apply transition-colors duration-200;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(107, 114, 128, 0.7);
    }
</style>

<script>
    lucide.createIcons();
</script>
@endsection
