@extends('layouts.admin')

@section('title', 'Create Article')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create New Article</h1>
        <p class="text-gray-600 mt-1">Add a new article with multi-language support</p>
    </div>

    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Main Article Details --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4">Article Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Thumbnail --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail Image</label>
                    <input type="file" name="thumb" accept="image/*" class="block w-full text-sm text-gray-500" />
                </div>

                {{-- Read Time --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Read Time (minutes)</label>
                    <input type="number" name="read_time" value="{{ old('read_time', 5) }}" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2" required />
                </div>

                {{-- Hijri Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hijri Date</label>
                    <input type="text" name="hijri_date" value="{{ old('hijri_date') }}" placeholder="e.g., 1 Muharram 1446" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
                </div>

                {{-- Flags --}}
                <div class="col-span-2 flex gap-6">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Featured Article</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4" />
                        <span class="text-sm font-medium text-gray-700">Pinned Article</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Arabic Content (Required) --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-blue-600">Arabic Content (AR) *</h2>

            <input type="hidden" name="locales[0][locale]" value="ar" />

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" name="locales[0][title]" value="{{ old('locales.0.title') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                    <textarea name="locales[0][excerpt]" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old('locales.0.excerpt') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Body Content</label>
                    <textarea name="locales[0][body]" rows="10" class="w-full border border-gray-300 rounded-lg px-4 py-2">{{ old('locales.0.body') }}</textarea>
                </div>

                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="locales[0][is_ready]" value="1" checked class="w-4 h-4" />
                    <span class="text-sm font-medium text-gray-700">Translation is ready</span>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.articles.index') }}" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                Create Article
            </button>
        </div>
    </form>
</div>
@endsection
