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
                    <select name="book_id" required class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.select_book') }}</option>
                        @foreach($books as $book)
                            <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>
                                {{ $book->title_ar }}
                            </option>
                        @endforeach
                    </select>
                    @error('book_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
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

                <!-- Narrator -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.narrator') }}</label>
                    <select name="narrator_id" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.select_narrator') }}</option>
                        @foreach($narrators as $narrator)
                            <option value="{{ $narrator->id }}" @selected(old('narrator_id') == $narrator->id)>
                                {{ $narrator->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('narrator_id')
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
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.companion_id') }}</label>
                    <input type="number" name="companion_id" value="{{ old('companion_id') }}" min="0"
                        placeholder="{{ __('admin.enter_companion_id') }}"
                        class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('companion_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
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
