{{--
/**
 * Activity Feed Component
 *
 * A timeline-style activity list with user avatars, actions, and timestamps.
 * Supports different activity types with color coding and icons.
 *
 * @props
 * - title: string (optional) - Feed title
 * - activities: array (required) - Array of activity objects
 * - maxItems: int (optional) - Maximum number of items to display (default: 10)
 * - showViewAll: boolean (optional) - Show "View All" link
 * - viewAllUrl: string (optional) - URL for "View All" link
 * - delay: string (optional) - Animation delay class
 *
 * Activity Object Structure:
 * [
 *     'type' => 'created|updated|deleted|login|logout|comment|upload',
 *     'user' => ['name' => 'John Doe', 'avatar' => 'url'],
 *     'action' => 'created a new article',
 *     'target' => 'Introduction to Islam',
 *     'time' => '2 hours ago',
 *     'url' => '/admin/articles/123'
 * ]
 *
 * @example
 * <x-admin.activity-feed
 *     title="Recent Activity"
 *     :activities="$activities"
 *     :maxItems="8"
 *     :showViewAll="true"
 *     viewAllUrl="/admin/activity-logs"
 *     delay="delay-300"
 * />
 */
--}}

@props([
    'title' => 'Recent Activity',
    'activities' => [],
    'maxItems' => 10,
    'showViewAll' => true,
    'viewAllUrl' => '#',
    'delay' => ''
])

@php
    $activityTypes = [
        'created' => ['icon' => 'plus-circle', 'color' => 'green'],
        'updated' => ['icon' => 'edit', 'color' => 'blue'],
        'deleted' => ['icon' => 'trash-2', 'color' => 'red'],
        'login' => ['icon' => 'log-in', 'color' => 'purple'],
        'logout' => ['icon' => 'log-out', 'color' => 'gray'],
        'comment' => ['icon' => 'message-circle', 'color' => 'cyan'],
        'upload' => ['icon' => 'upload', 'color' => 'yellow'],
        'published' => ['icon' => 'eye', 'color' => 'green'],
        'unpublished' => ['icon' => 'eye-off', 'color' => 'orange'],
    ];

    $displayActivities = array_slice($activities, 0, $maxItems);
@endphp

<div class="animate-fadeInUp {{ $delay }}">
    <div class="rounded-2xl shadow-lg overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

        <!-- Header -->
        <div class="p-6 border-b flex items-center justify-between"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                </div>
                <h3 class="text-lg font-bold"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $title }}
                </h3>
            </div>

            @if($showViewAll)
                <a href="{{ $viewAllUrl }}"
                   class="text-sm font-semibold hover:underline flex items-center gap-1 group text-brand">
                    <span>View All</span>
                    <i data-lucide="arrow-right"
                       class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            @endif
        </div>

        <!-- Activity List -->
        <div class="divide-y"
             :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">

            @forelse($displayActivities as $index => $activity)
                @php
                    $type = $activityTypes[$activity['type']] ?? ['icon' => 'activity', 'color' => 'gray'];
                    $colorClasses = [
                        'green' => 'bg-green-500/10 text-green-500',
                        'blue' => 'bg-blue-500/10 text-blue-500',
                        'red' => 'bg-red-500/10 text-red-500',
                        'purple' => 'bg-purple-500/10 text-purple-500',
                        'gray' => 'bg-gray-500/10 text-gray-500',
                        'cyan' => 'bg-cyan-500/10 text-cyan-500',
                        'yellow' => 'bg-yellow-500/10 text-yellow-500',
                        'orange' => 'bg-orange-500/10 text-orange-500',
                    ];
                    $colorClass = $colorClasses[$type['color']] ?? $colorClasses['gray'];
                @endphp

                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200 group"
                     x-data="{ expanded: false }">
                    <div class="flex gap-4">
                        <!-- Timeline Line -->
                        <div class="relative flex flex-col items-center">
                            <!-- Avatar -->
                            <div class="relative z-10 flex-shrink-0">
                                @if(isset($activity['user']['avatar']) && $activity['user']['avatar'])
                                    <img src="{{ $activity['user']['avatar'] }}"
                                         alt="{{ $activity['user']['name'] ?? 'User' }}"
                                         class="w-10 h-10 rounded-full object-cover ring-2"
                                         :class="darkMode ? 'ring-gray-900' : 'ring-white'">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand to-secondary flex items-center justify-center ring-2"
                                         :class="darkMode ? 'ring-gray-900' : 'ring-white'">
                                        <span class="text-white text-sm font-bold">
                                            {{ substr($activity['user']['name'] ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Activity Type Badge -->
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center {{ $colorClass }} ring-2"
                                     :class="darkMode ? 'ring-gray-900' : 'ring-white'">
                                    <i data-lucide="{{ $type['icon'] }}" class="w-3 h-3"></i>
                                </div>
                            </div>

                            <!-- Vertical Line (not shown for last item) -->
                            @if($index < count($displayActivities) - 1)
                                <div class="w-px h-full flex-1 mt-2"
                                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0 pt-1">
                            <!-- User Action -->
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm"
                                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        <span class="font-semibold"
                                              :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $activity['user']['name'] ?? 'Unknown User' }}
                                        </span>
                                        {{ $activity['action'] ?? 'performed an action' }}
                                        @if(isset($activity['target']))
                                            @if(isset($activity['url']))
                                                <a href="{{ $activity['url'] }}"
                                                   class="font-medium text-brand hover:underline">
                                                    "{{ Str::limit($activity['target'], 50) }}"
                                                </a>
                                            @else
                                                <span class="font-medium"
                                                      :class="darkMode ? 'text-accent' : 'text-brand'">
                                                    "{{ Str::limit($activity['target'], 50) }}"
                                                </span>
                                            @endif
                                        @endif
                                    </p>
                                </div>

                                <!-- Timestamp -->
                                <span class="text-xs flex-shrink-0"
                                      :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                    {{ $activity['time'] ?? 'Just now' }}
                                </span>
                            </div>

                            <!-- Additional Details (Optional) -->
                            @if(isset($activity['details']))
                                <button @click="expanded = !expanded"
                                        class="text-xs text-brand hover:underline flex items-center gap-1 mt-1">
                                    <span x-text="expanded ? 'Hide details' : 'Show details'">Show details</span>
                                    <i data-lucide="chevron-down"
                                       class="w-3 h-3 transition-transform"
                                       :class="expanded ? 'rotate-180' : ''"></i>
                                </button>

                                <div x-show="expanded"
                                     x-collapse
                                     class="mt-2 p-3 rounded-lg text-xs"
                                     :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                    {{ $activity['details'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            @empty
                <!-- Empty State -->
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <i data-lucide="inbox"
                           class="w-8 h-8"
                           :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                    </div>
                    <p class="text-sm font-medium mb-1"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        No Recent Activity
                    </p>
                    <p class="text-xs"
                       :class="darkMode ? 'text-gray-600' : 'text-gray-500'">
                        Activity will appear here when users perform actions
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Footer (Optional - can show total count) -->
        @if(count($activities) > $maxItems)
            <div class="p-4 border-t text-center"
                 :class="darkMode ? 'border-gray-800 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Showing {{ $maxItems }} of {{ count($activities) }} activities
                </p>
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Auto-refresh activity feed (optional)
        /*
        setInterval(() => {
            // Fetch latest activities via AJAX
            fetch('/admin/api/activities')
                .then(response => response.json())
                .then(data => {
                    // Update activities list
                    console.log('Activities updated:', data);
                });
        }, 30000); // Refresh every 30 seconds
        */
    </script>
    @endpush
@endonce
