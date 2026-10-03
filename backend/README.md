# Backend: Laravel 13 REST API

JWT-authenticated JSON API for the task management platform, with a queue for attachment processing and Laravel Reverb for real-time updates. See the [root README](../README.md) for the overview and the [setup guide](../documentation/setup-guide.md) for details.

```bash
composer install
cp .env.example .env && php artisan key:generate && php artisan jwt:secret
php artisan migrate --seed
composer run dev         # API (http://localhost:8000/api) + queue worker + Reverb (ws://localhost:8080)
php artisan test         # 40 feature tests (in-memory SQLite)
```

## Layout

| Path | Contents |
|---|---|
| `app/` | Application code (Laravel's equivalent of `src/`) |
| `app/Http/Controllers` | Thin controllers: Auth, Task, TaskAttachment, ChunkedUpload, TaskComment, User |
| `app/Http/Requests` | Validation (FormRequests) |
| `app/Http/Resources` | JSON output (API Resources) |
| `app/Policies/TaskPolicy.php` | Authorization rules |
| `app/Models`, `app/Enums` | Eloquent models, status/priority/role enums |
| `app/Jobs` | Queue jobs: `ScanAttachment` (virus scan simulation), `GenerateThumbnail` |
| `app/Events` | Broadcast events: `TaskChanged`, `CommentCreated`, `AttachmentChanged`, `RoleChanged` |
| `app/Support/Realtime.php` | Broadcast helper that never fails the request if Reverb is down |
| `config/` | `auth.php` (guard `api` = JWT), `jwt.php`, `cors.php`, `broadcasting.php`, `reverb.php` |
| `database/` | Migrations, factories, seeder |
| `routes/api.php` | All endpoints |
| `routes/channels.php` | WebSocket channel authorization (private and presence) |
| `routes/console.php` | `user:role {email} {role}`, `realtime:ping {email}` |
| `tests/Feature` | Auth, Task, Attachment, ChunkedUpload, BroadcastAuth and RealtimeEvents tests |

API reference: [README endpoint table](../README.md#api-endpoints) and the [Postman collection](../documentation/api-docs/task-management.postman_collection.json).
