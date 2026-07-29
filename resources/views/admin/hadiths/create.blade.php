@extends('layouts.admin')

@section('title', __('admin.add_hadith'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.add_new_hadith') }}</h1>
            <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.create_new_hadith') }}</p>
        </div>
        <a href="{{ route('admin.hadith.index') }}" class="px-4 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300' : 'border-gray-300 text-gray-700'">
            {{ __('admin.back') }}
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hadith.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.hadith_information') }}</h2>

            <div class="space-y-4">
                <!-- Book -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.book') }} *</label>
                    <select name="chapter_id" required class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.select_book') }}</option>
                        @foreach($chapters as $chapter)
                            <option value="{{ $chapter->id }}" @selected(old('chapter_id') == $chapter->id)>
                                {{ $chapter->book?->title_ar }} — {{ $chapter->getNameInLocale('ar') }}
                            </option>
                        @endforeach
                    </select>
                    @error('chapter_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">النص العربي</label>
                    <textarea name="text_ar" required rows="8" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('text_ar') }}</textarea>
                    @error('text_ar')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hadith Number -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.hadith_number') }} *</label>
                    <input type="number" name="hadith_number" value="{{ old('hadith_number') }}" required min="0"
                        placeholder="{{ __('admin.enter_hadith_number') }}"
                        class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('hadith_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Grading Status -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.grading') }}</label>
                    <select name="grading_status" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.select_grading') }}</option>
                        @foreach($gradingLevels as $level)
                            <option value="{{ $level->id }}" @selected(old('grading_status') == $level->id)>
                                {{ $level->grade_ar }}
                            </option>
                        @endforeach
                    </select>
                    @error('grading_status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Companion ID -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.narrator') }}</label>
                    <input type="number" name="primary_narrator_id" value="{{ old('primary_narrator_id') }}" min="0"
                        placeholder="{{ __('admin.narrator') }}"
                        class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('primary_narrator_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.isnad_chain_available_after_save') }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="plus"></i>
                {{ __('admin.create_hadith') }}
            </button>
            <a href="{{ route('admin.hadith.index') }}" class="px-6 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
    lucide.createIcons();
</script>
@endsection
