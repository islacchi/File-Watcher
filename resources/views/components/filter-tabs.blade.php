@props(['tabs' => [], 'active' => 'all', 'baseUrl' => ''])

<div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
    @foreach ($tabs as $key => $label)
        @php
            $isActive = $active === $key;
            $params = $key === 'all'
                ? array_filter(request()->except(['tab', 'page']))
                : array_merge(array_filter(request()->except(['tab', 'page'])), ['tab' => $key]);
        @endphp
        <a
            href="{{ $baseUrl }}?{{ http_build_query($params) }}"
            class="inline-flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                {{ $isActive
                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
                }}"
        >
            {{ $label }}
        </a>
    @endforeach
</div>