# Architecture

## Overview

```
┌────────────────────────┐   HTTPS/JSON + Bearer JWT   ┌──────────────────────────┐      ┌──────────┐
│ Next.js 16 (browser)   │ ──────────────────────────▶ │ Laravel 13 API (/api/*)  │ ───▶ │ MySQL 8  │
│ client components      │ ◀────────────────────────── │ stateless, guard "api"   │      └──────────┘
│ token in localStorage  │   JSON {data, meta} / error │                          │ ───▶ storage/app/private
│                        │                             └─────┬──────────────┬─────┘      (attachments, thumbnails)
│ Laravel Echo           │                                   │ dispatch     │ broadcast
│                        │                             ┌─────▼──────┐       │
│                        │                             │ Queue      │ ──────┤ (scan result,
│                        │                             │ worker     │       │  thumbnail ready)
│                        │   WebSocket (Pusher proto)  ┌─────────────────────▼────┐
│                        │ ◀────────────────────────── │ Laravel Reverb :8080     │
└────────────────────────┘   private/presence channels └──────────────────────────┘
```

The backend is a stateless JSON API. The frontend is a separate single-page style app that talks to it over CORS (`CORS_ALLOWED_ORIGINS`, default `http://localhost:3000`). Slow work (virus scan, thumbnails) runs on the database queue, and changes are pushed to browsers through Laravel Reverb. `composer run dev` starts the API server, the queue worker and Reverb together.

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
| Jobs | `app/Jobs` | `ScanAttachment` and `GenerateThumbnail`, run by the queue worker. |
| Events | `app/Events` | Broadcast events: `TaskChanged`, `CommentCreated`, `AttachmentChanged`, `RoleChanged`. |
| Channels | `routes/channels.php` | Who may join each private/presence channel (reuses `TaskPolicy`). |

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
- Validation checks the extension and MIME type together (`mimes:`, based on file contents) and caps a single request at 20 MB. The frontend mirrors these rules for instant feedback, but the server stays the source of truth.
- The FK cascade removes DB rows only, so model events delete the physical files and thumbnails as well (`Task::deleting`, `TaskAttachment::deleted`).

### Attachment processing pipeline

```
upload ─▶ row (scan_status = pending) ─▶ ScanAttachment ─┬─ clean    ─▶ GenerateThumbnail (images)
                                                         └─ infected ─▶ file deleted, row kept
```

- **Both uploads share one entry point**, `Task::addAttachment()`, so versioning, scanning and broadcasting are not duplicated between the normal and the chunked upload.
- **Scan before anything else.** Download and thumbnail return 423 while `pending` and 410 once `infected`. Thumbnails are only generated for clean files, so unscanned content is never decoded.
- **Virus scan simulation** flags the EICAR signature (plus a demo marker, because Windows Defender deletes real EICAR files on sight), `MZ`/`ELF` executable headers, and disguised double extensions such as `invoice.exe.pdf`. A real deployment would hand the file to ClamAV or a cloud scanner at the same point.
- **Streaming scan.** The file is read in 1 MB blocks, and the tail of each block is carried over, so a signature split across two blocks is still found. Memory stays flat even for 500 MB files.
- **Thumbnails are limited by pixels, not bytes.** GD decodes every pixel (~5 bytes each), so a 7 MB 24-megapixel JPEG needs ~120 MB of RAM. The job reads the dimensions from the header first, skips images above 50 MP, and raises the memory limit only inside the job.

### Chunked upload

- Files above 20 MB go up in 5 MB chunks: `init` → `chunks` → `complete`. The chunk size stays well under PHP's `upload_max_filesize`, so no server tuning is needed for large files.
- Upload state lives in the cache for a day and belongs to the user who started it. A chunk is stored under its index, so a retried chunk simply overwrites the failed attempt; the client retries network errors up to 3 times.
- `complete` concatenates the parts as streams and validates the **assembled** content (size and real file type), so renaming an executable to `.pdf` does not get through. A failed or aborted upload deletes its chunks.

### Versioning

- Re-uploading the same file name to the same task creates the next `version` (unique index on `task_id, file_name, version`).
- The task detail lists only the newest version of each file (`Task::latestAttachments`). `GET /attachments/{id}/versions` returns the history. Deleting the newest version brings the previous one back as the latest.

