# Task Management Platform

A task management system built for the Full-Stack Developer technical assessment. It has a **Laravel 13 REST API** (JWT auth, file attachments, role-based authorization) and a **Next.js 16** dashboard.

## Features

- **Authentication**: JWT login, logout (token blacklist) and current-user endpoint. Login is rate limited.
- **Tasks CRUD**: pagination, filters (status, priority, assignee), title search, whitelisted sorting (priority sorts low → high, not alphabetically).
- **Authorization**: every signed-in user can view tasks. Creator, assignee or admin can update. Creator or admin can delete.
- **Attachments**: private storage with random filenames, type and size validation (20 MB), authenticated download. Files are deleted from disk together with the attachment or the task.
- **Comments** on tasks.
- **Frontend**: login, responsive task table/cards, filters with debounced search, create/edit modal, delete confirmation, task detail page, drag-and-drop multi-file upload with per-file progress bars, comments, toasts, and loading/error/empty states.
- **Tests**: 17 PHPUnit feature tests (auth, tasks, validation, policies, uploads).

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13, `php-open-source-saver/jwt-auth` |
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

Requirements: PHP 8.3+ (extensions `pdo_mysql`, `sodium`, `fileinfo`), Composer, MySQL 8, Node.js 22 LTS. The full guide, including Windows/Laragon notes, is in [documentation/setup-guide.md](documentation/setup-guide.md).

**Backend**: http://localhost:8000

```bash
cd backend
composer install
cp .env.example .env              # set DB_* for your MySQL
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed        # or import database/database.sql
php artisan serve
```

**Frontend**: http://localhost:3000

```bash
cd frontend
npm install
cp .env.example .env.local        # NEXT_PUBLIC_API_URL=http://localhost:8000/api
npm run dev
```

**Tests:** `cd backend && php artisan test`

## Demo accounts

| Email | Password | Role |
|---|---|---|
| admin@example.com | password | admin |
| user@example.com | password | member |

The seeder also creates 4 random members, 20 tasks and 30 comments.

## API endpoints

All endpoints are prefixed with `/api`. Everything except login needs `Authorization: Bearer <token>`. Errors are always JSON `{ "message": "...", "errors"?: {...} }`. The API returns 201 on create, 204 on delete, and 401, 403, 404 or 422 on errors.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Get a JWT |
| POST | `/auth/logout` | Invalidate the token |
| GET | `/auth/me` | Current user |
| GET | `/tasks` | List: `page`, `per_page` (≤50), `status`, `priority`, `assigned_user_id`, `search`, `sort` (`created_at`, `due_date`, `priority`, `title`; prefix `-` for desc) |
| GET | `/tasks/{id}` | Task detail with attachments *(extra)* |
| POST | `/tasks` | Create |
| PUT | `/tasks/{id}` | Update (partial) |
| DELETE | `/tasks/{id}` | Delete |
| POST | `/tasks/{id}/attachments` | Upload (`multipart/form-data`, field `file`) |
| GET | `/attachments/{id}/download` | Download |
| DELETE | `/attachments/{id}` | Delete |
| GET / POST | `/tasks/{id}/comments` | List / add comments *(extra)* |
| GET | `/users` | Users for the assignee dropdown *(extra)* |

The Postman collection is at [documentation/api-docs/](documentation/api-docs/task-management.postman_collection.json). Run **Login** first; it stores the token automatically.

## Deferred features

I scoped the work to a 4–6 hour time box and chose to finish the core flows and test them, instead of starting every item. These are not implemented:

| Feature | Why deferred | How it would fit in |
|---|---|---|
| Real-time updates and comments (WebSocket/SSE) | Needs an extra running service (Laravel Reverb or Pusher) plus client subscription handling. That was too much for the time left. | Broadcast `TaskUpdated` and `CommentCreated` events on a private `task.{id}` channel, and subscribe with Laravel Echo on the detail and list pages. |
| Queue jobs (assignment emails, bulk updates, CSV/PDF export) | The `jobs` table and `QUEUE_CONNECTION=database` are already set up, but writing and testing each job would have cut into core CRUD quality. | Dispatch `SendTaskAssignedNotification` from a `Task` observer when `assigned_user_id` changes, and run `php artisan queue:work`. |
| Thumbnails, virus scan, chunked upload, versioning | They depend on the queue (above) and on extra PHP extensions (GD/Imagick). | A `ProcessAttachment` job after upload that generates the thumbnail and runs a scan stub. |
| Frontend tests | Time was prioritised for backend tests. See [architecture.md](documentation/architecture.md#testing). | |

More detail and trade-offs are in [documentation/architecture.md](documentation/architecture.md).
