@props(['nodes' => [], 'currentDirectory' => null, 'level' => 0])

@if (count($nodes) > 0)
    <ul class="{{ $level > 0 ? 'ml-4 border-l border-gray-200 dark:border-gray-700 pl-2' : '' }}">
        @foreach ($nodes as $node)
            @php
                $isSelected = $currentDirectory === $node['path'];
                $hasChildren = count($node['children']) > 0;
                $isLeafFile = empty($node['children']) && $node['file_count'] > 0;
                $fileIconPath = '';
                if ($isLeafFile) {
                    $ext = strtolower(pathinfo($node['name'], PATHINFO_EXTENSION));
                    $fileIconPath = match ($ext) {
                        'pdf' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                        'mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv', 'webm' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                        'mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
                        'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                        'xls', 'xlsx', 'csv', 'tsv' => 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                        'doc', 'docx', 'txt', 'rtf', 'odt' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        'ppt', 'pptx', 'odp' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z',
                        'exe', 'msi', 'bat', 'cmd', 'ps1', 'sh' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
                        'js', 'ts', 'jsx', 'tsx', 'py', 'rb', 'go', 'rs', 'java', 'c', 'cpp', 'h', 'cs', 'php', 'swift', 'kt' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                        'css', 'scss', 'less', 'sass' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                        'html', 'htm', 'xml', 'json', 'yaml', 'yml', 'toml' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
                        'sql', 'db', 'sqlite', 'mdb' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                        'log', 'tmp', 'bak', 'cache' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        default => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    };
                }
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

                    {{-- Directory/File Link (base64 encode to preserve UNC paths with backslashes) --}}
                    <a
                        href="{{ $isLeafFile
                            ? route('filewatcher.snapshot', ['path' => base64_encode($node['path'])])
                            : route('filewatcher.snapshot', ['directory' => base64_encode($node['path'])]) }}"
                        class="flex items-center gap-1.5 text-sm px-2 py-1 rounded-md transition-colors truncate max-w-[200px] {{ $isSelected
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                        }}"
                        title="{{ $node['path'] }}"
                    >
                        @if ($isLeafFile)
                            <svg class="w-4 h-4 flex-shrink-0 {{ $isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fileIconPath }}"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 flex-shrink-0 {{ $isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-yellow-500' }}" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2 6a2 2 0 012-2h5l2 2h9a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                            </svg>
                        @endif
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