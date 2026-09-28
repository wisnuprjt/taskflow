# Architecture

## Overview

```
┌────────────────────────┐   HTTPS/JSON + Bearer JWT   ┌──────────────────────────┐      ┌──────────┐
│ Next.js 16 (browser)   │ ──────────────────────────▶ │ Laravel 13 API (/api/*)  │ ───▶ │ MySQL 8  │
│ client components      │ ◀────────────────────────── │ stateless, guard "api"   │      └──────────┘
│ token in localStorage  │   JSON {data, meta} / error │                          │ ───▶ storage/app/private
└────────────────────────┘                             └──────────────────────────┘      (attachments)
```

The backend is a stateless JSON API. The frontend is a separate single-page style app that talks to it over CORS (`CORS_ALLOWED_ORIGINS`, default `http://localhost:3000`).

## Backend request flow

```
routes/api.php → auth:api (JWT) → FormRequest (validation, 422)
              → Controller (thin) → Gate/TaskPolicy (403) → Eloquent model
              → API Resource (JSON shape) → response (200/201/204)
```

| Layer | Location | Responsibility |
|---|---|---|
| Routes | `routes/api.php` | Endpoint map. Everything except login sits behind `auth:api`. |
| Form Requests | `app/Http/Requests` | All input validation. `UpdateTaskRequest` reuses the create rules, with every field optional. |
| Controllers | `app/Http/Controllers` | Orchestration only (about 5–15 lines per action). |
| Policy | `app/Policies/TaskPolicy.php` | Who may update or delete. It also governs attachment upload and delete. |
| Models | `app/Models` | Relations, enum casts, `Task::scopeFilter()` for listing, file cleanup hooks. |
| Resources | `app/Http/Resources` | Output format, independent of the DB schema. |
| Enums | `app/Enums` | `TaskStatus`, `TaskPriority`, `UserRole`. Shared by casts and validation (`Rule::enum`). |
| Error rendering | `bootstrap/app.php` | Every `/api/*` error is `{message, errors?}` with no stack traces or model class names. |

## Key decisions and trade-offs

### JWT stored in `localStorage` (vs httpOnly cookie)

- **Chosen:** a Bearer JWT (`php-open-source-saver/jwt-auth`), stored in `localStorage` and attached by a single API client (`frontend/src/lib/api.ts`). On a 401, the client clears the token and redirects to `/login`.
- **Why:** it matches the brief ("JWT"). The API stays stateless and works the same for Postman, curl and the SPA. There is no CSRF handling, and cross-origin cookies between :3000 and :8000 are not needed.
- **Trade-off:** any successful XSS can read the token. An httpOnly, `SameSite` cookie (for example Laravel Sanctum SPA auth, or a Next.js route handler proxying the API) would be safer. The cost is CSRF protection and same-site deployment constraints.
- **Mitigations:** short-lived tokens (60 min TTL), server-side blacklist on logout, login rate limit (10/min), React escaping by default, and no `dangerouslySetInnerHTML`.

### Private disk for attachments

- Files go to the `local` disk (`storage/app/private/attachments/{task_id}/`), which is **never publicly served**. The stored name is Laravel's random hash name. The original filename is kept only in the DB and restored through `Content-Disposition` on download.
- **Why:** this prevents direct URL guessing, path traversal and execution of uploaded files. Every download goes through `auth:api` and the policy.
- **Trade-off:** downloads are streamed through PHP instead of being served by the web server or a CDN. Signed temporary URLs (S3 `temporaryUrl`) would scale better.
- Validation checks the extension and MIME type together (`mimes:`, based on file contents) and caps the size at 20 MB. The frontend mirrors these rules for instant feedback, but the server stays the source of truth.
- The FK cascade removes DB rows only, so model events delete the physical files as well (`Task::deleting`, `TaskAttachment::deleted`).

### Policy for authorization

