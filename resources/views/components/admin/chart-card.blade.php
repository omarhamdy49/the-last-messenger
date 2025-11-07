{{--
/**
 * Chart Card Component
 *
 * A reusable container for charts with title, subtitle, and time period selector.
 * Includes placeholder for Chart.js integration.
 *
 * @props
 * - title: string (required) - Chart title
 * - subtitle: string (optional) - Additional description
 * - chartId: string (required) - Unique ID for the canvas element
 * - height: string (optional) - Chart height (default: '300px')
 * - periods: array (optional) - Time period options for filter
 * - defaultPeriod: string (optional) - Default selected period
 * - delay: string (optional) - Animation delay class
 *
 * @slots
 * - actions: Additional action buttons in header
 *
 * @example
 * <x-admin.chart-card
 *     title="User Growth"
 *     subtitle="Monthly active users over time"
 *     chartId="userGrowthChart"
 *     height="400px"
 *     :periods="['7D', '30D', '3M', '1Y', 'ALL']"
 *     defaultPeriod="30D"
 *     delay="delay-200"
 * >
 *     <x-slot name="actions">
 *         <button>Export</button>
 *     </x-slot>
 * </x-admin.chart-card>
 */
--}}

@props([
    'title' => 'Chart',
    'subtitle' => null,
    'chartId' => 'chart-' . uniqid(),
    'height' => '300px',
    'periods' => ['7D', '30D', '3M', '1Y', 'ALL'],
    'defaultPeriod' => '30D',
    'delay' => ''
])

<div class="animate-fadeInUp {{ $delay }}"
     x-data="{
         selectedPeriod: '{{ $defaultPeriod }}',
         chartInstance: null,

         selectPeriod(period) {
             this.selectedPeriod = period;
             // Emit event for chart update
             this.$dispatch('period-changed', { period: period, chartId: '{{ $chartId }}' });
         },

         initChart() {
             // Placeholder for Chart.js initialization
             // Will be implemented when Chart.js is integrated
             console.log('Chart initialized:', '{{ $chartId }}');
         }
     }"
     x-init="initChart()">

    <div class="rounded-2xl shadow-lg overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

        <!-- Card Header -->
        <div class="p-6 border-b"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Title Section -->
                <div class="flex-1">
                    <h3 class="text-lg font-bold mb-1"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $title }}
                    </h3>
                    @if($subtitle)
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>

                <!-- Action Section -->
                <div class="flex items-center gap-3">
                    <!-- Period Selector -->
                    @if(count($periods) > 0)
                        <div class="flex items-center gap-1 p-1 rounded-lg"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                            @foreach($periods as $period)
                                <button @click="selectPeriod('{{ $period }}')"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all duration-200"
                                        :class="selectedPeriod === '{{ $period }}'
                                            ? 'bg-gradient-to-r from-brand to-secondary text-white shadow-md'
                                            : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                                    {{ $period }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <!-- Custom Actions Slot -->
                    @isset($actions)
                        {{ $actions }}
                    @endisset

                    <!-- Export Button -->
                    <button class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                            title="Export Chart">
                        <i data-lucide="download" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="p-6">
            <div class="relative" style="height: {{ $height }}">
                <!-- Canvas for Chart.js -->
                <canvas id="{{ $chartId }}"
                        class="w-full h-full"
                        x-ref="chartCanvas"></canvas>

                <!-- Placeholder Message (shown until chart is loaded) -->
                <div class="absolute inset-0 flex flex-col items-center justify-center"
                     :class="darkMode ? 'bg-gray-900' : 'bg-white'"
                     x-show="!chartInstance">
                    <div class="text-center">
                        <!-- Animated Icon -->
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-gradient-to-br from-brand to-secondary animate-pulse">
                            <i data-lucide="bar-chart-3" class="w-8 h-8 text-white"></i>
                        </div>

                        <!-- Message -->
                        <p class="text-lg font-semibold mb-2"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Chart Ready
                        </p>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            Integrate Chart.js to display data visualization
                        </p>

                        <!-- Code Snippet Hint -->
                        <div class="mt-4 p-3 rounded-lg text-xs text-left max-w-md mx-auto"
                             :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                            <code>
                                // Listen for period changes<br>
                                document.addEventListener('period-changed', (e) => {<br>
                                &nbsp;&nbsp;if (e.detail.chartId === '{{ $chartId }}') {<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;updateChart(e.detail.period);<br>
                                &nbsp;&nbsp;}<br>
                                });
                            </code>
                        </div>
                    </div>
                </div>

                <!-- Loading Skeleton (optional - can be shown during data fetch) -->
                <div class="absolute inset-0 hidden"
                     :class="darkMode ? 'bg-gray-900' : 'bg-white'"
                     x-show="false">
                    <div class="flex items-center justify-center h-full">
                        <div class="flex gap-2">
                            @for($i = 0; $i < 5; $i++)
                                <div class="w-12 h-full rounded animate-pulse"
                                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"
                                     style="height: {{ rand(40, 100) }}%; animation-delay: {{ $i * 0.1 }}s;"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Statistics (Optional Summary) -->
            @isset($statistics)
                <div class="mt-6 pt-6 border-t grid grid-cols-2 sm:grid-cols-4 gap-4"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    {{ $statistics }}
                </div>
            @endisset
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Chart.js Integration Helper
        // Uncomment and modify when integrating Chart.js

        /*
        document.addEventListener('alpine:initialized', () => {
            // Initialize all charts
            document.querySelectorAll('[id^="chart-"]').forEach(canvas => {
                const ctx = canvas.getContext('2d');
                const chartId = canvas.id;

                // Example Chart.js initialization
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Dataset',
                            data: [12, 19, 3, 5, 2, 3],
                            borderColor: '#0056b3',
                            backgroundColor: 'rgba(0, 86, 179, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        });
        */
    </script>
    @endpush
@endonce
