@extends('layouts.admin')

@php
    $t = fn (string $key, array $replace = []) => __('quran.playlists_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputTheme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $labelTheme = "darkMode ? 'text-gray-300' : 'text-gray-700'";
    $num = fn ($n) => $locale === 'ar' ? arabic_digits($n) : (string) $n;
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="list-music" class="w-7 h-7 text-white" aria-hidden="true"></i>
            </div>
            {{ $t('edit_title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $playlist->name }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('public.quran.playlists.show', $playlist->slug) }}" target="_blank" rel="noopener"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="external-link" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('view_public') }}</span>
        </a>
        <a href="{{ route('admin.playlists.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-{{ in_array($locale, ['ar', 'ur'], true) ? 'right' : 'left' }}" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('back') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.playlists.update', $playlist->id) }}" class="grid grid-cols-1 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)] gap-6"
      x-data="{ saving: false }" @submit="saving = true">
    @csrf
    @method('PUT')

    <div class="space-y-6">
        <!-- Basics -->
        <section class="p-6 rounded-2xl border space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div>
                <label for="name" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $playlist->name) }}" required minlength="2" maxlength="80"
                       class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                @error('name')<p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('description') }}</label>
                <textarea id="description" name="description" rows="3" maxlength="300" class="{{ $inputClass }}" :class="{{ $inputTheme }}">{{ old('description', $playlist->description) }}</textarea>
                @error('description')<p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <input type="hidden" name="is_public" value="0">
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $playlist->is_public)) class="mt-1 rounded border-gray-300 text-accent focus:ring-accent">
                    <span class="text-sm" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $t('is_public') }}</span>
                </label>
                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $playlist->is_featured)) class="mt-1 rounded border-gray-300 text-accent focus:ring-accent">
                    <span class="text-sm" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $t('is_featured') }}</span>
                </label>
            </div>
        </section>

        <!-- Items: Alpine-managed rows. A removed row is simply not posted; rows always stay in surah order. -->
        <section class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
                 x-data="playlistItems(@js($rows), @js($surahs), @js(route('admin.playlists.coverage', ['surah' => '__S__'])))">
            <div class="px-6 py-4 border-b flex flex-wrap items-start justify-between gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <div>
                    <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t('items_title') }}</h2>
                    <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('items_hint') }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold tabular-nums" :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'"
                      x-text="@js($t('rows_count', ['count' => ':count'])).replace(':count', fmt(rows.length))"></span>
            </div>

            {{-- Add surah --}}
            <div class="px-6 py-4 border-b grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end" :class="darkMode ? 'border-gray-800 bg-gray-900/30' : 'border-gray-100 bg-gray-50/60'">
                <div>
                    <label for="add-surah" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('add_surah') }}</label>
                    <select id="add-surah" x-model.number="addSurah" @change="loadOptions()" class="{{ $inputClass }}" :class="{{ $inputTheme }}" :disabled="!available.length">
                        <option value="" x-text="available.length ? @js($t('pick_surah')) : @js($t('all_added'))"></option>
                        <template x-for="s in available" :key="s.id">
                            <option :value="s.id" x-text="`${fmt(s.id)} · ${s.name}`"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="add-recording" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('pick_recording') }}</label>
                    <select id="add-recording" x-model.number="addRecording" class="{{ $inputClass }}" :class="{{ $inputTheme }}" :disabled="!addSurah || loading">
                        <option value="" x-text="loading ? @js($t('loading')) : (addSurah && !addOptions.length ? @js($t('no_options')) : @js($t('no_recording')))"></option>
                        <template x-for="o in addOptions" :key="o.id">
                            <option :value="o.id" x-text="o.label"></option>
                        </template>
                    </select>
                </div>
                <button type="button" @click="add()" :disabled="!addSurah || loading"
                        class="h-[46px] px-5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow flex items-center gap-2 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-accent">
                    <i data-lucide="plus" class="w-4 h-4"></i>{{ $t('add') }}
                </button>
            </div>

            <div class="overflow-x-auto" x-show="rows.length">
                <table class="w-full text-sm">
                    <thead :class="darkMode ? 'bg-gray-800/60 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start font-semibold w-16">#</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_surah') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_recording') }}</th>
                            <th scope="col" class="px-4 py-3 text-end font-semibold w-24">{{ $t('col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        <template x-for="row in rows" :key="row.surah">
                            <tr :class="!row.recording ? 'bg-amber-50 dark:bg-amber-900/10' : ''">
                                <td class="px-4 py-3 tabular-nums font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="fmt(row.surah)"></td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    <span x-text="row.name"></span>
                                    <span class="block text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="@js(__('quran.playlists.verses', ['count' => ':count'])).replace(':count', fmt(row.verses ?? 0))"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <label :for="`item-${row.surah}`" class="sr-only">{{ $t('col_recording') }}</label>
                                    <select :id="`item-${row.surah}`" :name="`items[${row.surah}]`" x-model.number="row.recording" class="{{ $inputClass }} max-w-md" :class="{{ $inputTheme }}">
                                        <option value="">{{ $t('no_recording') }}</option>
                                        <template x-for="o in row.options" :key="o.id">
                                            <option :value="o.id" :selected="row.recording === o.id" x-text="o.label"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <button type="button" @click="remove(row.surah)"
                                            class="w-9 h-9 rounded-lg inline-flex items-center justify-center text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors focus:outline-none focus:ring-2 focus:ring-red-300"
                                            :title="@js($t('remove'))" :aria-label="`${@js($t('remove'))} ${row.name}`">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="!rows.length" x-cloak class="px-6 py-10 text-center text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('empty_title') }}</p>
            {{-- An empty list still posts `items` so the controller clears the rows instead of ignoring the field. --}}
            <template x-if="!rows.length"><input type="hidden" name="items" value=""></template>
        </section>
    </div>

    <!-- Aside -->
    <aside class="space-y-6">
        <section class="p-6 rounded-2xl border space-y-4 text-sm" :class="darkMode ? 'bg-gray-900/50 border-gray-800 text-gray-300' : 'bg-white border-gray-200 text-gray-700'">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('owner') }}</p>
                <p class="font-bold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $playlist->owner?->name }}</p>
                <p dir="ltr" class="text-xs">{{ $playlist->owner?->email }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('slug') }}</p>
                <p dir="ltr" class="font-mono text-xs mt-1 break-all">{{ route('public.quran.playlists.show', $playlist->slug) }}</p>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('scope') }}</p><p class="font-bold mt-0.5">{{ $t('scope_'.$playlist->scope) }}</p></div>
                <div><p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('col_surahs') }}</p><p class="font-bold mt-0.5 tabular-nums">{{ $num($playlist->items_count) }}</p></div>
                <div><p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('plays') }}</p><p class="font-bold mt-0.5 tabular-nums">{{ $num($playlist->plays_count) }}</p></div>
            </div>
        </section>

        <div class="p-6 rounded-2xl border space-y-3" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <button type="submit" :disabled="saving"
                    class="w-full px-6 py-3 rounded-xl font-semibold shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-accent disabled:opacity-60">
                <i data-lucide="save" class="w-5 h-5" aria-hidden="true"></i>
                <span>{{ $t('save') }}</span>
            </button>
        </div>
    </aside>
