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
            class="mt-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
        >
        <form method="GET" action="{{ route('filewatcher.events') }}" x-ref="filterForm">

            @if (isset($filters['tab']) && $filters['tab'] !== 'all')
                <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
            @endif
            @if (isset($filters['search']) && $filters['search'] !== '')
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
            @endif

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-start">

                {{-- Event Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Event Type</label>
                    <div class="space-y-1.5">
                        @foreach ($eventTypeOptions as $value => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="event_type[]"
                                    value="{{ $value }}"
                                    {{ in_array($value, (array) ($filters['event_type'] ?? [])) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Date From --}}
                <div class="flex flex-col gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Date From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Date To</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        />
                    </div>
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

            </div>

            {{-- Drawer Footer --}}
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <a href="{{ route('filewatcher.events') }}"
                    class="text-xs font-medium text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                >
                    Clear all
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                >
                    Apply filters
                </button>
            </div>

        </form>
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
                @php
                    $sortBy = $filters['sort_by'] ?? 'time';
                    $sortDir = $filters['sort_dir'] ?? 'desc';
                    $nextDir = $sortDir === 'asc' ? 'desc' : 'asc';

                    $sortUrl = fn($col) => request()->fullUrlWithQuery([
                        'sort_by' => $col,
                        'sort_dir' => $sortBy === $col ? $nextDir : 'desc',
                    ]);
                @endphp
                <table class="w-full text-sm" style="table-layout: fixed; width: 100%;">
                    <colgroup>
                        <col style="width: 8%;">
                        <col style="width: 12%;">
                        <col style="width: 30%;">
                        <col style="width: 8%;">
                        <col style="width: 35%;">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <a href="{{ $sortUrl('time') }}" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                    Time
                                    <svg class="w-3 h-3 {{ $sortBy === 'time' ? 'text-gray-700 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if ($sortBy === 'time' && $sortDir === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        @elseif ($sortBy === 'time')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                                        @endif
                                    </svg>
                                </a>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">File Path</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <a href="{{ $sortUrl('size') }}" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                    Size
                                    <svg class="w-3 h-3 {{ $sortBy === 'size' ? 'text-gray-700 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if ($sortBy === 'size' && $sortDir === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        @elseif ($sortBy === 'size')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                                        @endif
                                    </svg>
                                </a>
                            </th>
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
                                <td class="px-4 py-3 truncate">
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
            {{-- Pagination --}}
            @if ($events->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $events->withQueryString()->links('pagination::tailwind') }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>