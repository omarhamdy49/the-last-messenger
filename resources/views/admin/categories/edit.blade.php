@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeLanguage: 'en', darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Edit Category
        </h1>
        <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Update category information
        </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.categories.update', $category->id) }}" method="POST"
          class="glass-light rounded-lg p-8 max-w-4xl" :class="darkMode ? 'glass-dark' : ''">
        @csrf
        @method('PUT')

        <!-- Language Tabs -->
        <div class="flex gap-2 mb-6 border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'"
                        :class="activeLanguage === '{{ $lang }}' ? 'border-b-2 border-[#0056b3] font-bold' : ''"
                        class="px-4 py-2 transition"
                        :class="darkMode ? 'text-white hover:text-[#64CCC5]' : 'text-gray-700 hover:text-[#0056b3]'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Multi-language Fields -->
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <div x-show="activeLanguage === '{{ $lang }}'" class="space-y-6">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Name ({{ strtoupper($lang) }}) <span class="text-red-600">*</span>
                    </label>
                    <input type="text"
                           name="name[{{ $lang }}]"
                           value="{{ old("name.$lang", $category->name[$lang] ?? '') }}"
                           {{ in_array($lang, ['ar', 'en']) ? 'required' : '' }}
                           class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error("name.$lang") border-red-500 @enderror"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error("name.$lang")
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Field -->
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Description ({{ strtoupper($lang) }})
                    </label>
                    <textarea name="description[{{ $lang }}]"
                              rows="4"
                              class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old("description.$lang", $category->description[$lang] ?? '') }}</textarea>
                </div>
            </div>
        @endforeach

        <!-- Info Message -->
        @if($category->children_count > 0)
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-r-4 border-yellow-500 text-yellow-800 dark:text-yellow-300 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 ml-3"></i>
                    <span class="font-semibold">Warning: This category has {{ $category->children_count }} child categories</span>
                </div>
            </div>
        @endif

        @if($category->articles_count > 0)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-r-4 border-blue-500 text-blue-800 dark:text-blue-300 rounded-lg mb-6">
                <div class="flex items-center">
                    <i data-lucide="info" class="w-5 h-5 ml-3"></i>
                    <span class="font-semibold">This category is used in {{ $category->articles_count }} articles</span>
                </div>
            </div>
        @endif

        <!-- Other Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Type -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Type <span class="text-red-600">*</span>
                </label>
                <select name="type"
                        required
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] @error('type') border-red-500 @enderror"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">Select Type</option>
                    <option value="blog" {{ old('type', $category->type) === 'blog' ? 'selected' : '' }}>Blog</option>
                    <option value="media" {{ old('type', $category->type) === 'media' ? 'selected' : '' }}>Media</option>
                    <option value="timeline" {{ old('type', $category->type) === 'timeline' ? 'selected' : '' }}>Timeline</option>
                </select>
                @error('type')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Parent Category -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Parent Category
                </label>
                <select name="parent_id"
                        class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">None (Root Category)</option>
                    {{-- Categories will be loaded here dynamically or via blade --}}
                </select>
                <p class="text-xs mt-1 text-yellow-600">
                    <i data-lucide="alert-triangle" class="w-3 h-3 inline"></i>
                    Changing parent may affect category hierarchy
                </p>
            </div>

            <!-- Icon -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Icon (Lucide Icon Name)
                </label>
                <input type="text"
                       name="icon"
                       value="{{ old('icon', $category->icon ?? 'folder') }}"
                       placeholder="folder, file-text, layers..."
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    Visit lucide.dev/icons for icon names
                </p>
            </div>

            <!-- Color -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Color
                </label>
                <input type="color"
                       name="color"
                       value="{{ old('color', $category->color ?? '#0056b3') }}"
                       class="w-full h-10 px-2 py-1 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-300'">
                <div class="flex gap-2 mt-2">
                    <button type="button" onclick="document.querySelector('[name=color]').value='#0056b3'" class="w-8 h-8 rounded-full bg-[#0056b3] border-2 border-gray-300"></button>
                    <button type="button" onclick="document.querySelector('[name=color]').value='#218838'" class="w-8 h-8 rounded-full bg-[#218838] border-2 border-gray-300"></button>
                    <button type="button" onclick="document.querySelector('[name=color]').value='#64CCC5'" class="w-8 h-8 rounded-full bg-[#64CCC5] border-2 border-gray-300"></button>
                    <button type="button" onclick="document.querySelector('[name=color]').value='#dc3545'" class="w-8 h-8 rounded-full bg-[#dc3545] border-2 border-gray-300"></button>
                    <button type="button" onclick="document.querySelector('[name=color]').value='#6f42c1'" class="w-8 h-8 rounded-full bg-[#6f42c1] border-2 border-gray-300"></button>
                    <button type="button" onclick="document.querySelector('[name=color]').value='#ffc107'" class="w-8 h-8 rounded-full bg-[#ffc107] border-2 border-gray-300"></button>
                </div>
            </div>

            <!-- Display Order -->
            <div>
                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Display Order
                </label>
                <input type="number"
                       name="display_order"
                       value="{{ old('display_order', $category->display_order ?? 0) }}"
                       min="0"
                       class="w-full px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4 mt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                <i data-lucide="check" class="w-4 h-4 inline ml-2"></i>
                Update Category
            </button>
            <a href="{{ route('admin.categories.index') }}"
               class="px-8 py-3 rounded-lg font-semibold border transition"
               :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-4 h-4 inline ml-2"></i>
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
