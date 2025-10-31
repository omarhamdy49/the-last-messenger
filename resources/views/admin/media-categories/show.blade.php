@extends('layouts.admin')

@section('title', 'Media Category Details')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Media Category Details
            </h1>
            <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                View and manage category information
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.media-categories.edit', $mediaCategory->id) }}"
               class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="edit" class="w-5 h-5"></i>
                <span>Edit</span>
            </a>
            <form action="{{ route('admin.media-categories.destroy', $mediaCategory->id) }}" method="POST" class="inline"
                  onsubmit="return confirm('Are you sure you want to delete this category?')">
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

    <!-- Category Information -->
    <div class="glass rounded-lg p-8 mb-8 transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <h3 class="text-lg font-semibold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
            Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Name (English)</p>
                    <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $mediaCategory->name['en'] ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Name (Arabic)</p>
                    <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="rtl">
                        {{ $mediaCategory->name['ar'] ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Icon</p>
                    <div class="flex items-center gap-2 mt-2">
                        <i data-lucide="{{ $mediaCategory->icon ?? 'video' }}" class="w-8 h-8 text-[#0056b3]"></i>
                        <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $mediaCategory->icon ?? 'video' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Programs Count</p>
                    <p class="text-lg font-bold text-[#0056b3]">
                        {{ $mediaCategory->programs_count ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Display Order</p>
                    <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $mediaCategory->display_order ?? 0 }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Created At</p>
                    <p class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $mediaCategory->created_at->format('Y-m-d H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if($mediaCategory->description && ($mediaCategory->description['en'] || $mediaCategory->description['ar']))
            <div class="mt-8 pt-8 border-t transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Description
                </h3>
                @if($mediaCategory->description['en'])
                    <div class="mb-4">
                        <p class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">English</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $mediaCategory->description['en'] }}</p>
                    </div>
                @endif
                @if($mediaCategory->description['ar'])
                    <div>
                        <p class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Arabic</p>
                        <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'" dir="rtl">{{ $mediaCategory->description['ar'] }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Programs in Category -->
    <div class="glass rounded-lg overflow-hidden transition-colors" :class="darkMode ? 'bg-gray-900/50' : 'bg-white/80'">
        <div class="p-6 border-b transition-colors" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <h3 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Programs in This Category ({{ $mediaCategory->programs_count ?? 0 }})
            </h3>
        </div>

        @if($mediaCategory->programs && $mediaCategory->programs->count() > 0)
            <table class="w-full">
                <thead :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Episodes</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mediaCategory->programs as $program)
                        <tr :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'" class="border-t transition">
                            <td class="px-6 py-4 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $program->name['en'] ?? $program->name['ar'] ?? 'Untitled' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                    {{ $program->episodes_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    @if($program->status->value === 'ongoing') bg-blue-100 text-blue-800
                                    @elseif($program->status->value === 'completed') bg-green-100 text-green-800
                                    @elseif($program->status->value === 'paused') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $program->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $program->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.media-programs.edit', $program->id) }}"
                                   class="text-[#0056b3] hover:text-blue-700 font-semibold text-sm"
                                   title="Edit Program">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center">
                <i data-lucide="video" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'" class="text-lg">
                    No programs in this category yet
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
