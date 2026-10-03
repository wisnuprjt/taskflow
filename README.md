# Task Management Platform

A task management system built for the Full-Stack Developer technical assessment. It has a **Laravel 13 REST API** (JWT auth, file attachments with background processing, role-based authorization), **real-time updates over WebSocket** (Laravel Reverb) and a **Next.js 16** dashboard.

## Features

- **Authentication**: JWT login, logout (token blacklist) and current-user endpoint. Login is rate limited.
- **Tasks CRUD**: pagination, filters (status, priority, assignee), title search, whitelisted sorting (priority sorts low → high, not alphabetically).
- **Authorization**: every signed-in user can view tasks. Creator, assignee or admin can update. Creator or admin can delete.
- **Attachments**: private storage with random filenames, type validation based on file contents, authenticated download. Files are deleted from disk together with the attachment or the task.
  - **Chunked upload** for large files: up to 20 MB in one request, larger files (up to 500 MB) in 5 MB chunks with per-chunk retry.
  - **Virus scan simulation** (queue job): known signatures, executable headers and disguised double extensions. Infected files are quarantined, and downloads stay blocked until the scan passes.
  - **Thumbnails** (queue job): 320px WebP previews generated with GD, only for files that passed the scan.
  - **Versioning**: re-uploading a file name to the same task stores the next version; older versions stay downloadable.
- **Comments** on tasks.
- **Real-time** (Laravel Reverb + Echo): the task list and detail, comments, attachment status and role changes update without a refresh. Online users, "also viewing" and "is typing…" indicators.
- **Frontend**: login, responsive task table/cards, filters with debounced search, create/edit modal, delete confirmation, task detail page, drag-and-drop multi-file upload with per-file progress bars, comments, toasts, and loading/error/empty states.
- **Tests**: 40 PHPUnit feature tests (auth, tasks, validation, policies, uploads, chunked upload, virus scan, thumbnails, versioning, broadcasting).

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13, `php-open-source-saver/jwt-auth`, database queue |
| Real-time | Laravel Reverb (WebSocket server), Laravel Echo + `pusher-js` |
| Database | MySQL 8 (SQLite in-memory for tests) |
| Frontend | Next.js 16 (App Router), React 19, TypeScript, Tailwind CSS 4 |
| Testing | PHPUnit 12 |

## Project structure

```
project-root/
├── backend/            Laravel API (app/ is Laravel's equivalent of src/)
├── frontend/           Next.js app (src/, public/)
├── database/
│   └── database.sql    MySQL dump: schema + seed data
├── documentation/
│   ├── api-docs/       Postman collection
│   ├── architecture.md
│   ├── database-schema.md
│   └── setup-guide.md
└── README.md
```

## Quick start

Requirements: PHP 8.3+ (extensions `pdo_mysql`, `sodium`, `fileinfo`, `gd`), Composer, MySQL 8, Node.js 22 LTS. The full guide, including Windows/Laragon notes, is in [documentation/setup-guide.md](documentation/setup-guide.md).

**Backend**: http://localhost:8000

```bash
cd backend
composer install
cp .env.example .env              # set DB_* for your MySQL
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed        # or import database/database.sql
composer run dev                  # API server + queue worker + Reverb (ws://localhost:8080)
```

**Frontend**: http://localhost:3000

```bash
cd frontend
npm install
cp .env.example .env.local        # API URL + NEXT_PUBLIC_REVERB_* (key must match backend REVERB_APP_KEY)
npm run dev
```

**Tests:** `cd backend && php artisan test`

**Handy commands** (from `backend/`):

| Command | What it does |
|---|---|
| `php artisan user:role {email} {admin\|member}` | Change a user's role; their open session updates live |
| `php artisan realtime:ping {email}` | Send a test message to a user's browser to check the WebSocket connection |

## Demo accounts

| Email | Password | Role |
|---|---|---|
| admin@example.com | password | admin |  
| user@example.com | password | member |
| wisnu@transcosmos.com | password | admin |
| wisnu2@transcosmos.com | password | member |

