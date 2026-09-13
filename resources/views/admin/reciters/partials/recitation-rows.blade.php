{{-- One page of surah audio rows for admin.reciters.show; injected inside the recitationManager scope. --}}
@php
    $locale = app()->getLocale();
    $subtitle = $reciter->getDisplayName($locale);
    $providers = $reciter->sources->pluck('provider', 'id');
@endphp
@foreach($rows as $row)
    @php
        $rec = $row['recitation'];
        $surahLabel = $row['number'].' · '.$row['name_ar'];
        $sourceId = $rec?->source_id ?? $selectedSource;
    @endphp
    <li class="flex flex-wrap items-center gap-x-4 gap-y-3 px-4 sm:px-6 py-4 transition-colors motion-reduce:transition-none"
        :class="darkMode ? 'hover:bg-gray-800/40' : 'hover:bg-gray-50'">

        {{-- Surah number + names --}}
        <div class="flex items-center gap-3 min-w-0 flex-1 basis-56">
            <span class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm tabular-nums {{ $rec ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] text-white shadow-md' : '' }}"
                  @unless($rec) :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'" @endunless>
                {{ $row['number'] }}
            </span>
            <div class="min-w-0">
                <p class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" dir="rtl">{{ $row['name_ar'] }}</p>
                <p class="text-xs truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" dir="ltr">
                    {{ $row['transliteration'] ?: $row['name_en'] }} · {{ __('quran.reciter_admin.verses_count', ['count' => $row['verses_count']]) }}
                </p>
            </div>
        </div>

        {{-- Status (icon + text, not colour only) --}}
        @if($rec)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                  :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-800'">
                <i data-lucide="check" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ __('quran.recitations.has_audio') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                <i data-lucide="minus" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ __('quran.recitations.no_audio') }}
            </span>
        @endif

        {{-- Audio metadata --}}
        @if($rec)
            <dl class="hidden md:flex flex-wrap items-center gap-2 text-xs" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                <div class="px-2 py-1 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <dt class="sr-only">{{ __('quran.recitations.format') }}</dt><dd class="uppercase">{{ $rec->format ?? '—' }}</dd>
                </div>
                @if($rec->quality)
                    <div class="px-2 py-1 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <dt class="sr-only">{{ __('quran.recitations.quality') }}</dt><dd>{{ __('quran.recitations.'.$rec->quality) }}</dd>
                    </div>
                @endif
                @if($rec->duration_seconds)
                    <div class="px-2 py-1 rounded-lg inline-flex items-center gap-1" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <dt><i data-lucide="clock" class="w-3.5 h-3.5 text-accent" aria-hidden="true"></i><span class="sr-only">{{ __('quran.recitations.duration') }}</span></dt>
                        <dd class="tabular-nums" dir="ltr">{{ gmdate($rec->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $rec->duration_seconds) }}</dd>
                    </div>
                @endif
                <div class="px-2 py-1 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <dt class="sr-only">{{ __('quran.reciter_admin.provider') }}</dt><dd dir="ltr">{{ $providers->get($rec->source_id) ?? __('quran.reciter_admin.unknown_provider') }}</dd>
                </div>
            </dl>
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-2 ms-auto">
            @if($rec)
                @if($rec->audio_url)
                    <button type="button"
                            @click="$store.audioPlayer.play({{ \Illuminate\Support\Js::from(['url' => $rec->audio_url, 'title' => $surahLabel, 'subtitle' => $subtitle]) }})"
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                            :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-100 hover:bg-gray-200'"
                            aria-label="{{ __('quran.recitations.play') }}: {{ $surahLabel }}">
                        <i data-lucide="play" class="w-4 h-4" aria-hidden="true"></i>
                    </button>
                @endif
                <button type="button"
                        @click="openEdit({{ \Illuminate\Support\Js::from([
                            'id' => $rec->id,
                            'update_url' => route('admin.reciters.recitations.update', [$reciter->id, $rec->id]),
                            'surah_id' => $row['surah_id'],
                            'source_id' => $sourceId,
                            'surah_label' => $surahLabel,
                            'audio_url' => $rec->audio_url,
                            'format' => $rec->format,
                            'quality' => $rec->quality,
                            'duration_seconds' => $rec->duration_seconds,
                            'file_size_kb' => $rec->file_size_kb,
                        ]) }})"
                        class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                        :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-blue-400' : 'bg-blue-50 hover:bg-blue-100 text-blue-600'"
                        aria-label="{{ __('quran.recitations.edit_audio') }}: {{ $surahLabel }}">
                    <i data-lucide="pencil" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <form method="POST"
                      action="{{ route('admin.reciters.recitations.destroy', [$reciter->id, $rec->id]) }}"
                      @submit.prevent="$dispatch('show-confirm', { title: {{ \Illuminate\Support\Js::from(__('quran.recitations.confirm_delete')) }}, message: {{ \Illuminate\Support\Js::from(__('quran.recitations.confirm_delete_message')) }}, type: 'danger', onConfirm: () => $el.submit() })">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                            :class="darkMode ? 'bg-red-900/20 hover:bg-red-900/40 text-red-400' : 'bg-red-50 hover:bg-red-100 text-red-600'"
                            aria-label="{{ __('quran.recitations.delete_audio') }}: {{ $surahLabel }}">
                        <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                    </button>
                </form>
            @else
                <button type="button"
                        @click="openAdd({{ \Illuminate\Support\Js::from(['surah_id' => $row['surah_id'], 'source_id' => $sourceId, 'surah_label' => $surahLabel]) }})"
                        class="min-h-10 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-semibold transition-all hover:scale-105 motion-reduce:transition-none motion-reduce:hover:scale-100 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow focus:outline-none focus:ring-2 focus:ring-accent">
                    <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                    <span>{{ __('quran.recitations.add_audio') }}</span>
                </button>
            @endif
        </div>
    </li>
@endforeach
