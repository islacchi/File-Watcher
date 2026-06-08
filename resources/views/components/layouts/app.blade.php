<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('dark') === 'true' }" :class="{ 'dark': dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - File Watcher</title>
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen" x-cloak>

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        localStorage.setItem('sidebarOpen', this.sidebarOpen);
    },
    toggleDark() {
        this.dark = !this.dark;
        localStorage.setItem('dark', this.dark);
    }
}" class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'w-60' : 'w-16'"
        class="fixed left-0 top-0 h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-200 z-30 flex flex-col"
    >
        {{-- Logo --}}
        <div class="flex items-center h-14 px-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-7 h-7 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="font-bold text-sm whitespace-nowrap text-gray-900 dark:text-white">File Watcher</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-4 space-y-1 px-2">
            @php
                $navItems = [
                    ['route' => 'filewatcher.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'filewatcher.events', 'label' => 'Events Log', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ['route' => 'filewatcher.snapshot', 'label' => 'Snapshot', 'icon' => 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h.01M15 11h.01M9 15h.01M15 15h.01'],
                ];
            @endphp

            @foreach ($navItems as $item)
                @php
                    $isActive = request()->routeIs($item['route']);
                @endphp
                <a
                    href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                        {{ $isActive
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-l-2 border-indigo-600'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'
                        }}"
                    title="{{ $item['label'] }}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Collapse Toggle --}}
        <div class="border-t border-gray-200 dark:border-gray-700 p-2">
            <button
                @click="toggleSidebar()"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                :title="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'"
            >
                <svg class="w-5 h-5 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">Collapse</span>
            </button>
        </div>
    </aside>

    {{-- Main Content --}}
    <div :class="sidebarOpen ? 'ml-60' : 'ml-16'" class="flex-1 transition-all duration-200">

        {{-- Top Bar --}}
        <header class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-14">
            <div class="flex items-center justify-between h-full px-6">
                {{-- Left: Breadcrumb --}}
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ $title ?? 'Dashboard' }}</span>
                </div>

                {{-- Right: Status + Dark Mode --}}
                <div x-data="{ status: 'online' }" class="flex items-center gap-4">
                    {{-- Watched Directory --}}
                    <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span class="font-mono" title="{{ $watchDirectory }}">{{ $watchDirectory }}</span>
                        @if ($scriptVersion)
                            <span class="text-gray-400 dark:text-gray-500">v{{ $scriptVersion }}</span>
                        @endif
                    </div>

                    {{-- Status Indicator (single health check poll) --}}
                    <div
                        x-init="
                            const check = async () => {
                                try {
                                    const resp = await fetch('{{ route('filewatcher.health') }}');
                                    const data = await resp.json();
                                    status = data.status;
                                } catch { status = 'offline'; }
                            };
                            check();
                            setInterval(check, 15000);
                        "
                        class="flex items-center gap-1.5"
                    >
                        <span
                            :class="status === 'online' ? 'bg-green-500' : 'bg-red-500'"
                            class="w-2 h-2 rounded-full animate-pulse"
                        ></span>
                        <span x-text="status === 'online' ? 'Live' : 'Offline'" class="text-xs font-medium"
                            :class="status === 'online' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
                        ></span>
                    </div>

                    {{-- Auto-refresh Toggle --}}
                    <div x-data="{ autoRefresh: localStorage.getItem('autoRefresh') === 'true' }" class="flex items-center gap-2">
                        <button
                            @click="
                                autoRefresh = !autoRefresh;
                                localStorage.setItem('autoRefresh', autoRefresh);
                                if (autoRefresh) {
                                    window._autoRefreshInterval = setInterval(() => location.reload(), 10000);
                                } else {
                                    clearInterval(window._autoRefreshInterval);
                                }
                            "
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                            :class="autoRefresh ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                            role="switch"
                            :aria-checked="autoRefresh"
                            aria-label="Auto-refresh"
                            title="Auto-refresh every 10 seconds"
                        >
                            <span
                                class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                :class="autoRefresh ? 'translate-x-4.5' : 'translate-x-0.5'"
                            ></span>
                        </button>
                    </div>

                    {{-- Dark Mode Toggle --}}
                    <button
                        @click="toggleDark()"
                        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        :title="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                        aria-label="Toggle dark mode"
                    >
                        <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="p-6">
            {{ $slot }}
        </main>
    </div>

    {{-- Offline Banner --}}
    <div
        x-data="{
            show: false,
            init() {
                const check = async () => {
                    try {
                        const resp = await fetch('{{ route('filewatcher.health') }}');
                        const data = await resp.json();
                        this.show = data.status === 'offline';
                    } catch { this.show = true; }
                };
                check();
                setInterval(check, 15000);
            }
        }"
        x-show="show"
        x-transition
        class="fixed bottom-0 left-0 right-0 z-50 bg-red-600 text-white px-4 py-3 text-center text-sm font-medium shadow-lg"
    >
        <div class="flex items-center justify-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>The file watcher appears to be offline. Events are not being recorded.</span>
            <button @click="location.reload()" class="underline hover:no-underline font-semibold">Retry</button>
        </div>
    </div>

</div>

</body>
</html>