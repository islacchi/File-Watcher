@props(['hash', 'label' => null, 'searchable' => false, 'truncated' => null])

@php
    $displayHash = $truncated ?? \App\Services\Formatter::truncateHash($hash);
@endphp

@if ($hash)
    <div class="group inline-flex items-center gap-1">
        @if ($label)
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $label }}:</span>
        @endif

        @if ($searchable)
            <a
                href="{{ route('filewatcher.events', ['search' => $hash]) }}"
                class="font-mono text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                title="Search events with hash: {{ $hash }}"
            >
                {{ $displayHash }}
            </a>
        @else
            <span
                class="font-mono text-xs text-gray-600 dark:text-gray-400"
                title="{{ $hash }}"
            >
                {{ $displayHash }}
            </span>
        @endif
    </div>
@else
    <span class="font-mono text-xs text-gray-400 dark:text-gray-500">—</span>
@endif