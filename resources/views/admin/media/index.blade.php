@extends('layouts.admin')

@section('title', __('media.library_page.title'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
@endpush

@section('page-header')
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.25em]"
               :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                {{ __('media.library_page.kicker') }}
            </p>
            <h1 class="text-3xl font-black mt-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('media.library_page.title') }}
            </h1>
            <p class="mt-2 text-sm max-w-2xl"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ __('media.library_page.subtitle') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.media-library.settings') }}"
               class="px-5 py-3 rounded-xl font-semibold transition-all"
            :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                <i data-lucide="settings-2" class="w-4 h-4 inline me-2"></i>
                {{ __('media.library_page.settings') }}
            </a>

            <button @click="$dispatch('open-media-upload')"
                    class="px-5 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg shadow-[#0056b3]/20 hover:scale-[1.02] transition-all">
                <i data-lucide="upload-cloud" class="w-4 h-4 inline me-2"></i>
                {{ __('media.library_page.upload_media') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="mediaLibraryPage()" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <template x-for="card in statCards" :key="card.label">
                <div class="rounded-2xl p-5 shadow-lg"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em]"
                               :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                               x-text="card.label"></p>
                            <p class="text-3xl font-black mt-3"
                               :class="darkMode ? 'text-white' : 'text-gray-900'"
                               x-text="card.value"></p>
                        </div>
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center"
                             :class="card.iconBg">
                            <i :data-lucide="card.icon" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="rounded-2xl shadow-lg p-4"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                        <input type="text"
                               x-model="filters.search"
                               @input="debounceSearch()"
                               placeholder="{{ __('media.library_page.search_placeholder') }}"
                               class="w-full ps-12 pe-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                               :class="darkMode ? 'bg-gray-950 border-gray-800 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                    </div>

                    <button @click="showFilters = !showFilters"
                            class="lg:hidden px-4 py-3 rounded-xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="filter" class="w-4 h-4 inline me-2"></i>
                        {{ __('media.library_page.filters') }}
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex p-1 rounded-xl"
                         :class="darkMode ? 'bg-gray-950 border border-gray-800' : 'bg-gray-100 border border-gray-200'">
                        <button @click="viewMode = 'grid'"
                                class="px-3 py-2 rounded-lg transition-all"
                                :class="viewMode === 'grid'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                            <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                        </button>
                        <button @click="viewMode = 'list'"
                                class="px-3 py-2 rounded-lg transition-all"
                                :class="viewMode === 'list'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                            <i data-lucide="list" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <select x-model="filters.sort"
                            @change="applyFilters()"
                            class="px-4 py-3 rounded-xl border"
                            :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="created_at">{{ __('media.library_page.sort_newest') }}</option>
                        <option value="updated_at">{{ __('media.library_page.sort_recently_updated') }}</option>
                        <option value="size">{{ __('media.library_page.sort_largest_size') }}</option>
                        <option value="filename">{{ __('media.library_page.sort_stored_filename') }}</option>
                        <option value="original_filename">{{ __('media.library_page.sort_original_filename') }}</option>
                    </select>

                    <button x-show="selectedItems.length > 0"
                            @click="bulkDownload()"
                            class="px-4 py-3 rounded-xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="download" class="w-4 h-4 inline me-2"></i>
                        {{ __('media.actions.download') }}
                    </button>

                    <button x-show="selectedItems.length > 0"
                            @click="bulkTag()"
                            class="px-4 py-3 rounded-xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="tags" class="w-4 h-4 inline me-2"></i>
                        {{ __('media.library_page.bulk.tag_selected') }}
                    </button>

                    <button x-show="selectedItems.length > 0"
                            @click="confirmBulkDelete()"
                            class="px-4 py-3 rounded-xl font-semibold bg-red-500 text-white hover:bg-red-600 transition-all">
                        <i data-lucide="trash-2" class="w-4 h-4 inline me-2"></i>
                        {{ __('media.actions.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <x-admin.media-filter />

            <div class="flex-1 space-y-4">
                <div x-show="loading"
                     class="rounded-2xl p-10 text-center shadow-lg"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="inline-flex w-14 h-14 rounded-full border-4 border-[#64CCC5] border-t-transparent animate-spin"></div>
                    <p class="mt-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.library_page.loading') }}</p>
                </div>

                <div x-show="!loading && items.length === 0"
                     class="rounded-2xl p-12 text-center shadow-lg"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="image-off" class="w-10 h-10 text-white"></i>
                    </div>
                    <h3 class="mt-6 text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.library_page.empty_title') }}</h3>
                    <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('media.library_page.empty_description') }}
                    </p>
                </div>

                <div x-show="!loading && items.length > 0 && viewMode === 'grid'"
                     class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    <template x-for="item in items" :key="item.id">
                        <article class="rounded-2xl overflow-hidden shadow-lg transition-all hover:-translate-y-1"
                                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                            <div class="relative aspect-video overflow-hidden"
                                 :class="darkMode ? 'bg-gray-950' : 'bg-gray-100'">
                                <template x-if="item.is_image">
                                    <img :src="item.thumbnail_url || item.src"
                                         :alt="item.title"
                                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                                </template>

                                <template x-if="!item.is_image">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i :data-lucide="item.type === 'video' ? 'video' : (item.type === 'audio' ? 'music-4' : 'file-text')"
                                           class="w-12 h-12"
                                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    </div>
                                </template>

                                <div class="absolute top-3 left-3">
                                    <label class="w-8 h-8 rounded-full bg-black/45 backdrop-blur text-white flex items-center justify-center cursor-pointer">
                                        <input type="checkbox"
                                               class="hidden"
                                               :value="item.id"
                                               x-model="selectedItems">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </label>
                                </div>

                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                          :class="typeBadge(item.type)"
                                          x-text="item.type_label || item.type"></span>
                                </div>
                            </div>

                            <div class="p-5 space-y-4">
                                <div>
                                    <h3 class="font-bold truncate"
                                        :class="darkMode ? 'text-white' : 'text-gray-900'"
                                        x-text="item.title"></h3>
                                    <p class="text-xs mt-1 truncate"
                                       :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                                       x-text="item.original_filename"></p>
                                </div>

                                <div class="flex items-center justify-between text-xs"
                                     :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    <span x-text="item.human_readable_size"></span>
                                    <span x-text="item.width && item.height ? `${item.width}×${item.height}` : '-'"></span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button @click="viewMedia(item)"
                                            class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                        {{ __('media.actions.view') }}
                                    </button>
                                    <button @click="openEdit(item)"
                                            class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                        {{ __('media.actions.edit') }}
                                    </button>
                                    <button x-show="item.is_image"
                                            @click="openCrop(item)"
                                            class="px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                        {{ __('media.actions.crop') }}
                                    </button>
                                    <button @click="deleteMedia(item)"
                                            class="px-3 py-2 rounded-lg text-xs font-semibold bg-red-500 text-white hover:bg-red-600 transition-all">
                                        {{ __('media.actions.delete') }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>

                <div x-show="!loading && items.length > 0 && viewMode === 'list'"
                     class="rounded-2xl shadow-lg overflow-hidden"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr :class="darkMode ? 'bg-gray-950 text-gray-300' : 'bg-gray-50 text-gray-700'">
                                    <th class="px-4 py-4 text-left">
                                        <input type="checkbox"
                                               @change="toggleSelectAll()"
                                               :checked="currentPageSelected()"
                                               class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                    </th>
                                    <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.media') }}</th>
                                    <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.title') }}</th>
                                    <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.collection') }}</th>
                                    <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.size') }}</th>
                                    <th class="px-4 py-4 text-left text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.uploaded') }}</th>
                                    <th class="px-4 py-4 text-right text-xs uppercase tracking-[0.18em]">{{ __('media.library_page.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr class="border-t"
                                        :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                        <td class="px-4 py-4">
                                            <input type="checkbox"
                                                   :value="item.id"
                                                   x-model="selectedItems"
                                                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="w-16 h-16 rounded-xl overflow-hidden"
                                                 :class="darkMode ? 'bg-gray-950' : 'bg-gray-100'">
                                                <template x-if="item.is_image">
                                                    <img :src="item.thumbnail_url || item.src" :alt="item.title" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.is_image">
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <i :data-lucide="item.type === 'video' ? 'video' : (item.type === 'audio' ? 'music-4' : 'file-text')"
                                                           class="w-6 h-6"
                                                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title"></div>
                                            <div class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'" x-text="item.original_filename"></div>
                                        </td>
                                        <td class="px-4 py-4 text-sm capitalize" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="item.collection_name"></td>
                                        <td class="px-4 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="item.human_readable_size"></td>
                                        <td class="px-4 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatDate(item.created_at)"></td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="viewMedia(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="openEdit(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>
                                                <button x-show="item.is_image" @click="openCrop(item)" class="p-2 rounded-lg transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                                                    <i data-lucide="crop" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="deleteMedia(item)" class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="!loading && pagination.total > 0"
                     class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('media.library_page.pagination.showing') }} <span class="font-semibold" x-text="pagination.from || 0"></span>
                        {{ __('admin.to') }} <span class="font-semibold" x-text="pagination.to || 0"></span>
                        {{ __('admin.of') }} <span class="font-semibold" x-text="pagination.total || 0"></span> {{ __('media.library_page.pagination.items') }}
                    </p>

                    <div class="flex items-center gap-2">
                        <button @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                class="px-4 py-2 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                            {{ __('admin.previous') }}
                        </button>
                        <span class="px-4 py-2 rounded-xl"
                              :class="darkMode ? 'bg-gray-900 border border-gray-800 text-gray-300' : 'bg-white border border-gray-200 text-gray-700'">
                            {{ __('media.library_page.pagination.page') }} <span x-text="pagination.current_page || 1"></span> / <span x-text="pagination.last_page || 1"></span>
                        </span>
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="px-4 py-2 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-900 border border-gray-800 text-white hover:bg-gray-800' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                            {{ __('admin.next') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <x-admin.media-upload-modal />

        <div class="fixed inset-x-0 top-4 z-[60] pointer-events-none px-4">
            <div class="mx-auto flex max-w-xl flex-col gap-3">
                <template x-for="toast in toasts" :key="toast.id">
                    <div class="pointer-events-auto rounded-2xl border shadow-2xl overflow-hidden"
                         :class="toast.type === 'error'
                            ? (darkMode ? 'bg-red-950/95 border-red-900 text-red-100' : 'bg-white border-red-200 text-red-900')
                            : (darkMode ? 'bg-gray-900/95 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900')">
                        <div class="flex items-start gap-4 p-4">
                            <div class="mt-0.5 w-10 h-10 rounded-2xl flex items-center justify-center"
                                 :class="toast.type === 'error'
                                    ? 'bg-red-500 text-white'
                                    : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'">
                                <i :data-lucide="toast.type === 'error' ? 'shield-alert' : 'check-check'" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold"
                                   x-text="toast.title || (toast.type === 'error' ? config.i18n.toastErrorTitle : config.i18n.toastSuccessTitle)"></p>
                                <p class="mt-1 text-sm"
                                   :class="toast.type === 'error'
                                      ? (darkMode ? 'text-red-200' : 'text-red-700')
                                      : (darkMode ? 'text-gray-300' : 'text-gray-600')"
                                   x-text="toast.message"></p>
                            </div>
                            <button @click="dismissToast(toast.id)"
                                    type="button"
                                    class="p-2 rounded-lg transition-all"
                                    :class="darkMode ? 'hover:bg-white/10 text-gray-400' : 'hover:bg-gray-100 text-gray-500'">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="confirmDialog.open"
             x-cloak
             class="fixed inset-0 z-[55] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="closeConfirmDialog()"></div>
            <div class="relative w-full max-w-md rounded-3xl overflow-hidden shadow-2xl"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="p-6">
                    <div class="w-16 h-16 rounded-[1.5rem] mx-auto flex items-center justify-center bg-gradient-to-r from-red-500 to-rose-600 text-white shadow-lg shadow-red-500/25">
                        <i data-lucide="trash-2" class="w-8 h-8"></i>
                    </div>
                    <h3 class="mt-5 text-2xl font-black text-center"
                        :class="darkMode ? 'text-white' : 'text-gray-900'"
                        x-text="confirmDialog.title"></h3>
                    <p class="mt-3 text-sm text-center leading-6"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                       x-text="confirmDialog.message"></p>

                    <div class="mt-6 flex items-center justify-center gap-2 flex-wrap">
                        <template x-for="pill in confirmDialog.meta" :key="pill.label">
                            <span class="px-3 py-1.5 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                                <span x-text="pill.label"></span>: <span x-text="pill.value"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 border-t"
                     :class="darkMode ? 'border-gray-800 bg-gray-950' : 'border-gray-200 bg-gray-50'">
                    <button @click="closeConfirmDialog()"
                            type="button"
                            class="px-4 py-3 rounded-2xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-100'">
                        {{ __('admin.cancel') }}
                    </button>
                    <button @click="runConfirmAction()"
                            type="button"
                            class="px-4 py-3 rounded-2xl font-semibold text-white bg-gradient-to-r from-red-500 to-rose-600 shadow-lg shadow-red-500/20 hover:scale-[1.01] transition-all"
                            x-text="confirmDialog.confirmLabel"></button>
                </div>
            </div>
        </div>

        <div x-show="previewModal.open"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80" @click="closePreview()"></div>
            <div class="relative w-full max-w-6xl rounded-2xl overflow-hidden shadow-2xl"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center justify-between px-6 py-4 border-b"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div>
                        <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.preview.title') }}</h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="previewModal.media?.title || ''"></p>
                    </div>
                    <button @click="closePreview()" class="p-2 rounded-lg" :class="darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="min-h-[420px] p-6"
                         :class="darkMode ? 'bg-gray-950' : 'bg-gray-50'">
                        <div class="h-full rounded-2xl overflow-hidden flex items-center justify-center"
                             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                            <template x-if="previewModal.media && previewModal.media.is_image">
                                <img :src="activePreviewSource()?.url || previewModal.media.preview_url || previewModal.media.src"
                                     :alt="previewModal.media.title"
                                     class="max-h-[70vh] max-w-full h-auto w-auto object-contain">
                            </template>

                            <template x-if="previewModal.media && previewModal.media.is_video">
                                <video :src="activePreviewSource()?.url || previewModal.media.src"
                                       controls
                                       class="max-h-[70vh] w-full bg-black"></video>
                            </template>

                            <template x-if="previewModal.media && previewModal.media.is_audio">
                                <div class="w-full max-w-xl px-8 py-10 text-center space-y-6">
                                    <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                                        <i data-lucide="music-4" class="w-10 h-10 text-white"></i>
                                    </div>
                                    <audio :src="activePreviewSource()?.url || previewModal.media.src"
                                           controls
                                           class="w-full"></audio>
                                </div>
                            </template>

                            <template x-if="previewModal.media && previewModal.media.is_document">
                                <iframe :src="activePreviewSource()?.url || previewModal.media.src"
                                        class="h-[70vh] w-full"
                                        :title="previewModal.media.title"></iframe>
                            </template>
                        </div>
                    </div>

                    <aside class="p-6 border-t lg:border-t-0 lg:border-s space-y-5"
                           :class="darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-white'">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em]"
                                :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                {{ __('media.preview.available_sizes') }}
                            </h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <template x-for="source in previewSources(previewModal.media)" :key="source.key">
                                    <button @click="previewModal.activeSource = source.key"
                                            type="button"
                                            class="px-3 py-2 rounded-xl text-sm font-semibold transition-all"
                                            :class="previewModal.activeSource === source.key
                                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                                : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                        <span x-text="source.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.preview.file_name') }}</p>
                                <p class="mt-1 break-all" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="previewModal.media?.original_filename || ''"></p>
                            </div>

                            <div>
                                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.preview.dimensions') }}</p>
                                <p class="mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="activePreviewDimensions()"></p>
                            </div>

                            <div>
                                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.size') }}</p>
                                <p class="mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="activePreviewSize()"></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <a :href="activePreviewSource()?.url || previewModal.media?.src || '#'"
                               target="_blank"
                               rel="noopener"
                               class="px-4 py-3 rounded-xl font-semibold transition-all"
                               :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                {{ __('media.preview.open_original') }}
                            </a>
                            <a :href="previewModal.media?.download_url || '#'"
                               class="px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                                {{ __('media.actions.download') }}
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <div x-show="editModal.open"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" @click="closeEdit()"></div>
            <div class="relative w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center justify-between px-6 py-4 border-b"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div>
                        <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.library_page.edit.title') }}</h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.library_page.edit.subtitle') }}</p>
                    </div>
                    <button @click="closeEdit()" class="p-2 rounded-lg" :class="darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="grid gap-6 lg:grid-cols-[220px_1fr] p-6">
                    <div class="rounded-2xl overflow-hidden"
                         :class="darkMode ? 'bg-gray-950 border border-gray-800' : 'bg-gray-100 border border-gray-200'">
                        <template x-if="editModal.form.is_image">
                            <img :src="editModal.form.preview_url || editModal.form.src" :alt="editModal.form.title" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!editModal.form.is_image">
                            <div class="min-h-[220px] flex items-center justify-center">
                                <i :data-lucide="editModal.form.type === 'video' ? 'video' : (editModal.form.type === 'audio' ? 'music-4' : 'file-text')"
                                   class="w-12 h-12"
                                   :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.title') }}</label>
                            <input type="text"
                                   x-model="editModal.form.title"
                                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                   :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.collection') }}</label>
                                <input type="text"
                                       x-model="editModal.form.collection_name"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.tags') }}</label>
                                <input type="text"
                                       x-model="editModal.form.tagsText"
                                       placeholder="{{ __('media.library_page.edit.tags_placeholder') }}"
                                       class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.alt_text') }}</label>
                            <textarea x-model="editModal.form.alt_text"
                                      rows="3"
                                      class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent resize-none"
                                      :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('media.fields.caption') }}</label>
                            <textarea x-model="editModal.form.caption"
                                      rows="4"
                                      class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent resize-none"
                                      :class="darkMode ? 'bg-gray-950 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-900'"></textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button x-show="editModal.form.is_image"
                                    @click="optimizeMedia(editModal.form)"
                                    class="px-4 py-3 rounded-xl font-semibold transition-all"
                                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                <i data-lucide="sparkles" class="w-4 h-4 inline me-2"></i>
                                {{ __('media.library_page.edit.optimize') }}
                            </button>

                            <button x-show="editModal.form.is_image"
                                    @click="regenerateVariants(editModal.form)"
                                    class="px-4 py-3 rounded-xl font-semibold transition-all"
                                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                                <i data-lucide="refresh-cw" class="w-4 h-4 inline me-2"></i>
                                {{ __('media.library_page.edit.regenerate_sizes') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t"
                     :class="darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">
                    <button @click="closeEdit()"
                            class="px-4 py-3 rounded-xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                        {{ __('admin.cancel') }}
                    </button>
                    <button @click="saveEdit()"
                            class="px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                        {{ __('media.library_page.edit.save') }}
                    </button>
                </div>
            </div>
        </div>

        <div x-show="cropModal.open"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80" @click="closeCrop()"></div>
            <div class="relative w-full max-w-5xl rounded-2xl overflow-hidden shadow-2xl"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center justify-between px-6 py-4 border-b"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div>
                        <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.library_page.crop.title') }}</h2>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.library_page.crop.subtitle') }}</p>
                    </div>
                    <button @click="closeCrop()" class="p-2 rounded-lg" :class="darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6">
                    <div class="max-h-[70vh] overflow-hidden rounded-2xl"
                         :class="darkMode ? 'bg-gray-950' : 'bg-gray-100'">
                        <img x-ref="cropperImage"
                             :src="cropModal.media ? cropModal.media.src : ''"
                             alt="{{ __('media.library_page.crop.target_alt') }}"
                             class="max-w-full">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t"
                     :class="darkMode ? 'border-gray-800 bg-gray-900' : 'border-gray-200 bg-gray-50'">
                    <button @click="closeCrop()"
                            class="px-4 py-3 rounded-xl font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-white border border-gray-200 text-gray-900 hover:bg-gray-50'">
                        {{ __('admin.cancel') }}
                    </button>
                    <button @click="saveCrop()"
                            class="px-4 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold shadow-lg">
                        {{ __('media.library_page.crop.apply') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        window.mediaLibraryConfig = {
            baseUrl: @json(url('/su/portal/v1/admin/media-library')),
            indexUrl: @json(route('admin.media-library.index')),
            statisticsUrl: @json(route('admin.media-library.statistics')),
            collectionsUrl: @json(route('admin.media-library.collections')),
            tagsUrl: @json(route('admin.media-library.tags')),
            bulkDeleteUrl: @json(route('admin.media-library.bulk-delete')),
            bulkUpdateTagsUrl: @json(route('admin.media-library.bulk-update-tags')),
            settingsUrl: @json(route('admin.media-library.settings')),
            openMediaId: @json(request('open')),
            initialFilters: @json($initialFilters),
            initialCollections: @json($collections),
            initialTags: @json($tags),
            initialStats: @json($stats),
            i18n: {
                statTotalFiles: @json(__('media.library_page.stats.total_files')),
                statImages: @json(__('media.library_page.stats.images')),
                statVideosAudio: @json(__('media.library_page.stats.videos_audio')),
                statUnused: @json(__('media.library_page.stats.unused')),
                confirmDeleteSingle: @json(__('media.library_page.messages.delete_single')),
                confirmDeleteMultiple: @json(__('media.library_page.messages.delete_multiple')),
                replaceTagsPrompt: @json(__('media.library_page.messages.replace_tags')),
                requestFailed: @json(__('media.library_page.messages.request_failed')),
                deleteDialogTitle: @json(__('media.library_page.confirm.delete_title')),
                bulkDeleteDialogTitle: @json(__('media.library_page.confirm.bulk_delete_title')),
                deleteAction: @json(__('media.library_page.confirm.delete_action')),
                bulkDeleteAction: @json(__('media.library_page.confirm.bulk_delete_action')),
                toastSuccessTitle: @json(__('media.library_page.toast.success_title')),
                toastErrorTitle: @json(__('media.library_page.toast.error_title')),
                metaType: @json(__('media.library_page.confirm.meta_type')),
                metaSize: @json(__('media.library_page.confirm.meta_size')),
                metaSelected: @json(__('media.library_page.confirm.meta_selected')),
                deleteSuccess: @json(__('media.messages.media_deleted')),
                bulkDeleteSuccess: @json(__('media.messages.bulk_delete_completed')),
                tagsUpdated: @json(__('media.messages.bulk_tags_updated')),
                cropSaved: @json(__('media.messages.media_cropped')),
                optimizeSaved: @json(__('media.messages.media_optimized')),
                variantsSaved: @json(__('media.messages.variants_regenerated')),
            },
        };

        function mediaLibraryPage() {
            return {
                config: window.mediaLibraryConfig,
                items: [],
                loading: false,
                viewMode: 'grid',
                showFilters: window.innerWidth >= 1024,
                collections: window.mediaLibraryConfig.initialCollections || [],
                tags: window.mediaLibraryConfig.initialTags || [],
                stats: window.mediaLibraryConfig.initialStats || {},
                selectedItems: [],
                searchTimer: null,
                cropper: null,
                toasts: [],
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 24,
                    total: 0,
                    from: 0,
                    to: 0,
                },
                filters: {
                    search: window.mediaLibraryConfig.initialFilters.search || '',
                    type: window.mediaLibraryConfig.initialFilters.type || '',
                    collection: window.mediaLibraryConfig.initialFilters.collection || '',
                    tag: window.mediaLibraryConfig.initialFilters.tag || '',
                    date_from: window.mediaLibraryConfig.initialFilters.date_from || '',
                    date_to: window.mediaLibraryConfig.initialFilters.date_to || '',
                    size_min_kb: window.mediaLibraryConfig.initialFilters.size_min ? Math.round(window.mediaLibraryConfig.initialFilters.size_min / 1024) : '',
                    size_max_kb: window.mediaLibraryConfig.initialFilters.size_max ? Math.round(window.mediaLibraryConfig.initialFilters.size_max / 1024) : '',
                    unused: Boolean(window.mediaLibraryConfig.initialFilters.unused),
                    sort: window.mediaLibraryConfig.initialFilters.sort || 'created_at',
                    order: window.mediaLibraryConfig.initialFilters.order || 'desc',
                },
                editModal: {
                    open: false,
                    form: {},
                },
                cropModal: {
                    open: false,
                    media: null,
                },
                confirmDialog: {
                    open: false,
                    title: '',
                    message: '',
                    confirmLabel: '',
                    meta: [],
                    action: null,
                },
                previewModal: {
                    open: false,
                    media: null,
                    activeSource: 'original',
                },

                init() {
                    this.loadMedia();
                    this.loadAuxiliaryData();

                    window.addEventListener('media-uploaded', () => {
                        this.loadMedia(1);
                        this.loadAuxiliaryData();
                    });
                },

                get statCards() {
                    return [
                        {
                            label: this.config.i18n.statTotalFiles,
                            value: this.stats.total_count || 0,
                            icon: 'database',
                            iconBg: 'bg-gradient-to-r from-[#0056b3] to-[#218838]',
                        },
                        {
                            label: this.config.i18n.statImages,
                            value: this.stats.images_count || 0,
                            icon: 'image',
                            iconBg: 'bg-gradient-to-r from-[#0056b3] to-[#64CCC5]',
                        },
                        {
                            label: this.config.i18n.statVideosAudio,
                            value: (this.stats.videos_count || 0) + (this.stats.audio_count || 0),
                            icon: 'clapperboard',
                            iconBg: 'bg-gradient-to-r from-[#218838] to-[#64CCC5]',
                        },
                        {
                            label: this.config.i18n.statUnused,
                            value: this.stats.unused_count || 0,
                            icon: 'shield',
                            iconBg: 'bg-gradient-to-r from-[#0f172a] to-[#334155]',
                        },
                    ];
                },

                async loadMedia(page = 1) {
                    this.loading = true;

                    try {
                        const params = new URLSearchParams();
                        params.set('page', page);
                        params.set('per_page', this.pagination.per_page || 24);
                        params.set('sort', this.filters.sort || 'created_at');
                        params.set('order', this.filters.order || 'desc');

                        if (this.filters.search) params.set('search', this.filters.search);
                        if (this.filters.type) params.set('type', this.filters.type);
                        if (this.filters.collection) params.set('collection', this.filters.collection);
                        if (this.filters.tag) params.set('tag', this.filters.tag);
                        if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                        if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                        if (this.filters.size_min_kb) params.set('size_min', String(Number(this.filters.size_min_kb) * 1024));
                        if (this.filters.size_max_kb) params.set('size_max', String(Number(this.filters.size_max_kb) * 1024));
                        if (this.filters.unused) params.set('unused', '1');

                        const response = await this.request(`${this.config.indexUrl}?${params.toString()}`);
                        this.items = response.data || [];
                        this.pagination = response.meta || this.pagination;
                        this.selectedItems = this.selectedItems.filter((id) => this.items.some((item) => item.id === id));

                        if (this.config.openMediaId) {
                            this.openRequestedMedia();
                        }
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                async loadAuxiliaryData() {
                    try {
                        const [statsResponse, collectionsResponse, tagsResponse] = await Promise.all([
                            this.request(this.config.statisticsUrl),
                            this.request(this.config.collectionsUrl),
                            this.request(this.config.tagsUrl),
                        ]);

                        this.stats = statsResponse.data || {};
                        this.collections = collectionsResponse.data || [];
                        this.tags = tagsResponse.data || [];
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                debounceSearch() {
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => this.applyFilters(), 300);
                },

                applyFilters() {
                    this.selectedItems = [];
                    this.loadMedia(1);
                },

                clearFilters() {
                    this.filters = {
                        search: '',
                        type: '',
                        collection: '',
                        tag: '',
                        date_from: '',
                        date_to: '',
                        size_min_kb: '',
                        size_max_kb: '',
                        unused: false,
                        sort: 'created_at',
                        order: 'desc',
                    };
                    this.applyFilters();
                },

                changePage(page) {
                    if (page < 1 || page > this.pagination.last_page) {
                        return;
                    }

                    this.loadMedia(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                currentPageSelected() {
                    return this.items.length > 0 && this.items.every((item) => this.selectedItems.includes(item.id));
                },

                toggleSelectAll() {
                    if (this.currentPageSelected()) {
                        this.selectedItems = this.selectedItems.filter((id) => !this.items.some((item) => item.id === id));
                        return;
                    }

                    const next = new Set(this.selectedItems);
                    this.items.forEach((item) => next.add(item.id));
                    this.selectedItems = Array.from(next);
                },

                typeBadge(type) {
                    const map = {
                        image: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        video: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        audio: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                        document: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                    };

                    return map[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
                },

                formatDate(value) {
                    if (!value) return '-';

                    return new Intl.DateTimeFormat(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    }).format(new Date(value));
                },

                viewMedia(item) {
                    this.previewModal.media = item;
                    this.previewModal.activeSource = this.defaultPreviewSource(item);
                    this.previewModal.open = true;
                    this.$nextTick(() => lucide.createIcons());
                },

                closePreview() {
                    this.previewModal.open = false;
                    this.previewModal.media = null;
                    this.previewModal.activeSource = 'original';
                },

                previewSources(media) {
                    if (!media) {
                        return [];
                    }

                    const sources = [{
                        key: 'original',
                        label: @js(__('media.preview.original')),
                        url: media.src,
                        width: media.width,
                        height: media.height,
                        size: media.human_readable_size,
                    }];

                    Object.entries(media.variants || {}).forEach(([key, variant]) => {
                        sources.push({
                            key,
                            label: key,
                            url: variant.url,
                            width: variant.width || null,
                            height: variant.height || null,
                            size: variant.human_readable_size || variant.size || null,
                        });
                    });

                    return sources;
                },

                defaultPreviewSource(media) {
                    if (!media) {
                        return 'original';
                    }

                    if (media.is_image && media.variants && media.variants.medium) {
                        return 'medium';
                    }

                    return 'original';
                },

                activePreviewSource() {
                    return this.previewSources(this.previewModal.media)
                        .find((source) => source.key === this.previewModal.activeSource) || null;
                },

                activePreviewDimensions() {
                    const source = this.activePreviewSource();
                    if (!source || !source.width || !source.height) {
                        return '-';
                    }

                    return `${source.width} × ${source.height}`;
                },

                activePreviewSize() {
                    const source = this.activePreviewSource();

                    return source?.size || this.previewModal.media?.human_readable_size || '-';
                },

                openEdit(item) {
                    this.editModal.form = {
                        ...item,
                        tagsText: (item.tags || []).join(', '),
                    };
                    this.editModal.open = true;
                    this.$nextTick(() => lucide.createIcons());
                },

                closeEdit() {
                    this.editModal.open = false;
                    this.editModal.form = {};
                },

                async saveEdit() {
                    const body = {
                        title: this.editModal.form.title,
                        alt_text: this.editModal.form.alt_text,
                        caption: this.editModal.form.caption,
                        collection_name: this.editModal.form.collection_name,
                        tags: this.editModal.form.tagsText
                            ? this.editModal.form.tagsText.split(',').map((tag) => tag.trim()).filter(Boolean)
                            : [],
                    };

                    await this.request(`${this.config.baseUrl}/${this.editModal.form.id}`, {
                        method: 'PUT',
                        body: JSON.stringify(body),
                        headers: {
                            'Content-Type': 'application/json',
                        },
                    });

                    this.closeEdit();
                    await this.loadMedia(this.pagination.current_page);
                    await this.loadAuxiliaryData();
                },

                async deleteMedia(item) {
                    this.openConfirmDialog({
                        title: this.config.i18n.deleteDialogTitle,
                        message: this.config.i18n.confirmDeleteSingle.replace(':title', item.title),
                        confirmLabel: this.config.i18n.deleteAction,
                        meta: [
                            { label: this.config.i18n.metaType, value: item.type_label || item.type },
                            { label: this.config.i18n.metaSize, value: item.human_readable_size },
                        ],
                        action: async () => {
                            const response = await this.request(`${this.config.baseUrl}/${item.id}`, {
                                method: 'DELETE',
                            });

                            this.showToast(response.message || this.config.i18n.deleteSuccess, 'success');
                            await this.loadMedia(this.pagination.current_page);
                            await this.loadAuxiliaryData();
                        },
                    });
                },

                async confirmBulkDelete() {
                    if (!this.selectedItems.length) {
                        return;
                    }

                    this.openConfirmDialog({
                        title: this.config.i18n.bulkDeleteDialogTitle,
                        message: this.config.i18n.confirmDeleteMultiple.replace(':count', this.selectedItems.length),
                        confirmLabel: this.config.i18n.bulkDeleteAction,
                        meta: [
                            { label: this.config.i18n.metaSelected, value: this.selectedItems.length },
                        ],
                        action: async () => {
                            const response = await this.request(this.config.bulkDeleteUrl, {
                                method: 'POST',
                                body: JSON.stringify({ ids: this.selectedItems }),
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                            });

                            this.selectedItems = [];
                            this.showToast(response.message || this.config.i18n.bulkDeleteSuccess, 'success');
                            await this.loadMedia(this.pagination.current_page);
                            await this.loadAuxiliaryData();
                        },
                    });
                },

                async bulkTag() {
                    const tagInput = prompt(this.config.i18n.replaceTagsPrompt);
                    if (tagInput === null) {
                        return;
                    }

                    const tags = tagInput.split(',').map((tag) => tag.trim()).filter(Boolean);

                    await this.request(this.config.bulkUpdateTagsUrl, {
                        method: 'POST',
                        body: JSON.stringify({
                            ids: this.selectedItems,
                            tags,
                        }),
                        headers: {
                            'Content-Type': 'application/json',
                        },
                    });

                    this.showToast(this.config.i18n.tagsUpdated, 'success');
                    await this.loadMedia(this.pagination.current_page);
                    await this.loadAuxiliaryData();
                },

                bulkDownload() {
                    this.items
                        .filter((item) => this.selectedItems.includes(item.id))
                        .forEach((item) => {
                            const link = document.createElement('a');
                            link.href = item.download_url;
                            link.target = '_blank';
                            link.rel = 'noopener';
                            document.body.appendChild(link);
                            link.click();
                            link.remove();
                        });
                },

                openCrop(item) {
                    this.cropModal.media = item;
                    this.cropModal.open = true;

                    this.$nextTick(() => {
                        if (this.cropper) {
                            this.cropper.destroy();
                        }

                        this.cropper = new Cropper(this.$refs.cropperImage, {
                            viewMode: 1,
                            autoCropArea: 1,
                            responsive: true,
                            background: false,
                        });
                    });
                },

                closeCrop() {
                    this.cropModal.open = false;
                    this.cropModal.media = null;

                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }
                },

                async saveCrop() {
                    if (!this.cropModal.media || !this.cropper) {
                        return;
                    }

                    const data = this.cropper.getData(true);

                    await this.request(`${this.config.baseUrl}/${this.cropModal.media.id}/crop`, {
                        method: 'POST',
                        body: JSON.stringify({
                            x: data.x,
                            y: data.y,
                            width: data.width,
                            height: data.height,
                        }),
                        headers: {
                            'Content-Type': 'application/json',
                        },
                    });

                    this.closeCrop();
                    this.showToast(this.config.i18n.cropSaved, 'success');
                    await this.loadMedia(this.pagination.current_page);
                },

                async optimizeMedia(item) {
                    await this.request(`${this.config.baseUrl}/${item.id}/optimize`, {
                        method: 'POST',
                    });

                    this.showToast(this.config.i18n.optimizeSaved, 'success');
                    await this.loadMedia(this.pagination.current_page);
                },

                async regenerateVariants(item) {
                    await this.request(`${this.config.baseUrl}/${item.id}/regenerate-variants`, {
                        method: 'POST',
                    });

                    this.showToast(this.config.i18n.variantsSaved, 'success');
                    await this.loadMedia(this.pagination.current_page);
                },

                async openRequestedMedia() {
                    const mediaId = this.config.openMediaId;
                    this.config.openMediaId = null;

                    if (!mediaId) {
                        return;
                    }

                    const existing = this.items.find((item) => item.id === Number(mediaId));
                    if (existing) {
                        this.openEdit(existing);
                        return;
                    }

                    const response = await this.request(`${this.config.baseUrl}/${mediaId}`);
                    if (response.data) {
                        this.openEdit(response.data);
                    }
                },

                openConfirmDialog(config) {
                    this.confirmDialog = {
                        open: true,
                        title: config.title || '',
                        message: config.message || '',
                        confirmLabel: config.confirmLabel || this.config.i18n.deleteAction,
                        meta: config.meta || [],
                        action: config.action || null,
                    };
                    this.$nextTick(() => lucide.createIcons());
                },

                closeConfirmDialog() {
                    this.confirmDialog = {
                        open: false,
                        title: '',
                        message: '',
                        confirmLabel: '',
                        meta: [],
                        action: null,
                    };
                },

                async runConfirmAction() {
                    const action = this.confirmDialog.action;
                    this.closeConfirmDialog();

                    if (!action) {
                        return;
                    }

                    try {
                        await action();
                    } catch (error) {
                        this.showToast(error.message || this.config.i18n.requestFailed, 'error');
                    }
                },

                showToast(message, type = 'success', title = '') {
                    const toast = {
                        id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                        type,
                        title,
                        message,
                    };

                    this.toasts.unshift(toast);
                    this.$nextTick(() => lucide.createIcons());

                    window.setTimeout(() => this.dismissToast(toast.id), 4500);
                },

                dismissToast(toastId) {
                    this.toasts = this.toasts.filter((toast) => toast.id !== toastId);
                },

                async request(url, options = {}) {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    const response = await fetch(url, {
                        method: options.method || 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                            ...(options.headers || {}),
                        },
                        body: options.body || null,
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.success === false) {
                        const errorMessage = data.message
                            || Object.values(data.errors || {}).flat().find(Boolean)
                            || this.config.i18n.requestFailed;

                        throw new Error(errorMessage);
                    }

                    return data;
                },
            };
        }
    </script>
@endpush
