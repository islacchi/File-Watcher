<x-layouts.app :title="'Events Log'">
    {{-- Filter Tabs --}}
    @php
        $tabs = [
            'all' => 'All Events',
            'modified' => 'Modified',
            'deleted' => 'Deleted',
            'renamed' => 'Renamed',
            'moved' => 'Moved',
            'offline' => 'Offline',
        ];
        $activeTab = $filters['tab'] ?? 'all';
    @endphp
    <div class="mb-4">
        <x-filter-tabs :tabs="$tabs" :active="$activeTab" :base-url="route('filewatcher.events')" />
    </div>

    {{-- Filter Drawer --}}
    <div x-data="{ open: false }" class="mb-4">
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <form method="GET" action="{{ route('filewatcher.events') }}" class="flex-1 flex gap-2">
                <div class="relative flex-1 max-w-md">
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by filename or path..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        x-data="{ value: @js($filters['search'] ?? ''), timeout: null }"
                        x-model="value"
                        @input.debounce.300ms="if (timeout) clearTimeout(timeout); timeout = setTimeout(() => $el.form.submit(), 300)"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- Hidden fields to preserve other filters --}}
                @if (isset($filters['tab']) && $filters['tab'] !== 'all')
                    <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                @endif
                @if (isset($filters['event_type']))
                    @foreach ((array) $filters['event_type'] as $type)
                        <input type="hidden" name="event_type[]" value="{{ $type }}">
                    @endforeach
                @endif
                @if (isset($filters['date_from']))
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                @endif
                @if (isset($filters['date_to']))
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                @endif
                @if (isset($filters['extension']))
                    <input type="hidden" name="extension" value="{{ $filters['extension'] }}">
                @endif
            </form>

            {{-- Filter Toggle Button --}}
            <button
                @click="open = !open"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters
                @if (!empty($filters['event_type']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['extension']))
                    <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                @endif
            </button>
        </div>

        {{-- Filter Drawer Content --}}
        <div
            x-show="open"
            x-collapse
            x-cloak
            class="mt-3 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                {{-- Event Type Checkboxes --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Event Type</label>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @foreach ($eventTypeOptions as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="event_type[]"
                                    value="{{ $value }}"
                                    {{ in_array($value, (array) ($filters['event_type'] ?? [])) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    onchange="this.form.submit()"
                                />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Date From</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        onchange="this.form.submit()"
                    />
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Date To</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        onchange="this.form.submit()"
                    />
                </div>

                {{-- Extension --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">File Extension</label>
                    <input
                        type="text"
                        name="extension"
                        value="{{ $filters['extension'] ?? '' }}"
                        placeholder="e.g. pdf, docx"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
            </div>

            <div class="flex justify-end mt-4 gap-2">
                <a
                    href="{{ route('filewatcher.events') }}"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                >
                    Clear all
                </a>
            </div>
        </div>
    </div>

    {{-- Events Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($events->isEmpty())
            <x-empty-state
                title="No events found"
                description="No events match your current filters. Try adjusting the search criteria or clearing the filters."
                icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">File Path</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hash</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($events as $event)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs text-gray-500 dark:text-gray-400" title="{{ $event->formattedTime }}">
                                        {{ $event->relativeTime }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center justify-start">
                                        <x-event-badge
                                            :label="$event->badgeLabel"
                                            :color="$event->badgeColor"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-lg">
                                    <x-file-path :path="$event->srcPath" :truncated="$event->truncatedPath" />

                                    @if ($event->hasPathChange())
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                            <x-file-path :path="$event->destPath" :truncated="\App\Services\Formatter::truncatePath($event->destPath)" />
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event->formattedSize }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($event->hasHashDiff())
                                        <div class="flex items-center gap-1 text-xs">
                                            <x-hash-display :hash="$event->prevHash" :truncated="$event->truncatedPrevHash" :searchable="true" />
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                            <x-hash-display :hash="$event->md5Hash" :truncated="$event->truncatedHash" :searchable="true" />
                                        </div>
                                    @else
                                        <x-hash-display :hash="$event->md5Hash" :truncated="$event->truncatedHash" :searchable="true" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($events->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $events->withQueryString()->links('pagination::tailwind') }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>