{{--
    Quran command bar: type a verse key, chapter, "juz 3", "page 50" or words from a
    verse; navigation and verse hits come from public.quran.search.quick. Enter opens the
    full results page.
--}}
@props(['value' => ''])
<form method="GET" action="{{ route('public.quran.search') }}" role="search"
      x-data="{
          q: @js($value), open: false, loading: false, active: -1, nav: [], verses: [], timer: null,
          get items() { return [...this.nav.map(n => ({ label: n.name, url: n.url, type: n.result_type })), ...this.verses.map(v => ({ label: v.chapter_name + ' ' + v.verse_key, text: v.text, url: v.url, type: 'verse' }))]; },
          input() {
              clearTimeout(this.timer);
              if (this.q.trim().length < 1) { this.open = false; return; }
              this.timer = setTimeout(() => this.fetch(), 200);
          },
          async fetch() {
              this.loading = true;
              try {
                  const response = await fetch(@js(route('public.quran.search.quick')) + '?q=' + encodeURIComponent(this.q.trim()), { headers: { Accept: 'application/json' } });
                  const data = response.ok ? await response.json() : null;
                  this.nav = data?.result?.navigation || []; this.verses = data?.result?.verses || [];
                  this.open = true; this.active = -1;
              } catch (e) { this.open = false; }
              this.loading = false;
          },
          move(delta) { if (!this.items.length) { return; } this.active = (this.active + delta + this.items.length) % this.items.length; },
          choose(event) { if (this.active >= 0 && this.items[this.active]) { event.preventDefault(); window.location = this.items[this.active].url; } },
      }"
      @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="choose($event)" @keydown.escape="open = false"
      @click.outside="open = false"
      {{ $attributes->merge(['class' => 'relative w-full']) }}>
    <label for="quran-command-bar" class="sr-only">{{ __('quran.public.search_label') }}</label>
    <div class="relative">
        <svg class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#64CCC5] pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
        <input id="quran-command-bar" type="search" name="q" x-model="q" @input="input()" autocomplete="off" maxlength="100"
               role="combobox" :aria-expanded="open" aria-controls="quran-command-results"
               placeholder="{{ __('quran.public.search_placeholder') }}"
               class="w-full ps-12 pe-4 py-3.5 text-base rounded-2xl border shadow-sm focus:outline-none focus:ring-2 focus:ring-[#64CCC5]/60"
               :class="$store.theme.dark ? 'bg-gray-900 border-gray-800 text-white placeholder-gray-500' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'">
    </div>
    <ul id="quran-command-results" role="listbox" x-show="open && items.length" x-cloak
        class="absolute z-40 mt-2 w-full max-h-96 overflow-y-auto rounded-2xl border shadow-2xl py-2"
        :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <template x-for="(item, index) in items" :key="item.url + index">
            <li role="option" :aria-selected="active === index">
                <a :href="item.url" class="block px-4 py-2.5 text-sm transition-colors"
                   :class="active === index ? ($store.theme.dark ? 'bg-gray-800' : 'bg-gray-100') : ''">
                    <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="item.label"></span>
                    <span class="ms-2 text-xs uppercase text-[#218838]" x-text="item.type"></span>
                    <span x-show="item.text" class="mt-1 block truncate font-['Amiri'] text-base" dir="rtl" lang="ar"
                          :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="item.text"></span>
                </a>
            </li>
        </template>
    </ul>
</form>
