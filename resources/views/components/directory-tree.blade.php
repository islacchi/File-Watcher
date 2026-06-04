@props(['nodes' => [], 'currentDirectory' => null, 'level' => 0])

@if (count($nodes) > 0)
    <ul class="{{ $level > 0 ? 'ml-4 border-l border-gray-200 dark:border-gray-700 pl-2' : '' }}">
        @foreach ($nodes as $node)
            @php
                $isSelected = $currentDirectory === $node['path'];
                $hasChildren = count($node['children']) > 0;
            @endphp
            <li x-data="{ open: {{ $hasChildren && $isSelected ? 'true' : 'false' }} }">
                <div class="flex items-center gap-1 py-0.5">
                    {{-- Expand/Collapse Toggle --}}
                    @if ($hasChildren)
                        <button
                            @click="open = !open"
                            class="flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            aria-label="Toggle directory"
                        >
                            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <span class="w-[14px] flex-shrink-0"></span>
                    @endif

                    {{-- Directory Link --}}
                    <a
                        href="{{ route('filewatcher.snapshot', ['directory' => $node['path']]) }}"
                        class="flex items-center gap-1.5 text-sm px-2 py-1 rounded-md transition-colors truncate max-w-[200px] {{ $isSelected
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                        }}"
                        title="{{ $node['path'] }}"
                    >
                        <svg class="w-4 h-4 flex-shrink-0 {{ $isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-yellow-500' }}" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2 6a2 2 0 012-2h5l2 2h9a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        <span class="truncate">{{ $node['name'] }}</span>
                        @if ($node['file_count'] > 0)
                            <span class="text-[10px] text-gray-400 dark:text-gray-500">({{ $node['file_count'] }})</span>
                        @endif
                    </a>
                </div>

                {{-- Children --}}
                @if ($hasChildren)
                    <div x-show="open" x-collapse x-cloak>
                        <x-directory-tree :nodes="$node['children']" :current-directory="$currentDirectory" :level="$level + 1" />
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif