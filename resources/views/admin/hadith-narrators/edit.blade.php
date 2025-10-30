@extends('admin.layouts.admin')

@section('title', __('admin.edit_narrator'))

@section('content')
<div x-data="narratorForm()" class="container mx-auto max-w-4xl px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            {{ __('admin.edit_narrator') }}: {{ $narrator->name['en'] ?? $narrator->name['ar'] }}
        </h1>
        <p class="text-gray-500">{{ __('admin.edit_narrator_description') }}</p>
    </div>

    <!-- Language Tabs -->
    <div class="flex gap-2 mb-8 border-b border-gray-300" :class="darkMode ? 'border-gray-700' : ''">
        @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
            <button type="button"
                    @click="activeTab = '{{ $lang }}'"
                    :class="activeTab === '{{ $lang }}' ? 'border-b-2 border-gradient-to-r from-[#0056b3] to-[#218838] text-[#0056b3]' : 'text-gray-500 hover:text-gray-700'"
                    class="px-6 py-3 font-semibold transition whitespace-nowrap">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- Form -->
    <form action="{{ route('admin.hadith-narrators.update', $narrator) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Basic Information Section -->
        <div class="glass rounded-2xl p-8 backdrop-blur-lg"
             :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="user" class="w-5 h-5 text-[#0056b3]"></i>
                {{ __('admin.basic_information') }}
            </h2>

            <!-- Multi-language Name Fields -->
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeTab === '{{ $lang }}'" x-cloak class="space-y-4">
                    <div class="form-group">
                        <label for="name_{{ $lang }}" class="block font-semibold mb-2"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __("admin.name_$lang") }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name[{{ $lang }}]"
                               id="name_{{ $lang }}"
                               value="{{ old("name.$lang", $narrator->name[$lang] ?? '') }}"
                               class="w-full px-4 py-2 rounded-lg border transition"
                               :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                               placeholder="{{ __('admin.enter_name') }}"
                               required>
                        @error("name.$lang")
                            <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Biography Field -->
                    <div class="form-group">
                        <label for="biography_json_{{ $lang }}" class="block font-semibold mb-2"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __("admin.biography_$lang") }}
                        </label>
                        <textarea name="biography_json[{{ $lang }}]"
                                  id="biography_json_{{ $lang }}"
                                  rows="5"
                                  class="w-full px-4 py-2 rounded-lg border transition"
                                  :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                                  placeholder="{{ __('admin.enter_biography') }}">{{ old("biography_json.$lang", $narrator->biography_json[$lang] ?? '') }}</textarea>
                        @error("biography_json.$lang")
                            <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Reliability & Era Section -->
        <div class="glass rounded-2xl p-8 backdrop-blur-lg"
             :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="star" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.reliability_and_era') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reliability Grade -->
                <div class="form-group">
                    <label for="reliability_grade" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.reliability_grade') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select name="reliability_grade"
                            id="reliability_grade"
                            class="w-full px-4 py-2 rounded-lg border transition"
                            :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                            required>
                        <option value="">{{ __('admin.select_grade') }}</option>
                        <option value="Sahih" {{ old('reliability_grade', $narrator->reliability_grade) === 'Sahih' ? 'selected' : '' }}>{{ __('admin.sahih') }}</option>
                        <option value="Hasan" {{ old('reliability_grade', $narrator->reliability_grade) === 'Hasan' ? 'selected' : '' }}>{{ __('admin.hasan') }}</option>
                        <option value="Daif" {{ old('reliability_grade', $narrator->reliability_grade) === 'Daif' ? 'selected' : '' }}>{{ __('admin.daif') }}</option>
                        <option value="Weak" {{ old('reliability_grade', $narrator->reliability_grade) === 'Weak' ? 'selected' : '' }}>{{ __('admin.weak') }}</option>
                    </select>
                    @error('reliability_grade')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Era -->
                <div class="form-group">
                    <label for="era" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.era') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <select name="era"
                            id="era"
                            class="w-full px-4 py-2 rounded-lg border transition"
                            :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                            required>
                        <option value="">{{ __('admin.select_era') }}</option>
                        <option value="Sahabah" {{ old('era', $narrator->era) === 'Sahabah' ? 'selected' : '' }}>{{ __('admin.sahabah') }}</option>
                        <option value="Tabiun" {{ old('era', $narrator->era) === 'Tabiun' ? 'selected' : '' }}>{{ __('admin.tabiun') }}</option>
                        <option value="Taba_al_Tabiun" {{ old('era', $narrator->era) === 'Taba_al_Tabiun' ? 'selected' : '' }}>{{ __('admin.taba_al_tabiun') }}</option>
                        <option value="Taba_Taba_al_Tabiun" {{ old('era', $narrator->era) === 'Taba_Taba_al_Tabiun' ? 'selected' : '' }}>{{ __('admin.taba_taba_al_tabiun') }}</option>
                        <option value="Later_Scholars" {{ old('era', $narrator->era) === 'Later_Scholars' ? 'selected' : '' }}>{{ __('admin.later_scholars') }}</option>
                    </select>
                    @error('era')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Geographic & Historical Section -->
        <div class="glass rounded-2xl p-8 backdrop-blur-lg"
             :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="map-pin" class="w-5 h-5 text-[#218838]"></i>
                {{ __('admin.geographic_historical_info') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Country -->
                <div class="form-group">
                    <label for="country" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.country') }}
                    </label>
                    <input type="text"
                           name="country"
                           id="country"
                           value="{{ old('country', $narrator->country) }}"
                           class="w-full px-4 py-2 rounded-lg border transition"
                           :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                           placeholder="{{ __('admin.enter_country') }}">
                    @error('country')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Birth Date (Hijri) -->
                <div class="form-group">
                    <label for="birth_hijri" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.birth_hijri') }}
                        <span class="text-xs text-gray-500">(e.g., 50 AH)</span>
                    </label>
                    <input type="text"
                           name="birth_hijri"
                           id="birth_hijri"
                           value="{{ old('birth_hijri', $narrator->birth_hijri) }}"
                           class="w-full px-4 py-2 rounded-lg border transition"
                           :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                           placeholder="e.g., 50 AH">
                    @error('birth_hijri')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Death Date (Hijri) -->
                <div class="form-group">
                    <label for="death_hijri" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.death_hijri') }}
                        <span class="text-xs text-gray-500">(e.g., 120 AH)</span>
                    </label>
                    <input type="text"
                           name="death_hijri"
                           id="death_hijri"
                           value="{{ old('death_hijri', $narrator->death_hijri) }}"
                           class="w-full px-4 py-2 rounded-lg border transition"
                           :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'"
                           placeholder="e.g., 120 AH">
                    @error('death_hijri')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Narrator Chain Section -->
        <div class="glass rounded-2xl p-8 backdrop-blur-lg"
             :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="link" class="w-5 h-5 text-[#0056b3]"></i>
                {{ __('admin.narrator_chain') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Teachers -->
                <div class="form-group">
                    <label for="teachers" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.teachers') }}
                    </label>
                    <select name="teachers[]"
                            id="teachers"
                            multiple
                            class="w-full px-4 py-2 rounded-lg border transition"
                            :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'">
                        @foreach($allNarrators as $n)
                            @if($n->id !== $narrator->id)
                                <option value="{{ $n->id }}"
                                        {{ in_array($n->id, old('teachers', $narrator->teachers ?? [])) ? 'selected' : '' }}>
                                    {{ $n->name['en'] ?? $n->name['ar'] }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">{{ __('admin.hold_ctrl_to_select_multiple') }}</p>
                    @error('teachers')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Students -->
                <div class="form-group">
                    <label for="students" class="block font-semibold mb-2"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('admin.students') }}
                    </label>
                    <select name="students[]"
                            id="students"
                            multiple
                            class="w-full px-4 py-2 rounded-lg border transition"
                            :class="darkMode ? 'bg-gray-800 text-white border-gray-600' : 'bg-white text-gray-900 border-gray-300'">
                        @foreach($allNarrators as $n)
                            @if($n->id !== $narrator->id)
                                <option value="{{ $n->id }}"
                                        {{ in_array($n->id, old('students', $narrator->students ?? [])) ? 'selected' : '' }}>
                                    {{ $n->name['en'] ?? $n->name['ar'] }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">{{ __('admin.hold_ctrl_to_select_multiple') }}</p>
                    @error('students')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="glass rounded-2xl p-8 backdrop-blur-lg"
             :class="darkMode ? 'bg-gray-900/30 border-gray-700' : 'bg-white/70 border-white'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="toggle-right" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.status') }}
            </h2>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio"
                           name="status"
                           value="active"
                           {{ old('status', $narrator->status) === 'active' ? 'checked' : '' }}>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.active') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio"
                           name="status"
                           value="inactive"
                           {{ old('status', $narrator->status) === 'inactive' ? 'checked' : '' }}>
                    <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.inactive') }}</span>
                </label>
            </div>
            @error('status')
                <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span>
            @enderror
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4 pt-8">
            <button type="submit"
                    class="flex items-center gap-2 px-8 py-3 rounded-lg font-semibold transition text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg">
                <i data-lucide="save" class="w-5 h-5"></i>
                {{ __('admin.update') }}
            </button>
            <a href="{{ route('admin.hadith-narrators.show', $narrator) }}"
               class="flex items-center gap-2 px-8 py-3 rounded-lg font-semibold transition"
               :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                <i data-lucide="x" class="w-5 h-5"></i>
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
function narratorForm() {
    return {
        activeTab: 'ar',
        darkMode: localStorage.getItem('darkMode') !== 'false',
        init() {
            this.$watch('darkMode', () => {
                localStorage.setItem('darkMode', this.darkMode);
            });
        }
    };
}
</script>
@endsection
