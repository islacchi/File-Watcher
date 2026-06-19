<x-layouts.app :title="'Analytics'">
    @php
        $eventTypes = ['CREATED', 'MODIFIED', 'DELETED', 'RENAMED', 'MOVED', 'MOVED_AND_RENAMED'];
        $typeLabels = [
            'CREATED' => 'Created',
            'MODIFIED' => 'Modified',
            'DELETED' => 'Deleted',
            'RENAMED' => 'Renamed',
            'MOVED' => 'Moved',
            'MOVED_AND_RENAMED' => 'Moved & Renamed',
        ];
        $typeColors = [
            'CREATED' => 'bg-green-500',
            'MODIFIED' => 'bg-blue-500',
            'DELETED' => 'bg-red-500',
            'RENAMED' => 'bg-yellow-500',
            'MOVED' => 'bg-teal-400',
            'MOVED_AND_RENAMED' => 'bg-indigo-400',
        ];
        $typeHex = [
            'CREATED' => '#22c55e',
            'MODIFIED' => '#3b82f6',
            'DELETED' => '#ef4444',
            'RENAMED' => '#eab308',
            'MOVED' => '#2dd4bf',
            'MOVED_AND_RENAMED' => '#818cf8',
        ];
        $sizeBuckets = ['<10 KB', '10–50 KB', '50–200 KB', '200 KB–1 MB', '1–10 MB', '>10 MB'];
    @endphp

    <div
        x-data="{
            tooltip: { visible: false, x: 0, y: 0, day: null },
            showTooltip(event, day) {
                this.tooltip = { visible: true, x: event.clientX, y: event.clientY + window.scrollY, day: day };
            },
            updateTooltip(event) {
                if (this.tooltip.visible) {
                    this.tooltip.x = event.clientX;
                    this.tooltip.y = event.clientY + window.scrollY;
                }
            },
            hideTooltip() { this.tooltip.visible = false; },

            range: '7d',
            chartMode: 'single',
            eventTypes: @js($eventTypes),
            typeLabels: @js($typeLabels),
            typeColors: @js($typeColors),
            typeHex: @js($typeHex),
            dailyByType: @js($dailyByType),
            topFolders: @js($topFolders),
            topExtensions: @js($topExtensions),
            sizeDistribution: @js($sizeDistribution),
            summaryCards: @js($summaryCards),
            sizeBuckets: @js($sizeBuckets),

            get dailyData() { return this.dailyByType[this.range] || this.dailyByType['7d']; },
            get folders() { return this.topFolders[this.range] || this.topFolders['7d']; },
            get extensions() { return this.topExtensions[this.range] || this.topExtensions['7d']; },
            get sizes() { return this.sizeDistribution[this.range] || this.sizeDistribution['7d']; },
            get summary() { return this.summaryCards[this.range] || this.summaryCards['7d']; },

            get hasEventData() {
                return this.dailyData.some(day =>
                    this.eventTypes.reduce((s, t) => s + (day[t] || 0), 0) > 0
                );
            },
            get hasSizeData() { return this.sizes.some(count => count > 0); },

            get typeTotals() {
                let totals = {};
                let grand = 0;
                this.eventTypes.forEach(t => { totals[t] = 0; });
                this.dailyData.forEach(day => {
                    this.eventTypes.forEach(t => { totals[t] += (day[t] || 0); });
                    grand += this.eventTypes.reduce((s, t) => s + (day[t] || 0), 0);
                });
                return { totals, grand };
            },

            get totalSize() { return this.sizes.reduce((a, b) => a + b, 0); },
            get maxSize() { return Math.max(...this.sizes, 1); },
            get maxFolderCount() { return Math.max(...this.folders.map(f => f.count), 1); },
            get maxExtCount() { return Math.max(...this.extensions.map(e => e.count), 1); },

            get stackMax() {
                const max = Math.max(...this.dailyData.map(day =>
                    this.eventTypes.reduce((s, t) => s + (day[t] || 0), 0)
                ), 1);
                return this.hasEventData ? (Math.ceil(max * 1.1) || 1) : 0;
            },

            yTicks() {
                let max = this.stackMax;
                if (max === 0) return [0];
                const target = 6;
                const rawStep = max / target;
                const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
                const step = Math.round(Math.ceil(rawStep / magnitude) * magnitude * 1e10) / 1e10;
                const roundedMax = Math.ceil(max / step) * step;
                let ticks = [];
                for (let i = 0; i <= roundedMax; i += step) ticks.push(parseFloat(i.toFixed(10)));
                return ticks;
            },

            sizeYTicks() {
                let max = this.maxSize;
                if (max === 0) return [0];
                const target = 5;
                const rawStep = max / target;
                const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
                const step = Math.ceil(rawStep / magnitude) * magnitude;
                const roundedMax = Math.ceil(max / step) * step;
                let ticks = [];
                for (let i = 0; i <= roundedMax; i += step) ticks.push(parseFloat(i.toFixed(10)));
                return ticks;
            },

            stackPercent(day, type) {
                return this.stackMax > 0 ? ((day[type] || 0) / this.stackMax * 100) : 0;
            },
            sizePercent(count) {
                return this.maxSize > 0 ? (count / this.maxSize * 100) : 0;
            },
            rangeLabel() {
                return { '7d': 'over 7 days', '30d': 'over 30 days', '90d': 'over 90 days', '365d': 'over 1 year' }[this.range];
            },
        }"
        x-init="
            const self = $data;
            console.log('range:', self.range);
            console.log('dailyByType keys:', Object.keys(self.dailyByType));
            let _chart = null;
            let _sizeChart = null;

            function buildChartData() {
                const daily = self.dailyByType[self.range] || self.dailyByType['7d'];
                const mode = self.chartMode;
                if (mode === 'multi') {
                    return {
                        labels: daily.map(d => d.date),
                        datasets: self.eventTypes.map(t => ({
                            label: self.typeLabels[t],
                            data: daily.map(d => d[t] || 0),
                            borderColor: self.typeHex[t],
                            backgroundColor: self.typeHex[t] + '22',
                            borderWidth: 2,
                            pointRadius: daily.length <= 14 ? 3 : 1,
                            pointHoverRadius: 5,
                            tension: 0.3,
                            fill: false,
                        }))
                    };
                }
                return {
                    labels: daily.map(d => d.date),
                    datasets: [{
                        label: 'Total Events',
                        data: daily.map(d => self.eventTypes.reduce((s, t) => s + (d[t] || 0), 0)),
                        borderColor: '#818cf8',
                        backgroundColor: '#818cf822',
                        borderWidth: 2,
                        pointRadius: daily.length <= 14 ? 3 : 1,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true,
                    }]
                };
            }

            function initChart() {
                const canvas = $refs.lineChart;
                if (!canvas) return;
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#374151' : '#e5e7eb';
                const tickColor = isDark ? '#9ca3af' : '#6b7280';
                if (_chart) { _chart.destroy(); _chart = null; }
                _chart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: buildChartData(),
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                            animation: {
                                duration: 1000,
                                easing: 'easeInOutQuart',
                                y: {
                                    from: (ctx) => ctx.chart.scales.y.bottom
                                }
                            },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: self.chartMode === 'multi',
                                labels: { color: tickColor, boxWidth: 12, font: { size: 11 } }
                            },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            x: { ticks: { color: tickColor, maxTicksLimit: 8, font: { size: 10 } }, grid: { color: gridColor } },
                            y: { beginAtZero: true, ticks: { color: tickColor, font: { size: 10 } }, grid: { color: gridColor } }
                        }
                    }
                });
            }

            function refreshChart() {
                if (!_chart) return;
                const d = buildChartData();
                _chart.data.labels = d.labels;
                _chart.data.datasets = d.datasets;
                _chart.options.plugins.legend.display = self.chartMode === 'multi';
                _chart.update();
            }
            
            function buildSizeChartData() {
                const sizes = $data.sizeDistribution[$data.range] || $data.sizeDistribution['7d'];
                return {
                    labels: $data.sizeBuckets,
                    datasets: [{
                        label: 'Events',
                        data: sizes,
                        backgroundColor: '#3b82f6',
                        hoverBackgroundColor: '#60a5fa',
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                };
            }

            function initSizeChart() {
                const canvas = $refs.sizeChart;
                if (!canvas) return;
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#374151' : '#e5e7eb';
                const tickColor = isDark ? '#9ca3af' : '#6b7280';
                if (_sizeChart) { _sizeChart.destroy(); _sizeChart = null; }
                _sizeChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: buildSizeChartData(),
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.parsed.y.toLocaleString() + ' events'
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: tickColor, font: { size: 10 } }, grid: { color: gridColor } },
                            y: { beginAtZero: true, ticks: { color: tickColor, font: { size: 10 } }, grid: { color: gridColor } }
                        }
                    }
                });
            }

            function refreshSizeChart() {
                if (!_sizeChart) return;
                const d = buildSizeChartData();
                _sizeChart.data.labels = d.labels;
                _sizeChart.data.datasets = d.datasets;
                _sizeChart.update('none');
            }


            $nextTick(() => initChart());
            $nextTick(() => initSizeChart());
            $watch('range', () => $nextTick(() => refreshChart()));
            $watch('chartMode', () => $nextTick(() => refreshChart()));
            $watch('range', () => $nextTick(() => refreshSizeChart()));
        "
    >

        {{-- Global tooltip portal --}}
        <div
            x-show="tooltip.visible"
            x-cloak
            class="fixed bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg px-3 py-2 shadow-lg whitespace-nowrap pointer-events-none"
            :style="'z-index: 9999; top: ' + (tooltip.y - 12) + 'px; transform: translateY(-100%); ' + (tooltip.x > window.innerWidth - 220 ? 'right: ' + (window.innerWidth - tooltip.x + 12) + 'px;' : 'left: ' + (tooltip.x + 12) + 'px;')"
        >
            <template x-if="tooltip.day">
                <div>
                    <div class="font-semibold mb-1" x-text="tooltip.day.date"></div>
                    <template x-for="t in eventTypes" :key="t">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-sm" :class="typeColors[t]"></span>
                            <span x-text="typeLabels[t] + ': ' + (tooltip.day[t] || 0)"></span>
                        </div>
                    </template>
                    <div class="border-t border-gray-600 mt-1 pt-1 font-bold"
                        x-text="'Total: ' + eventTypes.reduce((s, t) => s + (tooltip.day[t] || 0), 0)"></div>
                </div>
            </template>
        </div>

        {{-- Section 1: Summary metric cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

            {{-- Total events --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total events</span>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center card-icon-indigo">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="summary.total.toLocaleString()"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="rangeLabel()"></p>
            </div>

            {{-- Daily average --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Daily average</span>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center card-icon-blue">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="summary.avg.toLocaleString()"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">events per day</p>
            </div>

            {{-- Most active type --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Most active type</span>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center card-icon-amber">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="typeLabels[summary.mostActive] || summary.mostActive"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="summary.pct + '% of all events'"></p>
            </div>

            {{-- Total data affected --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total data affected</span>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center card-icon-emerald">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="summary.data"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">across all events</p>
            </div>

        </div>

        {{-- Range picker --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white"></h1>
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1 border border-gray-200 dark:border-gray-700">
                <template x-for="opt in [{k:'7d',l:'7d'},{k:'30d',l:'30d'},{k:'90d',l:'90d'},{k:'365d',l:'1y'}]" :key="opt.k">
                    <button
                        @click="range = opt.k"
                        :class="range === opt.k ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                        class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                        x-text="opt.l"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Section 2: Event volume line chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Event volume by type</h3>
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button
                        @click="chartMode = 'single'"
                        :class="chartMode === 'single' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                    >Total</button>
                    <button
                        @click="chartMode = 'multi'"
                        :class="chartMode === 'multi' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                    >By type</button>
                </div>
            </div>

            <div x-show="!hasEventData" x-cloak class="flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm" style="height: 260px;">
                No events for this period
            </div>

            <div x-show="hasEventData" style="height: 260px;">
                <canvas x-ref="lineChart"></canvas>
            </div>
        </div>

        {{-- Section 3: Top folders + File extensions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Top folders --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Top folders by activity</h3>
                <div x-show="folders.length === 0" x-cloak class="flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm" style="min-height: 120px;">
                    No folder data
                </div>
                <div x-show="folders.length > 0" class="space-y-3">
                    <template x-for="(folder, idx) in folders" :key="idx">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-mono text-gray-700 dark:text-gray-300 truncate max-w-[60%]"
                                      :title="folder.name"
                                      x-text="folder.name.split(/[\\\\/]/).filter(s => s.length > 0).slice(-2).join('\\\\')"></span>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white w-12 text-right" x-text="folder.count.toLocaleString()"></span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 w-10 text-right" x-text="folder.pct + '%'"></span>
                                </div>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-teal-500 rounded-full transition-all duration-500"
                                     :style="'width: ' + (maxFolderCount > 0 ? (folder.count / maxFolderCount * 100) : 0) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- File extensions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">File extensions</h3>
                <div x-show="extensions.length === 0" x-cloak class="flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm" style="min-height: 120px;">
                    No extension data
                </div>
                <div x-show="extensions.length > 0" class="space-y-3">
                    <template x-for="(ext, idx) in extensions" :key="idx">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-300">.<span x-text="ext.name"></span></span>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white w-12 text-right" x-text="ext.count.toLocaleString()"></span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 w-10 text-right" x-text="ext.pct + '%'"></span>
                                </div>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-teal-500 rounded-full transition-all duration-500"
                                     :style="'width: ' + (maxExtCount > 0 ? (ext.count / maxExtCount * 100) : 0) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- Section 4: File size distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">File size distribution — events by size bucket</h3>

            <div x-show="!hasSizeData" x-cloak class="flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm" style="min-height: 200px;">
                No size distribution data
            </div>

            <div x-show="hasSizeData" style="height: 200px;">
                <canvas x-ref="sizeChart"></canvas>
            </div>
        </div>

    </div>
</x-layouts.app>