The seeder loads a fixed demo dataset (`backend/database/seeders/data/demo.json`): these accounts plus 4 more members, 29 tasks (including a set of "Testing …" tasks used for manual QA) and 30 comments. `migrate --seed` and `database/database.sql` contain exactly the same data. To try role changes live: `php artisan user:role wisnu2@transcosmos.com admin`.

## API endpoints

All endpoints are prefixed with `/api`. Everything except login needs `Authorization: Bearer <token>`. Errors are always JSON `{ "message": "...", "errors"?: {...} }`. The API returns 201 on create, 204 on delete, and 401, 403, 404 or 422 on errors.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Get a JWT |
| POST | `/auth/logout` | Invalidate the token |
| GET | `/auth/me` | Current user |
| GET | `/tasks` | List: `page`, `per_page` (≤50), `status`, `priority`, `assigned_user_id`, `search`, `sort` (`created_at`, `due_date`, `priority`, `title`; prefix `-` for desc) |
| GET | `/tasks/{id}` | Task detail with the latest version of each attachment *(extra)* |
| POST | `/tasks` | Create |
| PUT | `/tasks/{id}` | Update (partial) |
| DELETE | `/tasks/{id}` | Delete |
| POST | `/tasks/{id}/attachments` | Upload up to 20 MB (`multipart/form-data`, field `file`) |
| POST | `/tasks/{id}/attachments/chunked` | Start a chunked upload (`file_name`, `file_size`) → `upload_id`, `chunk_size` |
| POST | `/uploads/{uploadId}/chunks` | Send one chunk (`index`, `file`); retry-safe |
| POST | `/uploads/{uploadId}/complete` | Assemble the chunks into an attachment |
| DELETE | `/uploads/{uploadId}` | Abort and discard the chunks |
| GET | `/attachments/{id}/download` | Download (423 while scanning, 410 if quarantined) |
| GET | `/attachments/{id}/thumbnail` | Image thumbnail (WebP) |
| GET | `/attachments/{id}/versions` | All versions of this file in its task, newest first |
| DELETE | `/attachments/{id}` | Delete |
| GET / POST | `/tasks/{id}/comments` | List / add comments *(extra)* |
| GET | `/users` | Users for the assignee dropdown *(extra)* |
| POST | `/broadcasting/auth` | Authorise a private/presence WebSocket channel (used by Laravel Echo) |

The Postman collection is at [documentation/api-docs/](documentation/api-docs/task-management.postman_collection.json). Run **Login** first; it stores the token automatically.

## Real-time events

| Channel | Type | Events |
|---|---|---|
| `tasks` | private | `task.created`, `task.updated`, `task.deleted` (the list page re-fetches) |
| `task.{id}` | private | `task.updated`, `task.deleted`, `comment.created`, `attachment.changed` |
| `App.Models.User.{id}` | private | `role.changed`, `ping` |
| `online` | presence | who is signed in |
| `task-viewers.{id}` | presence | who has the task open; `typing` / `stopped-typing` whispers |

## Deferred features

I scoped the work to a 4–6 hour time box and chose to finish the core flows and test them, instead of starting every item. These are not implemented:

| Feature | Why deferred | How it would fit in |
|---|---|---|
| Queue jobs for assignment emails, bulk status updates and CSV/PDF export | The queue already runs the attachment scan and thumbnails; these three jobs were not prioritised. | Dispatch `SendTaskAssignedNotification` from a `Task` observer when `assigned_user_id` changes; a `BulkUpdateTaskStatus` job; an `ExportTasks` job that stores the file and notifies the user on their private channel. |
| Video streaming, video thumbnails, adaptive streaming | Video files can be uploaded and downloaded, but not streamed. Thumbnails and multiple qualities need FFmpeg. | HTTP range responses for MP4/WebM; an FFmpeg job for a poster frame and HLS renditions. |
| Version history UI | The versions API exists; the dashboard shows the latest version only. | A "versions" popover per attachment using `GET /attachments/{id}/versions`. |
| Frontend tests | Time was prioritised for backend tests. See [architecture.md](documentation/architecture.md#testing). | |

More detail and trade-offs are in [documentation/architecture.md](documentation/architecture.md).