- `TaskPolicy` holds the rules in one place: view for everyone, update for creator, assignee or admin, delete for creator or admin. Controllers call `Gate::authorize()`.
- `TaskResource` exposes `can.update` and `can.delete`, so the UI hides actions the user cannot perform. The API still enforces the rules itself.

### API Resources for the JSON format

- Resources decouple the response from DB columns. For example, `file_path` is never exposed, and `due_date` is formatted as `Y-m-d`.
- Relations use `whenLoaded` and `whenCounted`, so there are no hidden N+1 queries. The list endpoint eager-loads `assignee` and `creator` and counts `attachments` and `comments`.
- Paginated responses use Laravel's standard `{data, links, meta}` envelope.

### Other decisions

- **Sorting is whitelisted** (`created_at`, `due_date`, `priority`, `title`) so arbitrary column names cannot be injected. Priority sorts by a `CASE` expression, so the order is low < medium < high, and it works on both MySQL and SQLite.
- **Enums in the DB and in PHP**: MySQL `ENUM` columns guarantee valid data at the storage level, and PHP backed enums give type safety in code.
- **`backend/app/` instead of `backend/src/`**: I kept Laravel's convention so artisan generators, auto-discovery and tooling keep working.
- **Frontend is client-rendered**: the token lives in `localStorage`, so pages are client components behind an auth guard (the `(app)` route group layout). Server-side rendering would need a cookie-based session.
- **No UI or state libraries**: toasts, modals and form components are small, reusable in-house components in `frontend/src/components/ui`. This keeps the bundle and dependency surface small.
- **Race-safe list fetching**: the task list ignores responses from stale queries, so fast filter changes cannot show outdated results.

## Deferred features

| Feature | Reason | Planned design |
|---|---|---|
| Real-time (WebSocket/SSE), presence, typing indicators | Needs an extra service (Reverb or Pusher) and client subscription handling. That did not fit the time box. | Laravel Reverb and broadcast events (`TaskUpdated`, `CommentCreated`) on private `task.{id}` channels, with Laravel Echo on the client. |
| Queue jobs (email on assignment, bulk status update, CSV/PDF export) | Prioritised core CRUD quality and tests. The infrastructure is ready (`jobs` table, `QUEUE_CONNECTION=database`). | A `TaskObserver` dispatches `SendTaskAssignedNotification` when the assignee changes. A `BulkUpdateTaskStatus` job. An `ExportTasks` job that stores a CSV and notifies the user. |
| Thumbnails, virus scan simulation, chunked upload, versioning | Depends on the queue and on GD/Imagick. Chunked upload needs a resumable protocol. | A `ProcessAttachment` job (thumbnail and scan stub, with a status column). A tus-style chunked endpoint for files over 50 MB. |
| Video streaming, caching (Redis) | Optional bonus items. | HTTP range responses for MP4/WebM, and Redis caching of list queries keyed by filters. |

## Testing

### Backend (implemented)

17 PHPUnit feature tests run against an in-memory SQLite database (`php artisan test`):

- **Auth:** successful login, wrong password (401), validation (422), and logout that invalidates the token.
- **Tasks:** auth required (401), filtering and pagination, create (201), validation (422), policy (403 for an unrelated member), delete by creator (204), JSON 404.
- **Attachments** (`Storage::fake`): upload with a random stored name, download, delete, rejected type and size (422), and file cleanup when the task is deleted.

### Frontend (planned, not implemented)

**Status: planned.** I prioritised time for backend tests, because the business rules (authorization, validation, file handling) live in the API and are covered there. The frontend was verified with TypeScript (`npm run typecheck`), ESLint (`npm run lint`), a production build, and manual testing of the main flows.

Planned setup:

- **Unit/component** (Vitest + React Testing Library): the `api.ts` client (Bearer header, 401 redirect, error mapping), `TaskFilters`, `Pagination`, and `AttachmentUploader` client-side validation.
- **Integration**: pages with a mocked API (MSW) to cover the loading, error and empty states.
- **E2E** (Playwright): login → create task → upload file → comment → delete, against a seeded backend.