### Real-time with Laravel Reverb

- **Why Reverb:** it is Laravel's first-party, self-hosted WebSocket server. Unlike SSE it supports presence channels and client whispers, which presence and typing indicators need, and it does not hold a PHP process open per connection.
- **Channel auth uses the same JWT** as the REST API: `/api/broadcasting/auth` sits behind `auth:api`, and channel rules reuse `TaskPolicy`.
- **Task events carry no task data.** The task JSON includes per-user permissions (`can.update`, `can.delete`), so a single broadcast payload would be wrong for some viewers. Events only say what changed and who changed it, and each browser re-fetches with its own token. Comments are broadcast in full because they contain nothing user-specific.
- **Broadcasting never fails a request.** Events use `ShouldBroadcastNow` through `App\Support\Realtime::broadcast()`, which reports errors to the log instead of throwing. If Reverb is down, data is still saved; the UI just does not update live.
- **Role changes** fire from a `User::updated` model hook, so every Eloquent path notifies the user (`php artisan user:role`, tinker, a future admin page). A raw SQL `UPDATE` bypasses Eloquent and cannot be detected; that is why the command exists.
- **Typing indicators use client whispers** (browser → Reverb → browsers), never Laravel or the database. They are throttled to one signal per 1.5 s and expire after 3 s.
- The frontend shares one connection (`lib/echo.ts`), reference-counts channel subscriptions so components can share a channel, and disconnects on logout.

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
| Queue jobs (email on assignment, bulk status update, CSV/PDF export) | The queue already runs the attachment jobs; these three were not prioritised. | A `TaskObserver` dispatches `SendTaskAssignedNotification` when the assignee changes. A `BulkUpdateTaskStatus` job that broadcasts on `tasks`. An `ExportTasks` job that stores a CSV and notifies the user on their private channel. |
| Video streaming, video thumbnails, adaptive streaming | Optional bonus items; thumbnails and renditions need FFmpeg. | HTTP range responses for MP4/WebM, and an FFmpeg job for a poster frame and HLS renditions. |
| Caching (Redis), API response caching | Optional bonus items. | Redis caching of list queries keyed by filters and user role. |
| Version history UI | The API exists; the UI shows the latest version only. | A versions popover per attachment. |

## Testing

### Backend (implemented)

40 PHPUnit feature tests run against an in-memory SQLite database (`php artisan test`). The queue runs synchronously in tests, so upload → scan → thumbnail is exercised end to end:

- **Auth:** successful login, wrong password (401), validation (422), and logout that invalidates the token.
- **Tasks:** auth required (401), filtering and pagination, create (201), validation (422), policy (403 for an unrelated member), delete by creator (204), JSON 404.
- **Attachments** (`Storage::fake`): upload with a random stored name, download, delete, rejected type and size (422), file and thumbnail cleanup, thumbnail size, quarantine of a virus signature and of a double extension, a signature split across scan blocks, download blocked while scanning (423), and versioning (history, latest-only listing, fallback after delete).
- **Chunked upload:** assembly of a multi-chunk file, missing chunks (422), executable content renamed to `.pdf` (422), oversized/disallowed init (422), another user's upload (403), abort.
- **Real-time:** channel authorisation with the JWT (own vs other user's channel, guest 401, task channel, presence member data), and the events sent for comments, task create/update/delete, the attachment lifecycle (`uploaded → scanned → thumbnail_ready → deleted`) and `user:role`.

### Frontend (planned, not implemented)

**Status: planned.** I prioritised time for backend tests, because the business rules (authorization, validation, file handling) live in the API and are covered there. The frontend was verified with TypeScript (`npm run typecheck`), ESLint (`npm run lint`), a production build, and manual testing of the main flows.

Planned setup:

- **Unit/component** (Vitest + React Testing Library): the `api.ts` client (Bearer header, 401 redirect, error mapping), `TaskFilters`, `Pagination`, and `AttachmentUploader` client-side validation.
- **Integration**: pages with a mocked API (MSW) to cover the loading, error and empty states.
- **E2E** (Playwright): login → create task → upload file → comment → delete, against a seeded backend.
