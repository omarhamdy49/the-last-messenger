@extends('layouts.admin')

@php
use Illuminate\Support\Str;
@endphp

@section('title', __('admin.hadiths'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold tracking-tight" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.hadiths') }}</h1>
            <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.manage_hadiths') }}</p>
        </div>
        <a href="{{ route('admin.hadith.create') }}" class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-medium rounded-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-200">
            <i data-lucide="plus" class="w-5 h-5"></i>
            {{ __('admin.add_hadith') }}
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-6 rounded-lg backdrop-blur-sm" :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white/50 border border-gray-200'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-widest" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.total_hadiths') }}</p>
                    <p class="text-3xl font-bold mt-2 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">{{ $hadiths->total() }}</p>
                </div>
                <div class="p-3 rounded-lg" :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                    <i data-lucide="book" class="w-6 h-6 text-[#0056b3]"></i>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-lg backdrop-blur-sm" :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white/50 border border-gray-200'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-widest" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.current_page') }}</p>
                    <p class="text-3xl font-bold mt-2">{{ $hadiths->currentPage() }}</p>
                </div>
                <div class="p-3 rounded-lg" :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                    <i data-lucide="list" class="w-6 h-6 text-[#218838]"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="space-y-4">
        <!-- Search Row -->
        <div class="p-6 rounded-lg backdrop-blur-sm" :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white/50 border border-gray-200'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <form method="GET" class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="ابحث عن: رقم، نص، أو كتاب..."
                        class="w-full px-4 py-2 rounded-lg border text-sm"
                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all font-medium text-sm">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </button>
            </form>
        </div>

        <!-- Filters Row -->
        <div class="p-6 rounded-lg backdrop-blur-sm" :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'bg-white/50 border border-gray-200'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <form method="GET" x-data="{ collectionId: '{{ request('collection_id') }}' }" class="space-y-4">
                <!-- Hidden search input to preserve it -->
                <input type="hidden" name="search" value="{{ request('search') }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Collection Filter -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">📚 {{ __('admin.collection') }}</label>
                        <select name="collection_id"
                            x-model="collectionId"
                            @change="document.querySelector('form').submit()"
                            class="w-full px-4 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach($collections as $collection)
                                <option value="{{ $collection->id }}" @selected(request('collection_id') == $collection->id)>
                                    {{ $collection->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Book Filter (Cascading) -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">📖 {{ __('admin.book') }}</label>
                        <select name="book_id" class="w-full px-4 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach($books as $book)
                                <option value="{{ $book->id }}" @selected(request('book_id') == $book->id)>
                                    {{ $book->title_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Grade Filter -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">⭐ {{ __('admin.grading') }}</label>
                        <select name="grading_status" class="w-full px-4 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach($gradingLevels as $level)
                                <option value="{{ $level->id }}" @selected(request('grading_status') == $level->id)>
                                    {{ $level->grade_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-opacity-90 transition-all font-medium text-sm">
                        {{ __('admin.filter') }}
                    </button>
                    <a href="{{ route('admin.hadith.index') }}" class="px-4 py-2 border rounded-lg font-medium text-sm" :class="darkMode ? 'border-gray-600 text-gray-300 hover:bg-gray-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                        {{ __('admin.reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Hadiths Rows -->
    <div x-data="{ selectedId: null }" class="space-y-4">
        @if($hadiths->count() > 0)
            @foreach($hadiths as $hadith)
                <div class="group hadith-row rounded-xl overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.01] cursor-pointer"
                     :class="darkMode ? 'bg-gray-800/60 border border-gray-700 hover:bg-gray-800' : 'bg-white border border-gray-200 hover:shadow-xl'"
                     style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">

                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center">
                        <!-- Hadith Number Badge -->
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                <span class="text-lg sm:text-xl font-bold text-white">#{{ $hadith->hadith_number }}</span>
                            </div>
                        </div>

                        <!-- Main Content: Hadith Text -->
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.hadith.show', $hadith) }}" class="group/text block">
                                <p class="text-base sm:text-lg font-serif leading-relaxed line-clamp-3 transition-colors duration-200"
                                   :class="darkMode ? 'text-gray-100 group-hover/text:text-[#64CCC5]' : 'text-gray-900 group-hover/text:text-[#0056b3]'"
                                   title="{{ $hadith->translations->where('language_code', 'ar')->first()?->text ?? '' }}">
                                    {{ Str::limit($hadith->translations->where('language_code', 'ar')->first()?->text ?? '—', 250) }}
                                </p>
                            </a>

                            <!-- Bottom Info Row -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mt-4 sm:mt-3 items-start sm:items-center flex-wrap">
                                <!-- Collection Info (Bottom Left) -->
                                <div class="text-xs sm:text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    <span class="font-semibold">{{ $hadith->book?->collection?->name_ar ?? '—' }}</span>
                                    @if($hadith->book?->title_ar)
                                        <span class="mx-2">•</span>
                                        <span>{{ $hadith->book->title_ar }}</span>
                                    @endif
                                </div>

                                <!-- Grade Badge -->
                                <div>
                                    @if($hadith->grading_status)
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap transition-all"
                                              :class="darkMode ? 'bg-emerald-900/30 text-emerald-300 border border-emerald-800' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'">
                                            <i data-lucide="star" class="w-3 h-3 mr-1"></i>
                                            {{ $hadith->grading_status }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full"
                                              :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                            <i data-lucide="help-circle" class="w-3 h-3 mr-1"></i>
                                            {{ __('admin.ungraded') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions (Right Side) -->
                        <div class="flex gap-2 sm:gap-1 flex-shrink-0 w-full sm:w-auto sm:justify-end order-first sm:order-last">
                            <!-- View Button -->
                            <a href="{{ route('admin.hadith.show', $hadith) }}"
                               class="flex-1 sm:flex-none p-2.5 rounded-lg transition-all duration-200 group/btn"
                               :class="darkMode ? 'text-blue-400 hover:bg-blue-900/30 bg-blue-900/10' : 'text-blue-600 hover:bg-blue-100 bg-blue-50'"
                               title="{{ __('admin.view') }}">
                                <i data-lucide="eye" class="w-5 h-5 mx-auto sm:mx-0 group-hover/btn:scale-110 transition-transform"></i>
                            </a>

                            <!-- Edit Button -->
                            <a href="{{ route('admin.hadith.edit', $hadith) }}"
                               class="flex-1 sm:flex-none p-2.5 rounded-lg transition-all duration-200 group/btn"
                               :class="darkMode ? 'text-amber-400 hover:bg-amber-900/30 bg-amber-900/10' : 'text-amber-600 hover:bg-amber-100 bg-amber-50'"
                               title="{{ __('admin.edit') }}">
                                <i data-lucide="edit-3" class="w-5 h-5 mx-auto sm:mx-0 group-hover/btn:scale-110 transition-transform"></i>
                            </a>

                            <!-- Delete Button -->
                            <form action="{{ route('admin.hadith.destroy', $hadith) }}" method="POST" class="flex-1 sm:flex-none inline-block"
                                  @click.stop="event.preventDefault()" @submit.prevent="
                                    if (confirm('{{ __('admin.confirm_delete') }}')) {
                                        this.submit();
                                    }
                                  ">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full p-2.5 rounded-lg transition-all duration-200 group/btn"
                                        :class="darkMode ? 'text-red-400 hover:bg-red-900/30 bg-red-900/10' : 'text-red-600 hover:bg-red-100 bg-red-50'"
                                        title="{{ __('admin.delete') }}">
                                    <i data-lucide="trash-2" class="w-5 h-5 mx-auto sm:mx-0 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="p-12 rounded-lg text-center" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.no_hadiths_found') }}</p>
            </div>
        @endif

        <!-- Pagination -->
        <div class="mt-8">
            {{ $hadiths->links() }}
        </div>
    </div>
</div>

<style>
    .hadith-row {
        backdrop-filter: blur(10px);
    }

    .hadith-row:hover {
        transform: scale(1.01);
    }

    /* Refined Typography for Hadith Text */
    .font-serif {
        font-family: Georgia, 'Garamond', serif;
        letter-spacing: 0.3px;
    }

    /* Smooth Transitions */
    * {
        transition: background-color 0.2s, border-color 0.2s, color 0.2s, transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Badge Pulse Animation */
    @keyframes badgePulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.4);
        }
        50% {
            box-shadow: 0 0 0 6px rgba(0, 86, 179, 0);
        }
    }

    /* Mobile Responsive */
    @media (max-width: 640px) {
        .hadith-row {
            padding: 1rem;
        }

        .hadith-row .flex-col {
            flex-direction: column;
        }
    }

    /* Responsive Button Layout */
    @media (min-width: 640px) {
        .hadith-row .flex-1.sm\\:flex-none {
            flex: none;
        }
    }
</style>

<script>
    lucide.createIcons();
</script>
@endsection
