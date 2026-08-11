{{--
/**
 * Table Row Component
 *
 * Dynamic table row with checkboxes, data cells, and action button.
 * Action menu is managed by the parent DataTable via event dispatching.
 * This avoids x-teleport inside x-for, which can be unreliable.
 *
 * @props
 * - columns: array (required) - Column definitions
 * - actions: array (optional) - Row actions ['edit', 'delete', 'view']
 * - hasBulkSelect: bool (optional) - Show checkbox
 * - viewRouteUrl: string|null - View URL with placeholder
 * - routePlaceholder: int - The placeholder value to replace with row.id (default 9999999)
 */
--}}

@props([
    'columns' => [],
    'actions' => [],
    'hasBulkSelect' => false,
    'editRouteUrl' => null,
    'deleteRouteUrl' => null,
    'viewRouteUrl' => null,
    'routePlaceholder' => 9999999,
])

@php
    $placeholder = (string) $routePlaceholder;
@endphp

<tr class="border-b transition-all duration-200 group"
    :class="[
        darkMode ? 'border-gray-800 hover:bg-gray-800/60' : 'border-gray-200 hover:bg-gray-50',
        index % 2 === 0 ? (darkMode ? 'bg-gray-900/20' : 'bg-white') : (darkMode ? 'bg-gray-900/40' : 'bg-gray-50/40'),
        selectedRows.includes(row.id) ? (darkMode ? 'bg-blue-900/20 border-blue-700' : 'bg-blue-50 border-blue-300') : ''
    ]">

    <!-- Checkbox Cell -->
    @if($hasBulkSelect)
        <td class="px-6 py-4">
            <div class="flex items-center justify-center">
                <input type="checkbox"
                       :checked="selectedRows.includes(row.id)"
                       @change="toggleRowSelection(row.id)"
                       class="w-5 h-5 rounded-md cursor-pointer transition-all
                              border-2 focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ? 'border-gray-600 bg-gray-700' : 'border-gray-300 bg-white'">
            </div>
        </td>
    @endif

    <!-- Data Cells -->
    @foreach($columns as $column)
        @php
            $key      = $column['key']       ?? '';
            $type     = $column['type']      ?? 'text';
            $badge    = $column['badge']     ?? false;
            $truncate = $column['truncate']  ?? null;
            $align    = $column['align']     ?? 'start';
            $link     = $column['link']      ?? false;
            $imageKey = $column['image_key'] ?? 'thumb';
            $badgeColors = $column['badgeColors'] ?? [
                'published' => 'from-green-500 to-emerald-600',
                'pending'   => 'from-yellow-500 to-orange-500',
                'draft'     => 'from-gray-500 to-gray-600',
                'scheduled' => 'from-blue-500 to-indigo-600',
                'archived'  => 'from-red-500 to-rose-600',
            ];
        @endphp

        <td class="px-4 py-3 text-{{ $align }}"
            :class="darkMode ? 'text-gray-300' : 'text-gray-700'">

            @if($type === 'image_link')
                {{-- Combined thumbnail + title link in one cell --}}
                <div class="flex items-center gap-3 min-w-0" x-data="{ imageFailed: false }">
                    {{-- A stored path that 404s (missing file, unlinked storage
                         disk) must degrade to the same placeholder as "no image"
                         rather than a broken-image glyph. --}}
                    <template x-if="row.{{ $imageKey }} && !imageFailed">
                        <img :src="row.{{ $imageKey }}"
                             :alt="row.{{ $key }} || ''"
                             loading="lazy"
                             {{-- x-on: longhand — @error is a Blade directive and breaks compilation --}}
                             x-on:error="imageFailed = true"
                             class="w-10 h-10 rounded-lg object-cover shadow-sm flex-shrink-0 bg-gray-100 dark:bg-gray-800">
                    </template>
                    <template x-if="!row.{{ $imageKey }} || imageFailed">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                            <i data-lucide="image" class="w-5 h-5"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        </div>
                    </template>

                    @if($link && $viewRouteUrl)
                        <a :href="'{{ $viewRouteUrl }}'.replace('{{ $placeholder }}', row.id)"
                           class="font-semibold text-sm hover:text-accent transition-colors leading-snug min-w-0"
                           :class="darkMode ? 'text-white hover:text-accent' : 'text-gray-900 hover:text-accent'">
                            @if($truncate)
                                <span :title="row.{{ $key }}"
                                      x-text="row.{{ $key }} && row.{{ $key }}.length > {{ $truncate }}
                                          ? row.{{ $key }}.substring(0, {{ $truncate }}) + '\u2026'
                                          : (row.{{ $key }} || '-')">
                                </span>
                            @else
                                <span x-text="row.{{ $key }} || '-'"></span>
                            @endif
                        </a>
                    @else
                        <span class="font-semibold text-sm min-w-0"
                              :class="darkMode ? 'text-white' : 'text-gray-900'"
                              x-text="row.{{ $key }} || '-'">
                        </span>
                    @endif
                </div>

            @elseif($type === 'image')
                <template x-if="row.{{ $key }}">
                    <img :src="row.{{ $key }}"
                         :alt="row.title || 'Image'"
                         class="w-12 h-12 rounded-lg object-cover shadow-md">
                </template>
                <template x-if="!row.{{ $key }}">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                        <i data-lucide="image" class="w-6 h-6"
                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                    </div>
                </template>

            @elseif($badge)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-sm bg-gradient-to-r"
                      :class="{
                          @foreach($badgeColors as $status => $colors)
                              '{{ $colors }}': String(row.{{ $key }} || '').toLowerCase() === '{{ $status }}',
                          @endforeach
                          'from-gray-400 to-gray-500': !['{{ implode("','", array_keys($badgeColors)) }}'].includes(String(row.{{ $key }} || '').toLowerCase())
                      }">
                    <span class="w-1.5 h-1.5 rounded-full bg-white/80 animate-pulse"></span>
                    <span x-text="row.{{ $key }} || '-'"></span>
                </span>

            @elseif($type === 'date')
                <span class="text-sm font-medium"
                      x-text="row.{{ $key }} ? new Date(row.{{ $key }}).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '-'">
                </span>

            @elseif($type === 'datetime')
                <div class="text-sm">
                    <div class="font-medium"
                         x-text="row.{{ $key }} ? new Date(row.{{ $key }}).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '-'">
                    </div>
                    <div class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"
                         x-text="row.{{ $key }} ? new Date(row.{{ $key }}).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : ''">
                    </div>
                </div>

            @elseif($type === 'boolean')
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold shadow-sm"
                      :class="row.{{ $key }} ?
                          'bg-gradient-to-r from-green-500 to-emerald-600 text-white' :
                          (darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-700')">
                    <i :data-lucide="row.{{ $key }} ? 'check' : 'x'" class="w-3 h-3"></i>
                </span>

            @elseif($type === 'number')
                <span class="text-sm font-bold gradient-text"
                      x-text="(row.{{ $key }} !== null && row.{{ $key }} !== undefined) ? Number(row.{{ $key }}).toLocaleString() : '-'">
                </span>

            @elseif($type === 'time')
                <span class="text-sm font-medium"
                      x-text="row.{{ $key }} ? row.{{ $key }} + ' min' : '-'">
                </span>

            @elseif($link && $viewRouteUrl)
                <a :href="'{{ $viewRouteUrl }}'.replace('{{ $placeholder }}', row.id)"
                   class="font-semibold hover:text-accent transition-colors"
                   :class="darkMode ? 'text-white hover:text-accent' : 'text-gray-900 hover:text-accent'">
                    @if($truncate)
                        <span :title="row.{{ $key }}"
                              x-text="row.{{ $key }} && row.{{ $key }}.length > {{ $truncate }}
                                  ? row.{{ $key }}.substring(0, {{ $truncate }}) + '\u2026'
                                  : (row.{{ $key }} || '-')">
                        </span>
                    @else
                        <span x-text="row.{{ $key }} || '-'"></span>
                    @endif
                </a>

            @else
                <span class="text-sm font-medium"
                      :class="darkMode ? 'text-white' : 'text-gray-900'"
                      @if($truncate)
                          :title="row.{{ $key }}"
                          x-text="row.{{ $key }} && String(row.{{ $key }}).length > {{ $truncate }}
                              ? String(row.{{ $key }}).substring(0, {{ $truncate }}) + '\u2026'
                              : (row.{{ $key }} !== null && row.{{ $key }} !== undefined ? String(row.{{ $key }}) : '-')"
                      @else
                          x-text="row.{{ $key }} !== null && row.{{ $key }} !== undefined ? String(row.{{ $key }}) : '-'"
                      @endif>
                </span>
            @endif
        </td>
    @endforeach

    <!-- Actions Cell -->
    @if(!empty($actions))
        <td class="px-4 py-3 text-center">
            <button @click.stop="$dispatch('open-row-menu', { row: row, rect: $el.getBoundingClientRect() })"
                    class="p-2 rounded-lg transition-all hover:scale-110"
                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                <i data-lucide="more-vertical" class="w-5 h-5"></i>
            </button>
        </td>
    @endif
</tr>
