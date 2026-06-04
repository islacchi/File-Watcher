@props(['title' => 'No results found', 'description' => '', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'])

<div class="flex flex-col items-center justify-center py-16 px-4 text-center">
    {{-- SVG Illustration --}}
    <div class="w-24 h-24 mb-6 text-gray-300 dark:text-gray-600">
        <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/>
        </svg>
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>

    @if ($description)
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mb-6">{{ $description }}</p>
    @endif

    @if (isset($action) && $action->isNotEmpty())
        <div>
            {{ $action }}
        </div>
    @endif
</div>