</form>

<form method="POST" action="{{ route('admin.playlists.destroy', $playlist->id) }}" class="mt-6"
      onsubmit="return window.confirmSubmit(this, { message: {{ Js::from($t('delete_message', ['name' => $playlist->name])) }} })">
    @csrf
    @method('DELETE')
    <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-red-600 border-2 border-red-200 hover:bg-red-50 dark:border-red-900/50 dark:hover:bg-red-900/20 flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-red-300">
        <i data-lucide="trash-2" class="w-4 h-4"></i>{{ $t('delete') }}
    </button>
</form>
@endsection

@push('scripts')
<script>
    function playlistItems(rows, surahs, coverageUrl) {
        const fmt = (n) => @js(app()->getLocale()) === 'ar' ? String(n).replace(/[0-9]/g, d => '٠١٢٣٤٥٦٧٨٩'[d]) : String(n);
        const bySurah = (a, b) => a.surah - b.surah;
        return {
            rows: [...rows].sort(bySurah), surahs, fmt,
            addSurah: '', addRecording: '', addOptions: [], loading: false,
            get available() { const used = new Set(this.rows.map(r => r.surah)); return this.surahs.filter(s => !used.has(s.id)); },
            async loadOptions() {
                this.addOptions = []; this.addRecording = '';
                if (!this.addSurah) return;
                this.loading = true;
                try {
                    const res = await fetch(coverageUrl.replace('__S__', this.addSurah), { headers: { Accept: 'application/json' } });
                    this.addOptions = res.ok ? (await res.json()).options : [];
                } catch (e) { this.addOptions = []; }
                finally { this.loading = false; }
            },
            add() {
                const s = this.surahs.find(x => x.id === this.addSurah);
                if (!s || this.rows.some(r => r.surah === s.id)) return;
                // Always in mushaf order, whatever order the admin adds them in.
                this.rows = [...this.rows, { surah: s.id, name: s.name, verses: s.verses, recording: this.addRecording || null, options: this.addOptions }].sort(bySurah);
                this.addSurah = ''; this.addRecording = ''; this.addOptions = [];
                this.$nextTick(() => window.lucide?.createIcons());
            },
            remove(surah) { this.rows = this.rows.filter(r => r.surah !== surah); },
        };
    }
</script>
@endpush
