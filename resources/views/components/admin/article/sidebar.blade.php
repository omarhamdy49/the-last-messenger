{{--
    Article Form Sidebar Component
    Contains: Thumbnail, Publication Settings, Writer, Categories, Tags, Options
--}}

@props([
    'article' => null,
    'formArticle' => null,
    'categories' => [],
    'writers' => [],
    'tags' => [],
    'currentHijriDate' => '',
])

@php
    $isEdit = $article && $article->exists;
@endphp

<aside class="w-full lg:w-80 xl:w-96 space-y-4 order-2 lg:order-1">

    {{-- Thumbnail --}}
    <div class="sidebar-card rounded-xl p-4"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <h3 class="text-sm font-semibold mb-3 flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.fields.thumb') }}
        </h3>
        <div class="relative aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden mb-3">
            <template x-if="thumbnailPreview">
                <img :src="thumbnailPreview" class="w-full h-full object-cover" alt="Thumbnail">
            </template>
            <template x-if="!thumbnailPreview">
                <div class="flex items-center justify-center h-full text-gray-400">
                    <i data-lucide="image-plus" class="w-10 h-10"></i>
                </div>
            </template>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <button type="button"
                    @click="$dispatch('open-media-library', { target: 'thumbnail', multiple: false })"
                    class="px-3 py-2 text-xs rounded-lg border transition-colors text-center"
                    :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                <i data-lucide="folder-open" class="w-3.5 h-3.5 inline me-1"></i>
                {{ __('articles.fields.select_thumbnail') }}
            </button>
            <label class="px-3 py-2 text-xs rounded-lg border transition-colors cursor-pointer text-center"
                   :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                <i data-lucide="upload" class="w-3.5 h-3.5 inline me-1"></i>
                {{ __('common.upload') }}
                <input type="file" name="thumb" accept="image/jpeg,image/png,image/webp" @change="handleThumbnailUpload" class="hidden">
            </label>
        </div>
        <input type="hidden" name="thumb_id" x-model="formData.thumb_id">
    </div>

    {{-- Publication --}}
    <div class="sidebar-card rounded-xl p-4 space-y-3"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <h3 class="text-sm font-semibold flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.sidebar.publication') }}
        </h3>

        {{-- Status --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.status') }}
            </label>
            <select name="status" x-model="formData.status"
                    class="w-full px-3 py-2 rounded-lg border text-sm"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="draft">{{ __('articles.status.draft') }}</option>
                <option value="pending">{{ __('articles.status.pending') }}</option>
                <option value="published">{{ __('articles.status.published') }}</option>
                <option value="scheduled">{{ __('articles.status.scheduled') }}</option>
                <option value="archived">{{ __('articles.status.archived') }}</option>
            </select>
        </div>

        {{-- Scheduled Date --}}
        <div x-show="formData.status === 'scheduled'" x-transition x-cloak>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.scheduled_at') }}
            </label>
            <input type="datetime-local" name="scheduled_at" x-model="formData.scheduled_at"
                   class="w-full px-3 py-2 rounded-lg border text-sm"
                   :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>

        {{-- Hijri Date (calendar picker) --}}
        <div>
            <x-admin.hijri-date-picker
                name="hijri_date"
                :label="__('articles.fields.hijri_date')"
                :value="$formArticle['hijri_date'] ?? $currentHijriDate" />
        </div>

        {{-- Read Time --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.read_time_minutes') }}
            </label>
            <input type="number" name="read_time" x-model="formData.read_time" min="1" max="999"
                   class="w-full px-3 py-2 rounded-lg border text-sm"
                   :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>

        {{-- Views (edit only) --}}
        @if($isEdit)
        <div>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.views') }}
            </label>
            <div class="px-3 py-2 rounded-lg border text-sm flex items-center gap-2"
                 :class="darkMode ? 'bg-gray-900 border-gray-600' : 'bg-gray-50 border-gray-300'">
                <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ number_format($article->views ?? 0) }}
                </span>
            </div>
        </div>
        @endif
    </div>

    {{-- Writer --}}
    <div class="sidebar-card rounded-xl p-4"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <label class="block text-sm font-semibold mb-2 flex items-center gap-2"
               :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.fields.writer') }}
        </label>
        <select name="writer_id" x-model="formData.writer_id"
                class="select2-writer w-full px-3 py-2 rounded-lg border text-sm"
                :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
            <option value="">{{ __('articles.placeholders.select_writer') }}</option>
            @foreach($writers as $writer)
                <option value="{{ $writer->id }}"
                        {{ old('writer_id', $article ? $article->writer_id : '') == $writer->id ? 'selected' : '' }}>
                    {{ $writer->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Categories --}}
    <div class="sidebar-card rounded-xl p-4 space-y-3"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <h3 class="text-sm font-semibold flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="folder" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.fields.categories') }}
        </h3>

        {{-- Primary Category --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.primary_category') }}
            </label>
            <select name="category_id" x-model="formData.category_id"
                    class="select2-category w-full px-3 py-2 rounded-lg border text-sm"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('articles.placeholders.select_category') }}</option>
                @foreach($categories as $catId => $catName)
                    <option value="{{ $catId }}"
                            {{ old('category_id', $article ? $article->category_id : '') == $catId ? 'selected' : '' }}>
                        {{ $catName }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Additional Categories --}}
        <div>
            <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('articles.fields.additional_categories') }}
            </label>
            <select name="additional_categories[]" multiple
                    class="select2-categories w-full px-3 py-2 rounded-lg border text-sm"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                @foreach($categories as $catId => $catName)
                    <option value="{{ $catId }}"
                            {{ in_array($catId, old('additional_categories', $article && $article->categories ? $article->categories->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                        {{ $catName }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tags --}}
    <div class="sidebar-card rounded-xl p-4"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <label class="block text-sm font-semibold mb-2 flex items-center gap-2"
               :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="tag" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.fields.tags') }}
        </label>
        <select name="tags[]" multiple
                class="select2-tags w-full px-3 py-2 rounded-lg border text-sm"
                :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
            @foreach($tags as $tag)
                <option value="{{ $tag->id }}"
                        {{ in_array($tag->id, old('tags', $article && $article->tags ? $article->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                    {{ $tag->getTranslation('name') ?? $tag->slug }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Options --}}
    <div class="sidebar-card rounded-xl p-4 space-y-3"
         :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
        <h3 class="text-sm font-semibold flex items-center gap-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
            {{ __('articles.sidebar.settings') }}
        </h3>
        <label class="flex items-center justify-between cursor-pointer">
            <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                {{ __('articles.fields.is_featured') }}
            </span>
            <input type="checkbox" name="is_featured" value="1" x-model="formData.is_featured"
                   class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
        </label>
        <label class="flex items-center justify-between cursor-pointer">
            <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                {{ __('articles.fields.is_pinned') }}
            </span>
            <input type="checkbox" name="is_pinned" value="1" x-model="formData.is_pinned"
                   class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
        </label>
    </div>

</aside>
