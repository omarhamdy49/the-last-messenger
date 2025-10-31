@extends('layouts.admin')

@section('title', 'Series Details')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Series Details
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                View and manage series information
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.series.edit', $series->id) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="edit" class="w-5 h-5"></i>
                <span>Edit</span>
            </a>
            <form action="{{ route('admin.series.destroy', $series->id) }}" method="POST" class="inline"
                  onsubmit="return confirm('Are you sure you want to delete this series?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-all flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Series Information -->
    <div class="glass rounded-lg p-8 mb-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Thumbnail -->
            @if($series->thumbnail)
                <div>
                    <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        Thumbnail
                    </h3>
                    <img src="{{ $series->thumbnail }}" alt="Series thumbnail"
                         class="w-full max-w-md rounded-lg shadow-lg">
                </div>
            @endif

            <!-- Details -->
            <div>
                <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Information
                </h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Title (English)</p>
                        <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $series->title['en'] ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Title (Arabic)</p>
                        <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $series->title['ar'] ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Articles Count</p>
                        <p class="text-lg font-bold text-[#0056b3]">
                            {{ $series->articles_count ?? 0 }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Display Order</p>
                        <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $series->display_order ?? 0 }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Created At</p>
                        <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $series->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if($series->description && ($series->description['en'] || $series->description['ar']))
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Description
                </h3>
                @if($series->description['en'])
                    <div class="mb-4">
                        <p class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">English</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $series->description['en'] }}</p>
                    </div>
                @endif
                @if($series->description['ar'])
                    <div>
                        <p class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Arabic</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="rtl">{{ $series->description['ar'] }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Articles in Series -->
    <div class="glass rounded-lg overflow-hidden transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="p-6 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Articles in This Series ({{ $series->articles_count ?? 0 }})
            </h3>
        </div>

        @if($series->articles && $series->articles->count() > 0)
            <table class="w-full">
                <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">#</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Title</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Author</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($series->articles as $index => $article)
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $article->title['en'] ?? $article->title['ar'] ?? 'Untitled' }}
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $article->author->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    @if($article->status->value === 'published') bg-green-100 text-green-800
                                    @elseif($article->status->value === 'draft') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $article->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.articles.edit', $article->id) }}"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm"
                                   title="Edit Article">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center">
                <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No articles in this series yet
                </p>
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
