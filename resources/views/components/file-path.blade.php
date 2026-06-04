@props(['path', 'truncated' => null, 'fullUrl' => null])

@php
    $displayPath = $truncated ?? \App\Services\Formatter::truncatePath($path);
@endphp

<div
    x-data="{ copied: false }"
    class="group relative flex items-center gap-1.5 font-mono text-sm text-gray-700 dark:text-gray-300 max-w-lg"
    title="{{ $path }}"
>
    @if ($fullUrl)
        <a href="{{ $fullUrl }}" class="truncate hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            {{ $displayPath }}
        </a>
    @else
        <span class="truncate">{{ $displayPath }}</span>
    @endif

    <button
        @click="
            navigator.clipboard.writeText('{{ addslashes($path) }}');
            copied = true;
            setTimeout(() => copied = false, 2000);
        "
        class="opacity-0 group-hover:opacity-100 flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all"
        title="Copy full path"
        aria-label="Copy path to clipboard"
    >
        <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </button>
</div>