<x-layouts.app :title="'Snapshot'">
    <div class="flex gap-6">
        {{-- Directory Tree (Left Panel - 20%) --}}
        <aside
            class="hidden lg:block flex-shrink-0 relative"
            x-data="{ width: 480, dragging: false }"
            :style="`width: ${width}px`"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sticky top-20">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Directories</h3>

                <div
                    x-data="{
                        treeHtml: '',
                        treeContainer: null,
                        async refreshTree() {
                            try {
                                const params = new URLSearchParams();
                                @if ($currentDirectory)
                                    params.set('directory', '{{ $currentDirectory }}');
                                @endif
                                const resp = await fetch('{{ route('filewatcher.snapshot.tree') }}?' + params.toString());
                                if (resp.ok) {
                                    this.treeHtml = await resp.text();
                                    // Recompile Alpine directives in the new DOM
                                    this.$nextTick(() => {
                                        if (this.treeContainer) {
                                            Alpine.initTree(this.treeContainer);
                                        }
                                    });
                                }
                            } catch {}
                        },
                        init() {
                            this.treeContainer = this.$el;
                            this.refreshTree();
                            setInterval(() => this.refreshTree(), 15000);
                        }
                    }"
                    class="overflow-y-auto max-h-[calc(100vh-10rem)]"
                >
                    {{-- Watch Directory Root --}}
                    <div class="mb-2">
                        <a
                            href="{{ route('filewatcher.snapshot') }}"
                            class="flex items-center gap-1.5 text-sm px-2 py-1 rounded-md transition-colors
                                {{ !$currentDirectory
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                                }}"
                            title="{{ $watchDirectory }}"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span class="truncate font-mono text-xs">{{ $watchDirectory }}</span>
                        </a>
                    </div>

                    {{-- Tree content refreshed every 15 seconds --}}
                    <div x-html="treeHtml" id="snapshot-tree-container"></div>
                </div>
            </div>
                    {{-- Resize Handle --}}
            <div
                class="absolute top-0 right-0 w-1 h-full cursor-col-resize hover:bg-indigo-400 dark:hover:bg-indigo-600 transition-colors z-10"
                @mousedown.prevent="
                    dragging = true;
                    const startX = $event.clientX;
                    const startWidth = width;
                    const onMove = (e) => { if (dragging) width = Math.max(256, Math.min(720, startWidth + (e.clientX - startX))); };
                    const onUp = () => { dragging = false; window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); };
                    window.addEventListener('mousemove', onMove);
                    window.addEventListener('mouseup', onUp);
                "
            ></div>
        </aside>

        {{-- Right Panel: File Table (80%) --}}
        <div class="flex-1 min-w-0">
            {{-- Single-File Breadcrumb (when ?path= is set) --}}
            @if ($currentFilePath)
                @php
                    $parentDir = dirname($currentFilePath);
                @endphp
                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <a href="{{ route('filewatcher.snapshot') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        All Files
                    </a>
                    @php
                        $parts = array_values(array_filter(explode('\\', $parentDir)));
                    @endphp
                    @php $cumulative = ''; @endphp
                    @foreach ($parts as $part)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        @php
                            $cumulative .= ($cumulative ? '\\' : '') . $part;
                        @endphp
                        <a href="{{ route('filewatcher.snapshot', ['directory' => base64_encode($cumulative)]) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            {{ $part }}
                        </a>
                    @endforeach
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-900 dark:text-white font-medium">{{ basename($currentFilePath) }}</span>
                    <a href="{{ route('filewatcher.snapshot', ['directory' => base64_encode($parentDir)]) }}" class="ml-auto text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors" title="Back to parent directory">
                        ← Back to parent
                    </a>
                </div>
            {{-- Directory Breadcrumb --}}
            @elseif ($currentDirectory)
                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <a href="{{ route('filewatcher.snapshot') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        All Files
                    </a>
                    @php
                        $parts = array_values(array_filter(explode('\\', $currentDirectory)));
                    @endphp
                    @php $cumulative = ''; @endphp
                    @foreach ($parts as $index => $part)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        @php
                            $cumulative .= ($cumulative ? '\\' : '') . $part;
                            $isActive = $cumulative === $currentDirectory;
                        @endphp
                        @if ($isActive)
                            <span class="text-gray-900 dark:text-white font-medium">{{ $part }}</span>
                        @else
                            <a href="{{ route('filewatcher.snapshot', ['directory' => base64_encode($cumulative)]) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                {{ $part }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Search --}}
            <form
                method="GET"
                action="{{ route('filewatcher.snapshot') }}"
                x-data="{
                    search: '{{ addslashes($filters['search'] ?? '') }}',
                    submit() {
                        const params = new URLSearchParams(window.location.search);
                        if (this.search) {
                            params.set('search', this.search);
                        } else {
                            params.delete('search');
                        }
                        params.delete('page');
                        window.location.href = '{{ route('filewatcher.snapshot') }}?' + params.toString();
                    }
                }"
                class="flex gap-2 mb-4"
                @submit.prevent
            >
                @if ($currentDirectory)
                    <input type="hidden" name="directory" value="{{ $currentDirectory }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.300ms="submit()"
                        placeholder="Search files..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>

            {{-- Snapshot Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if ($snapshots->isEmpty())
                    <x-empty-state
                        title="No files found"
                        description="No snapshot data matches your current filters. Try adjusting the search criteria or selecting a different directory."
                        icon="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h.01M15 11h.01M9 15h.01M15 15h.01"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Path</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hash</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Seen</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($snapshots as $snapshot)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $snapshot->isStale ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : '' }}">
                                        <td class="px-4 py-3 max-w-lg">
                                            <div class="flex items-center gap-2">
                                                @if ($snapshot->isStale)
                                                    <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Not seen in over 7 days">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                @endif
                                                <x-file-path :path="$snapshot->path" :truncated="$snapshot->truncatedPath" />
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                            {{ $snapshot->formattedSize }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-hash-display :hash="$snapshot->md5Hash" :truncated="$snapshot->truncatedHash" />
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs text-gray-500 dark:text-gray-400" title="{{ $snapshot->formattedLastSeen }}">
                                                {{ $snapshot->relativeLastSeen }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a
                                                href="{{ route('filewatcher.files.timeline', ['path' => $snapshot->path]) }}"
                                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
                                                title="View timeline for this file"
                                            >
                                                View timeline →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($snapshots->hasPages())
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                            {{ $snapshots->withQueryString()->links('pagination::tailwind') }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>