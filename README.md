<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
    <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
    <img src="https://img.shields.io/badge/Tailwind_CSS_v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
    <img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black" alt="Alpine.js">
    <img src="https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite">
    <img src="https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

<p align="center">
    <img src="https://img.shields.io/badge/Status-Active-22c55e?style=flat-square" alt="Status">
    <img src="https://img.shields.io/badge/License-MIT-yellow?style=flat-square" alt="License">
</p>

<h1 align="center">File Watcher Laravel UI</h1>

<p align="center">
    A read-only Laravel web interface for monitoring file system changes on a network drive. <br>
    Pairs with a Python file watcher that logs events to a SQLite database.
</p>

---

## Features

- **Dashboard** — Real-time metrics, sparkline chart, event type breakdown, and recent activity feed
- **Analytics** — Multi-range charts (7d / 30d / 90d / 1y) with stacked bar chart, top folders, file extensions, and size distribution
- **Events Log** — Filterable and paginated table with search by filename, path, or MD5 hash; date range, event type, and extension filters
- **Snapshot** — Current file state with expandable directory tree, stale file detection, auto-refreshes every 15s
- **File Timeline** — Complete event history tracking files across renames and moves via MD5 hash linking
- **Hash Search** — Clicking any MD5 hash in the Events Log filters all events sharing that file version, enabling full file lineage tracing
- **Health Monitoring** — Live/offline status indicator via heartbeat polling from the Python script
- **Auto-refresh** — Change-driven polling that reloads the page only when new events are detected
- **Dark Mode** — Class-based toggle with localStorage persistence

---

## Architecture

```
app/
├── Enums/          # EventType enum with badge colors and labels
├── Http/
│   ├── Controllers/# Dashboard, Event, File, Snapshot, Health, Analytics
│   └── Requests/  # EventFilter, SnapshotFilter, FileTimeline
├── Models/         # Event, Snapshot, Config (read-only)
├── Services/       # EventService, SnapshotService, ConfigService, Formatter
├── Providers/      # ViewServiceProvider (shared layout data)
└── View/Models/    # DashboardViewModel, EventViewModel, SnapshotViewModel

resources/views/
├── components/
│   └── layouts/
│       └── app.blade.php       # Shared layout: sidebar, topbar, offline banner
├── analytics/
│   └── index.blade.php         # Analytics page with Alpine.js charts
├── dashboard.blade.php
├── events/index.blade.php
├── files/timeline.blade.php
└── snapshot/
    ├── index.blade.php
    └── _tree.blade.php         # AJAX partial for directory tree
```

---

## Pages

| Page | Route | Description |
|------|-------|-------------|
| **Dashboard** | `/filewatcher/dashboard` | Metrics, sparkline, event type bars, recent activity |
| **Analytics** | `/filewatcher/analytics` | Multi-range charts and breakdowns |
| **Events Log** | `/filewatcher/events` | Filterable event table with pagination |
| **Snapshot** | `/filewatcher/snapshot` | Current file states and directory tree |
| **File Timeline** | `/filewatcher/files?path=...` | Full event history for a single file |

---

## Analytics

The analytics page is precomputed server-side across four date ranges and rendered entirely client-side via Alpine.js with no additional requests on range switch.

### Date Ranges

| Pill | Range | Chart grouping |
|------|-------|----------------|
| 7d | Last 7 days | Daily bars, scrollable |
| 30d | Last 30 days | Daily bars, scrollable |
| 90d | Last 90 days | Daily bars, compressed |
| 1y | Last 365 days | Weekly bars, compressed |

### Summary Cards

- **Total events** — count for the selected range
- **Daily average** — total divided by number of days
- **Most active type** — event type with highest count and its percentage
- **Total data affected** — sum of `file_size` across all events, formatted (B / KB / MB / GB)

### Charts

**Event volume by type** — stacked bar chart with per-type color coding, hover tooltip showing date, per-type counts, and total. Tooltip uses a fixed-position portal to avoid overflow clipping. Y-axis uses dynamic magnitude-based tick scaling. 1y range uses PHP-aggregated weekly grouping.

**Top folders by activity** — horizontal bar list showing the top 10 directories by event count, with percentage labels. Folder paths are extracted from `dirname(src_path)` and display the last two path segments.

**File extensions** — horizontal bar list showing the top 8 extensions by count. Extension is extracted using the last dot in the filename to prevent compound extensions from appearing as full filenames.

**File size distribution** — bar chart with 6 size buckets: `<10 KB`, `10–50 KB`, `50–200 KB`, `200 KB–1 MB`, `1–10 MB`, `>10 MB`. Bars use the same hover tooltip pattern.

---

## Database

The UI connects to an existing SQLite database created by the Python script. All tables are read-only.

### `events` — Permanent log of every file change

| Column | Type | Description |
|--------|------|-------------|
| `id` | `INTEGER PK` | Auto-increment |
| `timestamp` | `TEXT` | ISO 8601 datetime |
| `event_type` | `TEXT` | CREATED / MODIFIED / DELETED / RENAMED / MOVED / MOVED_AND_RENAMED (+ offline variants) |
| `src_path` | `TEXT` | Source file path (UNC or local) |
| `dest_path` | `TEXT` | Destination path for RENAMED / MOVED / MOVED_AND_RENAMED |
| `file_size` | `INTEGER` | Size in bytes |
| `md5_hash` | `TEXT` | Hash after the event |
| `prev_hash` | `TEXT` | Hash before the event (MODIFIED only) |

