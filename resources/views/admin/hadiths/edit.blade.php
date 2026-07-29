@extends('layouts.admin')

@section('title', __('admin.edit_hadith'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.edit_hadith') }}</h1>
            <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.hadith') }} #{{ $hadith->id }}</p>
        </div>
        <a href="{{ route('admin.hadith.index') }}" class="px-4 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300' : 'border-gray-300 text-gray-700'">
            {{ __('admin.back') }}
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hadith.update', $hadith) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <h2 class="text-xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.hadith_information') }}</h2>

            <div class="space-y-4">
                <!-- Book -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.book') }} *</label>
                    <select name="chapter_id" required class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.select_book') }}</option>
                        @foreach($chapters as $chapter)
                            <option value="{{ $chapter->id }}" @selected($hadith->chapter_id == $chapter->id)>
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
                    <textarea name="text_ar" required rows="8" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('text_ar', $hadith->translations->firstWhere('locale', 'ar')?->text) }}</textarea>
                    @error('text_ar')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hadith Number -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.hadith_number') }} *</label>
                    <input type="number" name="hadith_number" value="{{ $hadith->hadith_number }}" required min="0"
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
                            <option value="{{ $level->id }}" @selected($hadith->grading_status === $level->id)>
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
                    <input type="number" name="primary_narrator_id" value="{{ $hadith->primary_narrator_id }}" min="0"
                        class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('primary_narrator_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                <i data-lucide="save"></i>
                {{ __('admin.save_changes') }}
            </button>
            <a href="{{ route('admin.hadith.index') }}" class="px-6 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>

    <!-- Isnad Chain (Narrator Chain) -->
    <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
        <h2 class="text-xl font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.isnad_chain') }}</h2>
        <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.isnad_chain_hint') }}</p>

        @if($hadith->isnads->isEmpty())
            <p class="text-sm py-4" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.no_narrators_in_chain') }}</p>
        @else
            <ol class="space-y-2 mb-6">
                @foreach($hadith->isnads as $isnad)
                    @php
                        $narrator = $isnad->narrator;
                        $narratorName = $narrator?->getDisplayName('ar') ?: '—';
                    @endphp
                    <li class="flex flex-wrap items-center gap-3 p-3 rounded-lg border" :class="darkMode ? 'border-gray-700 bg-gray-900/40' : 'border-gray-200 bg-gray-50'">
                        <span class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
                            {{ $isnad->position_in_chain }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $narratorName }}</p>
                            @if($isnad->transmission_method)
                                <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $isnad->transmission_method }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 flex-shrink-0">
                            <!-- Move up (toward the compiler) -->
                            <form method="POST" action="{{ route('admin.hadith.isnad.move', [$hadith, $isnad]) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" @if($loop->first) disabled @endif
                                        class="min-w-11 min-h-11 flex items-center justify-center rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                        :class="darkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-200 text-gray-600'"
                                        title="{{ __('admin.move_up') }}">
                                    <i data-lucide="chevron-up" class="w-4 h-4"></i>
                                </button>
                            </form>

                            <!-- Move down (toward the companion) -->
                            <form method="POST" action="{{ route('admin.hadith.isnad.move', [$hadith, $isnad]) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" @if($loop->last) disabled @endif
                                        class="min-w-11 min-h-11 flex items-center justify-center rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                        :class="darkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-200 text-gray-600'"
                                        title="{{ __('admin.move_down') }}">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                            </form>

                            <!-- Remove -->
                            <form method="POST" action="{{ route('admin.hadith.isnad.destroy', [$hadith, $isnad]) }}"
                                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-w-11 min-h-11 flex items-center justify-center rounded-lg transition-colors text-red-500"
                                        :class="darkMode ? 'hover:bg-red-900/30' : 'hover:bg-red-50'"
                                        title="{{ __('admin.remove_from_chain') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif

        <!-- Add narrator to chain -->
        <form method="POST" action="{{ route('admin.hadith.isnad.store', $hadith) }}" class="flex flex-col sm:flex-row gap-3 pt-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            @csrf
            <select name="narrator_id" required class="flex-1 min-w-0 w-full px-4 py-2 rounded-lg border text-base sm:text-sm" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('admin.select_narrator') }}</option>
                @foreach($narrators as $narrator)
                    <option value="{{ $narrator->id }}">{{ $narrator->getDisplayName('ar') }}</option>
                @endforeach
            </select>
            <input type="text" name="transmission_method" placeholder="{{ __('admin.transmission_method') }}"
                   class="w-full sm:w-48 flex-shrink-0 px-4 py-2 rounded-lg border text-base sm:text-sm" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
            <button type="submit" class="w-full sm:w-auto flex-shrink-0 px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('admin.add_narrator_to_chain') }}
            </button>
        </form>
        @error('narrator_id')
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
    lucide.createIcons();
</script>
@endsection
