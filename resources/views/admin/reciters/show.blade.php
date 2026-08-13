@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.reciters.index') }}" class="hover:text-accent transition-colors">{{ __('quran.reciters.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $reciter->getTranslation('name', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="mic" class="w-7 h-7 text-white"></i>
            </div>
            {{ $reciter->getTranslation('name', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.reciters.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Profile Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-48 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($reciter->is_featured)
                <div class="absolute top-4 start-4">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-semibold bg-amber-500 text-white shadow-lg">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        قارئ مميز
                    </span>
                </div>
                @endif
            </div>

            <div class="relative px-6 pb-6">
                <!-- Profile Image -->
                <div class="absolute -top-16 start-6">
                    <div class="w-32 h-32 rounded-2xl overflow-hidden border-4 shadow-xl"
                         :class="darkMode ? 'border-gray-900' : 'border-white'">
                        @if($reciter->profile_image)
                            <img src="{{ asset('storage/' . $reciter->profile_image) }}"
                                 alt="{{ $reciter->getTranslation('name', 'ar') }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                <i data-lucide="mic" class="w-12 h-12 text-white/50"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="pt-20">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $reciter->getTranslation('name', 'ar') }}
                            </h2>
                            <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $reciter->getTranslation('name', 'en') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($reciter->is_active)
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                    غير نشط
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-4">
                        @if($reciter->country)
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <i data-lucide="map-pin" class="w-4 h-4 text-accent"></i>
                            {{ $reciter->country }}
                        </span>
                        @endif
                        @if($reciter->style)
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl"
                              :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                            <i data-lucide="music" class="w-4 h-4"></i>
                            {{ $reciter->style }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Biography -->
        @if($reciter->getTranslation('description', 'ar') || $reciter->getTranslation('description', 'en'))
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                السيرة الذاتية
            </h2>

            <div x-data="{ activeTab: 'ar' }" class="space-y-4">
                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                        @if($reciter->getTranslation('description', $lang, false))
                        <button @click="activeTab = '{{ $lang }}'"
                                class="px-4 py-2 rounded-lg font-medium transition-all"
                                :class="activeTab === '{{ $lang }}' ?
                                    'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                    (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                            {{ $label }}
                        </button>
                        @endif
                    @endforeach
                </div>

                <!-- Content -->
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    @if($reciter->getTranslation('description', $lang, false))
                    <div x-show="activeTab === '{{ $lang }}'"
                         class="p-4 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <p class="leading-relaxed {{ in_array($lang, ['ar', 'ur']) ? 'text-right' : 'text-left' }}"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $reciter->getTranslation('description', $lang, false) }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Names in Different Languages -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                الاسم بلغات مختلفة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    @if($reciter->getTranslation('name', $lang, false))
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $label }}
                        </label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $reciter->getTranslation('name', $lang, false) }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">

        <!-- Reciter Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->slug }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">ترتيب العرض</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->display_order }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $reciter->created_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                إجراءات سريعة
            </h3>

            <div class="space-y-3">
                <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل القارئ</span>
                </a>

                <form action="{{ route('admin.reciters.toggle-featured', $reciter->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                            :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                        <i data-lucide="star" class="w-5 h-5 {{ $reciter->is_featured ? 'text-amber-500' : 'text-gray-400' }}"></i>
                        <span>{{ $reciter->is_featured ? 'إلغاء التمييز' : 'تمييز القارئ' }}</span>
                    </button>
                </form>

                <a href="{{ route('admin.reciters.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة قارئ جديد</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف القارئ
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذا القارئ نهائياً ولا يمكن استرجاعه.
            </p>

            <form action="{{ route('admin.reciters.destroy', $reciter->id) }}"
                  method="POST"
                  @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف القارئ</span>
                </button>
            </form>
        </div>

    </div>
</div>

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur']);
    $withAudio = $surahs->filter(fn ($row) => $row['recitation'] !== null)->count();
@endphp

<!-- Surah Recitations (per-surah audio management) -->
<div class="mt-6 rounded-2xl border overflow-hidden"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="recitationManager({
        reciterId: {{ $reciter->id }},
        storeUrl: '{{ route('admin.reciters.recitations.store', $reciter->id) }}',
     })">

    <!-- Section Header -->
    <div class="p-6 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="audio-lines" class="w-5 h-5 text-accent"></i>
                {{ __('quran.recitations.section_title') }}
            </h2>
            <p class="text-sm mt-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('quran.recitations.section_subtitle') }}
            </p>
        </div>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold self-start"
              :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'">
            <i data-lucide="check-circle" class="w-4 h-4 text-accent"></i>
            {{ __('quran.recitations.with_audio_count', ['count' => $withAudio]) }}
        </span>
    </div>

    @if($surahs->isEmpty())
        <!-- Empty state -->
        <div class="p-12 text-center">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
            <p class="font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                {{ __('quran.recitations.empty') }}
            </p>
        </div>
    @else
        <!-- Surah table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start" :class="darkMode ? 'bg-gray-800/50 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <th class="px-4 py-3 text-start font-semibold w-12">{{ __('quran.recitations.surah_number') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('quran.recitations.surah_name') }}</th>
                        <th class="px-4 py-3 text-start font-semibold hidden md:table-cell">{{ __('quran.recitations.verses_count') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('quran.recitations.status') }}</th>
                        <th class="px-4 py-3 text-start font-semibold hidden lg:table-cell">{{ __('quran.recitations.format') }}</th>
                        <th class="px-4 py-3 text-start font-semibold hidden lg:table-cell">{{ __('quran.recitations.quality') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('quran.recitations.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @foreach($surahs as $row)
                        @php
                            $rec = $row['recitation'];
                            $surahLabel = $row['number'].' · '.$row['name_ar'];
                            $addPayload = json_encode([
                                'surah_id' => $row['surah_id'],
                                'surah_label' => $surahLabel,
                            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
                            $editPayload = $rec ? json_encode([
                                'id' => $rec->id,
                                'surah_id' => $row['surah_id'],
                                'surah_label' => $surahLabel,
                                'audio_url' => $rec->audio_url,
                                'format' => $rec->format,
                                'quality' => $rec->quality,
                                'duration_seconds' => $rec->duration_seconds,
                                'file_size_kb' => $rec->file_size_kb,
                            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) : '{}';
                        @endphp
                        <tr :class="darkMode ? 'hover:bg-gray-800/40' : 'hover:bg-gray-50'">
                            <td class="px-4 py-3 font-mono" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                {{ $row['number'] }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold truncate max-w-[16rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ $row['name_ar'] }}
                                </div>
                                <div class="text-xs truncate max-w-[16rem]" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    {{ $row['name_en'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ $row['verses_count'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if($rec)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        {{ __('quran.recitations.has_audio') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                        <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                        {{ __('quran.recitations.no_audio') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell uppercase" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ $rec?->format ?? '—' }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                @if($rec?->quality)
                                    {{ __('quran.recitations.' . $rec->quality) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if($rec)
                                        @if($rec->audio_url)
                                            <button type="button"
                                               @click="$store.audioPlayer.play({{ \Illuminate\Support\Js::from([
                                                   'url' => $rec->audio_url,
                                                   'title' => $surahLabel,
                                                   'subtitle' => $reciter->getDisplayName(app()->getLocale()),
                                               ]) }})"
                                               class="p-2 rounded-lg transition-colors"
                                               :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-accent' : 'bg-gray-100 hover:bg-gray-200 text-accent'"
                                               title="{{ __('quran.recitations.play') }}">
                                                <i data-lucide="play" class="w-4 h-4"></i>
                                            </button>
                                        @endif
                                        <button type="button"
                                                @click="openEdit({{ $editPayload }})"
                                                class="p-2 rounded-lg transition-colors"
                                                :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-blue-400' : 'bg-blue-50 hover:bg-blue-100 text-blue-600'"
                                                title="{{ __('quran.recitations.edit_audio') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('admin.reciters.recitations.destroy', [$reciter->id, $rec->id]) }}"
                                              @submit.prevent="$dispatch('show-confirm', { title: '{{ __('quran.recitations.confirm_delete') }}', message: '{{ __('quran.recitations.confirm_delete_message') }}', type: 'danger', onConfirm: () => $el.submit() })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 rounded-lg transition-colors bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/40 dark:text-red-400"
                                                    title="{{ __('quran.recitations.delete_audio') }}">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button"
                                                @click="openAdd({{ $addPayload }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            <span>{{ __('quran.recitations.add_audio') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Add/Edit Modal -->
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition.opacity>
        <div class="absolute inset-0 bg-black/50" @click="close()"></div>

        <div class="relative w-full max-w-lg rounded-2xl border shadow-2xl max-h-[80vh] overflow-y-auto"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
             @keydown.escape.window="close()">

            <!-- Modal Header -->
            <div class="p-5 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div>
                    <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                        x-text="mode === 'edit' ? '{{ __('quran.recitations.modal_edit_title') }}' : '{{ __('quran.recitations.modal_add_title') }}'"></h3>
                    <p class="text-sm mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="form.surah_label"></p>
                </div>
                <button type="button" @click="close()" class="p-2 rounded-lg transition-colors"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formAction" method="POST" @submit="submitting = true">
                @csrf
                <input type="hidden" name="_method" x-bind:value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="surah_id" x-model="form.surah_id">

                <div class="p-5 space-y-4">
                    <!-- Audio URL -->
                    <div>
                        <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.recitations.audio_url') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="url" name="audio_url" x-model="form.audio_url" required
                               dir="ltr"
                               placeholder="{{ __('quran.recitations.audio_url_placeholder') }}"
                               class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Format -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('quran.recitations.format') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="format" x-model="form.format" required
                                    class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                <option value="">{{ __('quran.recitations.select_format') }}</option>
                                <option value="mp3">MP3</option>
                                <option value="aac">AAC</option>
                                <option value="opus">OPUS</option>
                            </select>
                        </div>

                        <!-- Quality -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('quran.recitations.quality') }}
                            </label>
                            <select name="quality" x-model="form.quality"
                                    class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                <option value="">{{ __('quran.recitations.no_quality') }}</option>
                                <option value="high">{{ __('quran.recitations.high') }}</option>
                                <option value="medium">{{ __('quran.recitations.medium') }}</option>
                                <option value="low">{{ __('quran.recitations.low') }}</option>
                            </select>
                        </div>

                        <!-- Duration -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('quran.recitations.duration_seconds') }}
                            </label>
                            <input type="number" name="duration_seconds" x-model="form.duration_seconds" min="0" dir="ltr"
                                   class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>

                        <!-- File size -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('quran.recitations.file_size_kb') }}
                            </label>
                            <input type="number" name="file_size_kb" x-model="form.file_size_kb" min="0" dir="ltr"
                                   class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-5 border-t flex flex-wrap items-center justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <button type="button" @click="close()" :disabled="submitting"
                            class="px-5 py-2.5 rounded-xl font-semibold border-2 transition-all disabled:opacity-50"
                            :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                        {{ __('quran.recitations.cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white disabled:opacity-60 disabled:hover:scale-100">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting"></i>
                        <span x-text="submitting ? '{{ __('quran.recitations.saving') }}' : '{{ __('quran.recitations.save') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function recitationManager(config) {
        return {
            open: false,
            mode: 'add',
            submitting: false,
            storeUrl: config.storeUrl,
            reciterId: config.reciterId,
            formAction: config.storeUrl,
            form: {
                id: null,
                surah_id: '',
                surah_label: '',
                audio_url: '',
                format: '',
                quality: '',
                duration_seconds: '',
                file_size_kb: '',
            },
            openAdd(data) {
                this.mode = 'add';
                this.formAction = this.storeUrl;
                this.form = {
                    id: null,
                    surah_id: data.surah_id,
                    surah_label: data.surah_label,
                    audio_url: '',
                    format: '',
                    quality: '',
                    duration_seconds: '',
                    file_size_kb: '',
                };
                this.show();
            },
            openEdit(data) {
                this.mode = 'edit';
                this.formAction = `{{ url('su/portal/v1/admin/reciters') }}/${this.reciterId}/recitations/${data.id}`;
                this.form = {
                    id: data.id,
                    surah_id: data.surah_id,
                    surah_label: data.surah_label,
                    audio_url: data.audio_url ?? '',
                    format: data.format ?? '',
                    quality: data.quality ?? '',
                    duration_seconds: data.duration_seconds ?? '',
                    file_size_kb: data.file_size_kb ?? '',
                };
                this.show();
            },
            show() {
                this.submitting = false;
                this.open = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },
            close() {
                if (this.submitting) return;
                this.open = false;
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