### `snapshots` — Last known state of every watched file

| Column | Type | Description |
|--------|------|-------------|
| `id` | `INTEGER PK` | Auto-increment |
| `path` | `TEXT UNIQUE` | Current file path |
| `size` | `INTEGER` | File size |
| `mtime` | `REAL` | Unix timestamp |
| `md5_hash` | `TEXT` | Current hash |
| `last_seen` | `TEXT` | ISO 8601 timestamp |

### `config` — Script metadata (written by Python)

| Column | Type | Description |
|--------|------|-------------|
| `key` | `TEXT PK` | `watch_directory`, `started_at`, `heartbeat`, `status`, `script_version` |
| `value` | `TEXT` | Corresponding value |
| `updated` | `TEXT` | ISO 8601 timestamp |

---

## Design System

Built with **Tailwind CSS v4** and **Alpine.js**. Dark mode uses a class-based strategy (`dark` on `<html>`) toggled via Alpine and persisted to `localStorage`.

### Tailwind v4 Notes

Dynamic Alpine `:class` bindings are not scanned by Tailwind v4's build-time scanner. Classes used in dynamic bindings are safelisted via `@source inline()` in `app.css`:

```css
@source inline("ml-60 ml-16 w-60 w-16 translate-x-4.5 translate-x-0.5 dark:bg-gray-800 dark:bg-gray-700 dark:border-gray-700");
```

Custom dark mode variant is defined as:

```css
@custom-variant dark (&:is(.dark *));
```

IDE warnings about unknown `@source`, `@custom-variant` are cosmetic — suppress via `.vscode/settings.json`:

```json
{
    "css.lint.unknownAtRules": "ignore"
}
```

### Event Type Colors

| Type | Color | Hex |
|------|-------|-----|
| CREATED | `bg-green-500` | `#22c55e` |
| MODIFIED | `bg-blue-500` | `#3b82f6` |
| DELETED | `bg-red-500` | `#ef4444` |
| RENAMED | `bg-yellow-500` | `#eab308` |
| MOVED | `bg-teal-400` | `#2dd4bf` |
| MOVED & RENAMED | `bg-indigo-400` | `#818cf8` |

---

## Reusable Components

| Component | Usage | Props |
|-----------|-------|-------|
| `<x-event-badge>` | Events table, dashboard, timeline | `label`, `color` |
| `<x-metric-card>` | Dashboard cards | `title`, `value`, `trend`, `icon`, `sparkline` |
| `<x-file-path>` | All file path displays | `path`, `truncated` |
| `<x-hash-display>` | Hash columns — click to filter by file version | `hash`, `truncated`, `searchable` |
| `<x-timeline-dot>` | File timeline | `color` |
| `<x-directory-tree>` | Snapshot sidebar | `nodes`, `current-directory`, `level` |
| `<x-filter-tabs>` | Events quick-filter tabs | `tabs`, `active`, `base-url` |
| `<x-empty-state>` | Empty table states | `title`, `description`, `icon` |

---

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- SQLite (PHP extension enabled)

---

## Setup

```bash
# 1. Clone the repository
git clone https://github.com/islacchi/File-Watcher.git
cd File-Watcher

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
```

Edit `.env` to point to your SQLite database:

```env
DB_CONNECTION=sqlite
DB_DATABASE=C:/path/to/logs/filelog.db
```

```bash
# 4. Generate application key
php artisan key:generate

# 5. Install and build frontend assets
npm install
npm run build

# 6. Start the development server
php artisan serve
```

Visit `http://localhost:8000` — the root URL redirects to `/filewatcher/dashboard`.

---

## Development

```bash
# Start Laravel dev server
php artisan serve

# Watch for frontend changes (Vite HMR)
npm run dev

# Build frontend for production
npm run build
```

---

## Script Integration

The Laravel UI pairs with a companion **Python file watcher** available at:

```bash
gh repo clone islacchi/Python-File-Watcher
```

The script:

1. Monitors a network drive for file system changes using `watchdog`
2. Logs CREATED, MODIFIED, DELETED, RENAMED, MOVED, and MOVED_AND_RENAMED events to the SQLite database
3. Maintains a `snapshots` table with current file state and MD5 hashes
4. Writes a heartbeat timestamp to the `config` table every **5 seconds** and updates the `status` key to `live` on startup and `offline` on clean shutdown

### Health Check Logic

The health endpoint (`/filewatcher/health`) determines online/offline status in priority order:

1. **Primary** — reads the `status` key from the `config` table. The Python script writes `live` on startup and `offline` on shutdown. This is the most reliable indicator.
2. **Fallback 1** — if no `status` key exists, checks the `heartbeat` timestamp. The script writes every 5 seconds; a heartbeat older than **12 seconds** (1 missed cycle + buffer) is treated as offline.
3. **Fallback 2** — if no heartbeat exists (older script versions), checks the last event timestamp. Offline if no event within **60 seconds**.

### Move Detection on UNC Network Shares

On UNC paths (`\\server\share`), Windows SMB can fire `CREATE` before `DELETE` for the same move operation — sometimes seconds apart. The Python script handles this with a `move_window` (default **10 seconds**): it holds unmatched creates and deletes in memory and resolves pairs as MOVED or MOVED_AND_RENAMED if a hash match is found within the window. If no match is found before the window expires, the events are logged as genuine CREATED and DELETED.

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).