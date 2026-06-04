@props(['title', 'value', 'trend' => null, 'trendDirection' => 'neutral', 'sparkline' => null, 'icon' => null, 'subtitle' => null])

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $title }}</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>

            @if ($trend)
                <div class="mt-2 flex items-center gap-1.5">
                    @if ($trendDirection === 'up')
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/>
                        </svg>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $trend }}</span>
                    @elseif ($trendDirection === 'down')
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"/>
                        </svg>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $trend }}</span>
                    @else
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $trend }}</span>
                    @endif
                    <span class="text-xs text-gray-400 dark:text-gray-500">vs yesterday</span>
                </div>
            @endif

            @if ($subtitle)
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($icon)
            <div class="flex-shrink-0 p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                </svg>
            </div>
        @endif
    </div>

    {{-- Sparkline --}}
    @if ($sparkline && count($sparkline) > 0)
        @php
            $maxCount = max(array_column($sparkline, 'count'));
            $maxCount = max($maxCount, 1);
        @endphp
        <div class="mt-4 flex items-end gap-1 h-8">
            @foreach ($sparkline as $day)
                @php
                    $height = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                    $height = max($height, 2); // minimum height for visibility
                @endphp
                <div
                    class="flex-1 bg-indigo-200 dark:bg-indigo-800 rounded-t transition-all duration-300 hover:bg-indigo-400 dark:hover:bg-indigo-600"
                    style="height: {{ $height }}%"
                    title="{{ $day['date'] }}: {{ $day['count'] }} events"
                ></div>
            @endforeach
        </div>
        <div class="flex justify-between mt-1">
            <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $sparkline[0]['date'] }}</span>
            <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ end($sparkline)['date'] }}</span>
        </div>
    @endif
</div>