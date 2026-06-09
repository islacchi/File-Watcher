<x-layouts.app :title="'File Timeline'">
    {{-- File Path Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('filewatcher.events') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Events</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-medium">Timeline</span>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex-shrink-0 p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white font-mono truncate max-w-2xl" title="{{ $path }}">
                    {{ $path }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $totalEvents }} {{ Str::plural('event', $totalEvents) }} tracked
                </p>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    @if (empty($sessions))
        <x-empty-state
            title="No events found for this file"
            description="No file system events were found for this path. The file may not exist in the watched directory."
            icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        >
            <x-slot:action>
                <a href="{{ route('filewatcher.events') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Browse all events
                </a>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="space-y-8">
            @foreach ($sessions as $sessionIndex => $session)
                @php
                    $sessionStart = \App\Services\Formatter::formatTimestamp($session['start']);
                    $sessionEnd = \App\Services\Formatter::formatTimestamp($session['end']);
                @endphp

                {{-- Session Header --}}
                @if ($sessionIndex > 0)
                    <div class="flex items-center gap-4 my-6">
                        <div class="flex-1 border-t border-dashed border-gray-300 dark:border-gray-600"></div>
                        <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">Session break</span>
                        <div class="flex-1 border-t border-dashed border-gray-300 dark:border-gray-600"></div>
                    </div>
                @endif

                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium">
                    {{ $sessionStart }}
                    @if ($sessionStart !== $sessionEnd)
                        → {{ $sessionEnd }}
                    @endif
                </div>

                {{-- Events in Session --}}
                <div class="relative pl-8">
                    {{-- Vertical Line --}}
                    <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    @foreach ($session['events'] as $event)
                        @php
                            $dotColor = match($event->eventType) {
                                'CREATED' => 'bg-green-500',
                                'MODIFIED' => 'bg-blue-500',
                                'DELETED' => 'bg-red-500',
                                'RENAMED' => 'bg-purple-500',
                                'MOVED' => 'bg-teal-500',
                                'MOVED_AND_RENAMED' => 'bg-indigo-500',
                                default => 'bg-gray-400',
                            };
                        @endphp

                        <div class="relative flex items-start gap-4 pb-6 last:pb-0">
                            {{-- Timeline Dot --}}
                            <div class="absolute -left-8 top-1.5">
                                <x-timeline-dot :color="$dotColor" />
                            </div>

                            {{-- Event Content --}}
                            <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <x-event-badge
                                                :label="$event->badgeLabel"
                                                :color="$event->badgeColor"
                                            />
                                            <span class="text-xs text-gray-400 dark:text-gray-500" title="{{ $event->formattedTime }}">
                                                {{ $event->relativeTime }}
                                            </span>
                                        </div>

                                        {{-- Source Path --}}
                                        <div class="mt-2">
                                            <span class="text-xs text-gray-400 dark:text-gray-500">From:</span>
                                            <x-file-path :path="$event->srcPath" :truncated="$event->truncatedPath" />
                                        </div>

                                        {{-- Destination Path (for moves/renames) --}}
                                        @if ($event->hasPathChange())
                                            <div class="mt-1">
                                                <span class="text-xs text-gray-400 dark:text-gray-500">To:</span>
                                                <x-file-path :path="$event->destPath" :truncated="\App\Services\Formatter::truncatePath($event->destPath)" />
                                            </div>
                                        @endif

                                        {{-- Hash Diff (for modified events) --}}
                                        @if ($event->hasHashDiff())
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="text-xs text-gray-400 dark:text-gray-500">Hash:</span>
                                                <div class="flex items-center gap-1 text-xs">
                                                    <x-hash-display :hash="$event->prevHash" :truncated="$event->truncatedPrevHash" :searchable="true" />
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                                    </svg>
                                                    <x-hash-display :hash="$event->md5Hash" :truncated="$event->truncatedHash" :searchable="true" />
                                                </div>
                                            </div>
                                        @elseif ($event->md5Hash)
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="text-xs text-gray-400 dark:text-gray-500">Hash:</span>
                                                <x-hash-display :hash="$event->md5Hash" :truncated="$event->truncatedHash" :searchable="true" />
                                            </div>
                                        @endif

                                        {{-- File Size --}}
                                        @if ($event->fileSize)
                                            <div class="mt-1">
                                                <span class="text-xs text-gray-400 dark:text-gray-500">Size:</span>
                                                <span class="text-xs text-gray-600 dark:text-gray-300">{{ $event->formattedSize }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Diff Badge Placeholder --}}
                                    @if ($event->eventType === 'MODIFIED')
                                        <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            Diff available
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>