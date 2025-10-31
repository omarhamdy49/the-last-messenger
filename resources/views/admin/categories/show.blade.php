@extends('layouts.admin')

@section('title', 'Category Details')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Category Details
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                View category information and relationships
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.categories.edit', $category->id) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all">
                <i data-lucide="edit" class="w-4 h-4 inline ml-2"></i>
                Edit
            </a>
            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Are you sure you want to delete this category?')"
                        class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-all">
                    <i data-lucide="trash-2" class="w-4 h-4 inline ml-2"></i>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Breadcrumb showing parent path -->
    @if($category->parent)
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 space-x-reverse">
                <li><a href="{{ route('admin.categories.index') }}" class="text-[#0056b3] hover:text-blue-700">Categories</a></li>
                <li><i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i></li>
                @if($category->parent->parent)
                    <li><a href="{{ route('admin.categories.show', $category->parent->parent->id) }}" class="text-[#0056b3] hover:text-blue-700">{{ is_array($category->parent->parent->name) ? ($category->parent->parent->name['en'] ?? $category->parent->parent->name['ar']) : $category->parent->parent->name }}</a></li>
                    <li><i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i></li>
                @endif
                <li><a href="{{ route('admin.categories.show', $category->parent->id) }}" class="text-[#0056b3] hover:text-blue-700">{{ is_array($category->parent->name) ? ($category->parent->name['en'] ?? $category->parent->name['ar']) : $category->parent->name }}</a></li>
                <li><i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i></li>
                <li><span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ is_array($category->name) ? ($category->name['en'] ?? $category->name['ar']) : $category->name }}</span></li>
            </ol>
        </nav>
    @endif

    <!-- Category Information -->
    <div class="glass-light rounded-lg p-8 mb-6" :class="darkMode ? 'glass-dark' : ''">
        <div class="flex items-start gap-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 rounded-lg flex items-center justify-center"
                     style="background-color: {{ $category->color ?? '#0056b3' }}">
                    <i data-lucide="{{ $category->icon ?? 'folder' }}" class="w-10 h-10 text-white"></i>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ is_array($category->name) ? ($category->name['en'] ?? $category->name['ar']) : $category->name }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Type</p>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold mt-1 inline-block"
                              :class="'{{ $category->type->value }}' === 'blog' ? 'bg-blue-100 text-blue-800' : '{{ $category->type->value }}' === 'media' ? 'bg-green-100 text-green-800' : 'bg-teal-100 text-teal-800'">
                            {{ $category->type->label() }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Display Order</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $category->display_order ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Children Count</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $category->children_count ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Articles Count</p>
                        <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $category->articles_count ?? 0 }}</p>
                    </div>
                </div>

                <!-- All Language Names -->
                <div class="border-t pt-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h3 class="text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Names in All Languages</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach(['ar' => 'Arabic', 'en' => 'English', 'fr' => 'French', 'tr' => 'Turkish', 'ur' => 'Urdu'] as $lang => $label)
                            @php
                                $nameValue = is_array($category->name) ? ($category->name[$lang] ?? null) : null;
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

                <!-- Description -->
                @php
                    $descValue = is_array($category->description) ? ($category->description['en'] ?? $category->description['ar'] ?? null) : $category->description;
                @endphp
                @if(!empty($descValue))
                    <div class="border-t pt-4 mt-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        <h3 class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Description</h3>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $descValue }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Child Categories -->
    @if($category->children && $category->children->count() > 0)
        <div class="glass-light rounded-lg p-6 mb-6" :class="darkMode ? 'glass-dark' : ''">
            <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Child Categories ({{ $category->children->count() }})
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Name</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Type</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Children</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Articles</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->children as $child)
                            <tr class="border-t" :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                                <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <i data-lucide="{{ $child->icon ?? 'folder' }}" class="w-4 h-4 inline ml-2"></i>
                                    {{ is_array($child->name) ? ($child->name['en'] ?? $child->name['ar']) : $child->name }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                          :class="'{{ $child->type->value }}' === 'blog' ? 'bg-blue-100 text-blue-800' : '{{ $child->type->value }}' === 'media' ? 'bg-green-100 text-green-800' : 'bg-teal-100 text-teal-800'">
                                        {{ $child->type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $child->children_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $child->articles_count ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.categories.show', $child->id) }}" class="text-[#0056b3] hover:text-blue-700 text-sm font-semibold ml-3">View</a>
                                    <a href="{{ route('admin.categories.edit', $child->id) }}" class="text-[#64CCC5] hover:text-teal-700 text-sm font-semibold ml-3">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Articles using this category -->
    @if($category->articles && $category->articles->count() > 0)
        <div class="glass-light rounded-lg p-6" :class="darkMode ? 'glass-dark' : ''">
            <h3 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Articles in this Category ({{ $category->articles->count() }})
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
                        @foreach($category->articles as $article)
                            <tr class="border-t" :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                                <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ is_array($article->title) ? ($article->title['en'] ?? $article->title['ar']) : $article->title }}
                                </td>
                                <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $article->author->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                          :class="'{{ $article->status }}' === 'published' ? 'bg-green-100 text-green-800' : '{{ $article->status }}' === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'">
                                        {{ ucfirst($article->status) }}
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
