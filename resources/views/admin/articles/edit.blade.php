@extends('layouts.admin')

@section('title', 'Edit Article')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Article #{{ $article->id }}</h1>
        <p class="text-gray-600 mt-1">Update article content and settings</p>
    </div>

    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Main Article Details --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4">Article Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Current Thumbnail --}}
                @if($article->thumb)
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Thumbnail</label>
                        <img src="{{ asset('storage/' . $article->thumb) }}" alt="Thumbnail" class="h-32 w-auto rounded-lg" />
                    </div>
                @endif

                {{-- New Thumbnail --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Change Thumbnail</label>
                    <input type="file" name="thumb" accept="image/*" class="block w-full text-sm text-gray-500" />
                </div>

                {{-- Read Time --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Read Time (minutes)</label>
                    <input type="number" name="read_time" value="{{ old('read_time', $article->read_time) }}" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2" required />
                </div>

                {{-- Hijri Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hijri Date</label>
                    <input type="text" name="hijri_date" value="{{ old('hijri_date', $article->hijri_date) }}" placeholder="e.g., 1 Muharram 1446" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
                </div>

                {{-- Views (Read-only) --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Views</label>
                    <input type="text" value="{{ number_format($article->views) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50" readonly />
                </div>

                {{-- Flags --}}
                <div class="col-span-2 flex gap-6">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ $article->is_featured ? 'checked' : '' }} class="w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Featured Article</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" {{ $article->is_pinned ? 'checked' : '' }} class="w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Pinned Article</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Locales --}}
        @foreach($article->locales as $index => $locale)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-600">{{ strtoupper($locale->locale) }}</h2>

                <input type="hidden" name="locales[{{ $index }}][id]" value="{{ $locale->id }}" />
                <input type="hidden" name="locales[{{ $index }}][locale]" value="{{ $locale->locale }}" />

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                        <input type="text" name="locales[{{ $index }}][title]" value="{{ old("locales.{$index}.title", $locale->title) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                        <textarea name="locales[{{ $index }}][excerpt]" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old("locales.{$index}.excerpt", $locale->excerpt) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Body Content</label>
                        <textarea name="locales[{{ $index }}][body]" rows="10" class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old("locales.{$index}.body", $locale->getBody()) }}</textarea>
                    </div>

                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="locales[{{ $index }}][is_ready]" value="1" {{ $locale->is_ready ? 'checked' : '' }} class="w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Translation is ready</span>
                    </label>
                </div>
            </div>
        @endforeach

        {{-- Actions --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.articles.index') }}" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                Update Article
            </button>
        </div>
    </form>
</div>
@endsection
