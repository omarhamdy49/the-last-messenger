@extends('layouts.admin')

@section('title', 'Tag Details')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Tag Details
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                View tag information and usage statistics
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.tags.edit', $tag->id) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i data-lucide="edit" class="w-4 h-4 inline ml-2"></i>
                Edit
            </a>
            <form action="{{ route('admin.tags.destroy', $tag->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Are you sure you want to delete this tag?')"
                        class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4 inline ml-2"></i>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Tag Information -->
    <div class="glass-light rounded-lg p-8 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="flex items-start gap-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                    <i data-lucide="tag" class="w-10 h-10 text-white"></i>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ is_array($tag->name) ? ($tag->name['en'] ?? $tag->name['ar']) : $tag->name }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Type</p>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold mt-1 inline-block"
                              :class="'{{ (string) $tag->type }}' === 'article' ? 'bg-blue-100 text-blue-800' : '{{ (string) $tag->type }}' === 'hadith' ? 'bg-green-100 text-green-800' : '{{ (string) $tag->type }}' === 'program' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'">
                            {{ ucfirst((string) $tag->type) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Slug</p>
                        <p class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $tag->slug }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Created At</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $tag->created_at?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- All Language Names -->
                <div class="border-t pt-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h3 class="text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Names in All Languages</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach(['ar' => 'Arabic', 'en' => 'English', 'fr' => 'French', 'tr' => 'Turkish', 'ur' => 'Urdu'] as $lang => $label)
                            @php
                                $nameValue = is_array($tag->name) ? ($tag->name[$lang] ?? null) : null;
                            @endphp
                            @if(!empty($nameValue))
                                <div>
                                    <span class="text-xs font-semibold" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $label }}:</span>
                                    <p :class="darkMode ? 'text-white' : 'text-gray-900'" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">{{ $nameValue }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Total Uses</p>
            <p class="text-4xl font-bold mt-2 text-[#0056b3]">{{ $tag->usage_count ?? 0 }}</p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Articles</p>
            <p class="text-4xl font-bold mt-2 text-[#218838]">{{ $tag->articles_count ?? 0 }}</p>
        </div>
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Last Used</p>
            <p class="text-lg font-bold mt-2 text-[#64CCC5]">{{ $tag->updated_at?->diffForHumans() ?? 'Never' }}</p>
        </div>
    </div>

    <!-- Merge Tag Option -->
    <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="git-merge" class="w-5 h-5 inline ml-2"></i>
                    Merge This Tag
                </h3>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Merge this tag with another tag. All items will be moved to the destination tag.
                </p>
            </div>
            <a href="{{ route('admin.tags.index') }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                Go to Tags List
            </a>
        </div>
    </div>

    <!-- Articles using this tag -->
    @if($tag->articles && $tag->articles->count() > 0)
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Articles Using This Tag ({{ $tag->articles->count() }})
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Title</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Author</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Status</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Date</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tag->articles->take(50) as $article)
                            <tr class="border-t" :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                                <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ is_array($article->title) ? ($article->title['en'] ?? $article->title['ar']) : $article->title }}
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $article->author->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                          :class="'{{ $article->status->value }}' === 'published' ? 'bg-green-100 text-green-800' : '{{ $article->status->value }}' === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'">
                                        {{ $article->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $article->published_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.articles.edit', $article->id) }}" class="text-[#0056b3] hover:text-blue-700 text-sm font-semibold ml-3">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($tag->articles->count() > 50)
                    <div class="mt-4 text-center">
                        <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Showing first 50 articles. Total: {{ $tag->articles->count() }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="glass-light rounded-lg p-8 text-center" :class="darkMode ? 'glass-dark' : ''">
            <div class="max-w-md mx-auto">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-text" class="w-10 h-10" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    No Articles Yet
                </h3>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    This tag hasn't been used in any articles yet.
                </p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
