<x-layouts.app :title="'Dashboard'">
    {{-- Metric Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-metric-card
            title="Events Today"
            color="indigo"
            :value="$viewModel->eventsToday"
            :trend="$viewModel->todayTrend['value']"
            :trend-direction="$viewModel->todayTrend['direction']"
            :sparkline="$viewModel->sparkline"
            icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        />

        <x-metric-card
            title="Deletions (24h)"
            color="red"
            :value="$viewModel->deletionsLast24h"
            :trend="$viewModel->deletionsTrend['value']"
            :trend-direction="$viewModel->deletionsTrend['direction']"
            icon="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
        />

        <x-metric-card
            title="Tracked Files"
            color="emerald"
            :value="$viewModel->totalTrackedFiles"
            icon="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h.01M15 11h.01M9 15h.01M15 15h.01"
        />

        <x-metric-card
            title="Last Startup Scan"
            color="blue"
            :value="$viewModel->relativeStartupTime()"
            :subtitle="$viewModel->lastStartupTime ? \App\Services\Formatter::formatTimestamp($viewModel->lastStartupTime) : 'No events today'"
            icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Event Type Chart (real-time with Alpine polling) --}}
        <div
            class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5"
            x-data="{
            counts: @js($viewModel->eventTypeCounts),
            maxCount: @js($viewModel->maxEventCount()),
            labels: @js(collect(\App\Enums\EventType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])),
            colors: @js(collect(\App\Enums\EventType::cases())->mapWithKeys(fn($t) => [
                $t->value => match($t) {
                    \App\Enums\EventType::CREATED => 'bg-green-500',
                    \App\Enums\EventType::MODIFIED => 'bg-blue-500',
                    \App\Enums\EventType::DELETED => 'bg-red-500',
                    \App\Enums\EventType::RENAMED => 'bg-purple-500',
                    \App\Enums\EventType::MOVED => 'bg-teal-500',
                    \App\Enums\EventType::MOVED_AND_RENAMED => 'bg-indigo-500',
                },
            ])),
            types: @js(collect(\App\Enums\EventType::cases())->pluck('value')->values()),
            async refreshCounts() {
                try {
                    const resp = await fetch('{{ route('filewatcher.event-counts') }}');
                    const data = await resp.json();
                    this.counts = data;
                    this.maxCount = Math.max(...Object.values(data), 0);
                } catch {}
            },
            init() {
                setInterval(() => this.refreshCounts(), 5000);
            }
        }"
    >
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Events Today by Type</h3>

        <template x-if="maxCount > 0">
            <div class="space-y-3">
                <template x-for="type in types" :key="type">
                    <a
                        :href="'{{ route('filewatcher.events', ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')]) }}&event_type[]=' + type"
                        class="block group"
                        :title="'View ' + labels[type] + ' events (' + (counts[type] || 0) + ')'"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition-colors" x-text="labels[type]"></span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="counts[type] || 0"></span>
                        </div>
                        <div class="w-full h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500 group-hover:opacity-80"
                                :class="colors[type]"
                                :style="'width: ' + Math.max((counts[type] || 0) / maxCount * 100, (counts[type] || 0) > 0 ? 3 : 0) + '%'"
                            ></div>
                        </div>
                    </a>
                </template>
            </div>
        </template>
        <template x-if="!maxCount || maxCount === 0">
            <x-empty-state
                title="No events today"
                description="Events will appear here once the file watcher starts recording."
                icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
        </template>
    </div>

        {{-- Recent Activity --}}
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <a
                    href="{{ route('filewatcher.events') }}"
                    class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
                >
                    View all →
                </a>
            </div>

            @if ($recentActivity->isEmpty())
                <x-empty-state
                    title="No recent activity"
                    description="File system events will appear here as they are detected."
                    icon="M13 10V3L4 14h7v7l9-11h-7z"
                />
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($recentActivity as $event)
                        <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- File Icon --}}
                            <div class="shrink-0">
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <x-event-badge
                                        :label="$event->badgeLabel"
                                        :color="$event->badgeColor"
                                    />
                                </div>
                                <p class="mt-0.5 font-mono text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $event->srcPath }}">
                                    {{ $event->truncatedPath }}
                                </p>
                            </div>

                            {{-- Time --}}
                            <div class="shrink-0 text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400" title="{{ $event->formattedTime }}">
                                    {{ $event->relativeTime }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>