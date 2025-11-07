{{--
/**
 * Publish Scheduler Component
 *
 * Date-time picker for scheduling article publishing with timezone support.
 * Features quick actions, visual indicators, and validation.
 *
 * @props
 * - article: Article model (required) - The article to schedule
 * - action: string (optional) - Form action URL (defaults to current article update route)
 *
 * @example
 * <x-admin.publish-scheduler :article="$article" />
 */
--}}

@props([
    'article',
    'action' => null
])

@php
    $scheduleDate = $article->published_at?->format('Y-m-d\TH:i') ?? null;
    $userTimezone = config('app.timezone');
    $isScheduled = $article->status === 'scheduled' && $article->published_at && $article->published_at->isFuture();
    $isPast = $article->published_at && $article->published_at->isPast();
@endphp

<div class="rounded-2xl overflow-hidden shadow-lg glass border p-6"
     :class="darkMode ? 'glass-dark' : 'glass-light'"
     x-data="{
         showScheduler: {{ $isScheduled ? 'true' : 'false' }},
         scheduleDate: '{{ $scheduleDate }}',
         userTimezone: '{{ $userTimezone }}',
         currentTimezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
         isPastDate: false,

         init() {
             this.updateTimezone();
             this.$watch('scheduleDate', value => {
                 this.checkPastDate();
             });
         },

         updateTimezone() {
             this.currentTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
         },

         checkPastDate() {
             if (!this.scheduleDate) {
                 this.isPastDate = false;
                 return;
             }
             const selected = new Date(this.scheduleDate);
             const now = new Date();
             this.isPastDate = selected < now;
         },

         formatDateForDisplay(dateString) {
             if (!dateString) return '';
             const date = new Date(dateString);
             return date.toLocaleString('en-US', {
                 month: 'short',
                 day: 'numeric',
                 year: 'numeric',
                 hour: '2-digit',
                 minute: '2-digit'
             });
         },

         setQuickDate(hours) {
             const date = new Date();
             date.setHours(date.getHours() + hours);
             this.scheduleDate = date.toISOString().slice(0, 16);
         },

         publishNow() {
             const form = this.$refs.publishForm;
             const input = document.createElement('input');
             input.type = 'hidden';
             input.name = 'publish_now';
             input.value = '1';
             form.appendChild(input);
             form.submit();
         }
     }">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                <i data-lucide="calendar-clock" class="w-5 h-5 text-white"></i>
            </div>
            <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Publishing Schedule
            </h3>
        </div>

        @if($isScheduled)
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-white">
                <i data-lucide="clock" class="w-3 h-3 inline-block -mt-1"></i>
                Scheduled
            </span>
        @endif
    </div>

    <!-- Current Status -->
    @if($isScheduled)
        <div class="mb-4 p-4 rounded-xl border"
             :class="darkMode ? 'bg-yellow-500/10 border-yellow-500/20' : 'bg-yellow-50 border-yellow-200'">
            <div class="flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5"
                   :class="darkMode ? 'text-yellow-400' : 'text-yellow-600'"></i>
                <div>
                    <p class="font-semibold mb-1"
                       :class="darkMode ? 'text-yellow-400' : 'text-yellow-700'">
                        Scheduled for Publishing
                    </p>
                    <p class="text-sm"
                       :class="darkMode ? 'text-yellow-300' : 'text-yellow-600'">
                        This article will be automatically published on
                        <span class="font-semibold">{{ $article->published_at->format('M d, Y \a\t g:i A') }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form x-ref="publishForm"
          action="{{ $action ?? route('admin.articles.schedule', $article->id) }}"
          method="POST"
          class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Quick Action Buttons -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Publish Now Button -->
            <button type="button"
                    @click="publishNow()"
                    class="px-4 py-3 rounded-xl font-semibold transition-all hover:shadow-lg
                           bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90
                           flex items-center justify-center gap-2">
                <i data-lucide="rocket" class="w-4 h-4"></i>
                <span>Publish Now</span>
            </button>

            <!-- Schedule Toggle Button -->
            <button type="button"
                    @click="showScheduler = !showScheduler"
                    class="px-4 py-3 rounded-xl font-semibold transition-all border
                           flex items-center justify-center gap-2"
                    :class="showScheduler ?
                        'bg-gradient-to-r from-brand to-secondary text-white border-transparent' :
                        (darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50')">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                <span x-text="showScheduler ? 'Hide Scheduler' : 'Schedule'"></span>
            </button>
        </div>

        <!-- Scheduler Section -->
        <div x-show="showScheduler"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="space-y-4">

            <!-- Quick Schedule Buttons -->
            <div class="grid grid-cols-3 gap-2">
                <button type="button"
                        @click="setQuickDate(1)"
                        class="px-3 py-2 rounded-lg text-sm font-semibold transition-all hover:shadow
                               border"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    +1 Hour
                </button>
                <button type="button"
                        @click="setQuickDate(24)"
                        class="px-3 py-2 rounded-lg text-sm font-semibold transition-all hover:shadow
                               border"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    +1 Day
                </button>
                <button type="button"
                        @click="setQuickDate(168)"
                        class="px-3 py-2 rounded-lg text-sm font-semibold transition-all hover:shadow
                               border"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    +1 Week
                </button>
            </div>

            <!-- Date-Time Picker -->
            <div>
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <i data-lucide="calendar-clock" class="w-4 h-4 inline-block -mt-1 me-1"></i>
                    Select Date & Time
                </label>
                <input type="datetime-local"
                       name="published_at"
                       x-model="scheduleDate"
                       :min="new Date().toISOString().slice(0, 16)"
                       class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                       :class="darkMode ?
                           'bg-gray-800 border-gray-700 text-white' :
                           'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Past Date Warning -->
            <div x-show="isPastDate"
                 x-cloak
                 x-transition
                 class="p-3 rounded-xl border"
                 :class="darkMode ? 'bg-red-500/10 border-red-500/20' : 'bg-red-50 border-red-200'">
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
                    <p class="text-sm font-medium"
                       :class="darkMode ? 'text-red-400' : 'text-red-600'">
                        The selected date is in the past. The article will be published immediately.
                    </p>
                </div>
            </div>

            <!-- Selected Date Display -->
            <div x-show="scheduleDate"
                 x-cloak
                 class="p-4 rounded-xl border"
                 :class="darkMode ? 'bg-accent/5 border-accent/20' : 'bg-accent/5 border-accent/20'">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                        <i data-lucide="calendar-check" class="w-4 h-4 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold mb-1"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Scheduled Publishing Date
                        </p>
                        <p class="text-lg font-bold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'"
                           x-text="formatDateForDisplay(scheduleDate)">
                        </p>
                        <p class="text-xs mt-1"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <i data-lucide="clock" class="w-3 h-3 inline-block -mt-1"></i>
                            Timezone: <span x-text="currentTimezone"></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timezone Info -->
            <div class="flex items-center gap-2 text-xs"
                 :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <i data-lucide="globe" class="w-3 h-3"></i>
                <span>Server timezone: {{ config('app.timezone') }}</span>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:shadow-lg
                           bg-gradient-to-r from-brand to-secondary text-white hover:opacity-90
                           flex items-center justify-center gap-2">
                <i data-lucide="check" class="w-5 h-5"></i>
                <span>Save Schedule</span>
            </button>
        </div>
    </form>

    <!-- Help Text -->
    <div class="mt-4 pt-4 border-t"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <i data-lucide="info" class="w-3 h-3 inline-block -mt-1 me-1"></i>
            Scheduled articles will be automatically published at the specified time.
        </p>
    </div>
</div>

@push('styles')
<style>
    /* Custom datetime input styling */
    input[type="datetime-local"] {
        font-family: 'Cairo', sans-serif;
    }

    input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        filter: opacity(0.6);
    }

    .dark input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        filter: invert(1) opacity(0.6);
    }

    input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
        filter: opacity(1);
    }

    .dark input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
        filter: invert(1) opacity(1);
    }
</style>
@endpush
