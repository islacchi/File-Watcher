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

<h1 align="center">📁 File Watcher Laravel UI</h1>

<p align="center">
    A read-only Laravel web interface for monitoring file system changes on a network drive. <br>
    Pairs with a Python file watcher that logs events to a SQLite database.
</p>

---

## ✨ Features

- **Dashboard** — Real-time metrics, event type charts, and recent activity feed
- **Events Log** — Filterable table with search, date range, event type, and extension filters
- **Snapshot** — Current file state with expandable directory tree (auto-refreshes every 15s)
- **File Timeline** — Complete event history tracking files across renames and moves
- **Health Monitoring** — Live/offline status via heartbeat from the Python script
- **Dark Mode** — Class-based toggle with localStorage persistence

## 🏗️ Architecture

```
app/
├── Enums/          # EventType enum with badge colors and labels
├── Http/
│   ├── Controllers/# Dashboard, Event, File, Snapshot, Health
│   └── Requests/  # EventFilter, SnapshotFilter, FileTimeline
├── Models/         # Event, Snapshot, Config (read-only)
├── Services/       # EventService, SnapshotService, ConfigService, Formatter
├── Providers/      # ViewServiceProvider (shared layout data)
└── View/Models/    # DashboardViewModel, EventViewModel, SnapshotViewModel

resources/views/
├── components/     # Blade components (badges, cards, tree, pagination)
│   ├── directory-tree.blade.php
│   ├── event-badge.blade.php
│   ├── file-path.blade.php
│   ├── hash-display.blade.php
│   ├── metric-card.blade.php
│   ├── timeline-dot.blade.php
│   ├── filter-tabs.blade.php
│   ├── empty-state.blade.php
│   └── layouts/app.blade.php
├── dashboard.blade.php
├── events/index.blade.php
├── files/timeline.blade.php
└── snapshot/
    ├── index.blade.php
    └── _tree.blade.php    # AJAX partial for tree
```

## 📄 Pages

| Page | Route | Description |
|------|-------|-------------|
| **Dashboard** | `/filewatcher/dashboard` | Metrics, bar chart, recent activity |
| **Events Log** | `/filewatcher/events` | Filterable event table with pagination |
| **Snapshot** | `/filewatcher/snapshot` | Current file states + directory tree |
| **File Timeline** | `/filewatcher/files?path=...` | Event history for a single file |

## 🗄️ Database

The UI connects to an existing SQLite database created by the Python script. Tables are read-only:

### `events` — Permanent log of every file change

| Column | Type | Description |
|--------|------|-------------|
| `id` | `INTEGER PK` | Auto-increment |
| `timestamp` | `TEXT` | ISO 8601 datetime |
| `event_type` | `TEXT` | CREATED / MODIFIED / DELETED / RENAMED / MOVED (+ offline variants) |
| `src_path` | `TEXT` | Source file path (UNC or local) |
| `dest_path` | `TEXT` | Destination path for RENAMED/MOVED |
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
| `key` | `TEXT PK` | `watch_directory`, `started_at`, `heartbeat`, `script_version` |
| `value` | `TEXT` | Corresponding value |
| `updated` | `TEXT` | ISO 8601 timestamp |

## 🎨 Design System

### Event Type Badge Colors

| Type | Badge | Offline Variant |
|------|-------|-----------------|
| CREATED | <span style="background:#22c55e;color:white;padding:2px 8px;border-radius:999px;font-size:12px">Created</span> | <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:12px">Created (offline)</span> |
| MODIFIED | <span style="background:#3b82f6;color:white;padding:2px 8px;border-radius:999px;font-size:12px">Modified</span> | <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:12px">Modified (offline)</span> |
| DELETED | <span style="background:#ef4444;color:white;padding:2px 8px;border-radius:999px;font-size:12px">Deleted</span> | <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:12px">Deleted (offline)</span> |
| RENAMED | <span style="background:#a855f7;color:white;padding:2px 8px;border-radius:999px;font-size:12px">Renamed</span> | <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:12px">Renamed (offline)</span> |
| MOVED | <span style="background:#14b8a6;color:white;padding:2px 8px;border-radius:999px;font-size:12px">Moved</span> | <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:12px">Moved (offline)</span> |

## 🚀 Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- SQLite (PHP extension enabled)

## ⚙️ Setup

```bash
# 1. Clone the repository
git clone <repository-url>
cd filewatcher

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
```

Edit `.env` to point to your database:

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

## 🛠️ Development

```bash
# Start Laravel dev server
php artisan serve

# Build frontend for production
npm run build

# Watch for frontend changes
npm run dev
```

## 🔗 Script Integration

The Laravel UI works with a companion **Python file watcher** that:

1. Monitors a network drive for file changes using `watchdog`
2. Logs CREATED, MODIFIED, DELETED, RENAMED, MOVED events to the SQLite database
3. Maintains a current snapshot table with MD5 hashes
4. Writes a `heartbeat` to the config table every 30 seconds

The health check endpoint (`/filewatcher/health`) reads the heartbeat to determine if the script is running. If no heartbeat within 90 seconds, the UI displays as **offline**.

## 📋 Reusable Components

| Component | Usage | Props |
|-----------|-------|-------|
| `<x-event-badge>` | Events table, dashboard, timeline | `label`, `color` |
| `<x-metric-card>` | Dashboard cards | `title`, `value`, `trend`, `icon`, `sparkline` |
| `<x-file-path>` | All file path displays | `path`, `truncated` |
| `<x-hash-display>` | Hash columns | `hash`, `truncated`, `searchable` |
| `<x-timeline-dot>` | File timeline | `color` |
| `<x-directory-tree>` | Snapshot sidebar | `nodes`, `current-directory`, `level` |
| `<x-filter-tabs>` | Events quick-filter tabs | `tabs`, `active`, `base-url` |
| `<x-empty-state>` | Empty table states | `title`, `description`, `icon` |

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).