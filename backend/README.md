# Backend: Laravel 13 REST API

JWT-authenticated JSON API for the task management platform. See the [root README](../README.md) for the overview and the [setup guide](../documentation/setup-guide.md) for details.

```bash
composer install
cp .env.example .env && php artisan key:generate && php artisan jwt:secret
php artisan migrate --seed
php artisan serve        # http://localhost:8000/api
php artisan test         # feature tests (in-memory SQLite)
```

## Layout

| Path | Contents |
|---|---|
| `app/` | Application code (Laravel's equivalent of `src/`) |
| `app/Http/Controllers` | Thin controllers: Auth, Task, TaskAttachment, TaskComment, User |
| `app/Http/Requests` | Validation (FormRequests) |
| `app/Http/Resources` | JSON output (API Resources) |
| `app/Policies/TaskPolicy.php` | Authorization rules |
| `app/Models`, `app/Enums` | Eloquent models, status/priority/role enums |
| `config/` | `auth.php` (guard `api` = JWT), `jwt.php`, `cors.php` |
| `database/` | Migrations, factories, seeder |
| `routes/api.php` | All endpoints |
| `tests/Feature` | Auth, Task and Attachment tests |

API reference: [README endpoint table](../README.md#api-endpoints) and the [Postman collection](../documentation/api-docs/task-management.postman_collection.json).
