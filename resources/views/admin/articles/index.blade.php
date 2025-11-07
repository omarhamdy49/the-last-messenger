@extends('layouts.admin')

@section('title', 'Articles')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Articles</h1>
            <p class="text-gray-600 mt-1">Manage your articles and content</p>
        </div>
        <a href="{{ route('admin.articles.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
            + New Article
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="flex gap-4">
            <select name="category_id" class="border border-gray-300 rounded-lg px-4 py-2">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name['en'] ?? $category->name['ar'] ?? 'Untitled' }}
                    </option>
                @endforeach
            </select>

            <button type="submit" name="featured" value="1" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg {{ request('featured') ? 'bg-blue-100 text-blue-700' : '' }}">
                Featured Only
            </button>

            <button type="submit" name="pinned" value="1" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg {{ request('pinned') ? 'bg-green-100 text-green-700' : '' }}">
                Pinned Only
            </button>

            <a href="{{ route('admin.articles.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Clear Filters
            </a>
        </form>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Articles Table --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Writer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($articles as $article)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $article->id }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $article->locales->first()->title ?? 'No title' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $article->read_time }} min read
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $article->writer->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $article->category->name['en'] ?? $article->category->name['ar'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ number_format($article->views) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1">
                                @if($article->is_featured)
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Featured</span>
                                @endif
                                @if($article->is_pinned)
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Pinned</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="text-blue-600 hover:text-blue-800">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No articles found. <a href="{{ route('admin.articles.create') }}" class="text-blue-600 hover:underline">Create your first article</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $articles->links() }}
    </div>
</div>
@endsection
