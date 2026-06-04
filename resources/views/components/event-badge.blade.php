@props(['label', 'color' => 'bg-gray-100 text-gray-600', 'icon' => null])

<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    @if ($icon)
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
        </svg>
    @endif
    {{ $label }}
</span>