# Setup Guide

## Requirements

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.3+ (tested with 8.4) | extensions: `pdo_mysql`, `sodium` (JWT), `fileinfo` (MIME detection), `mbstring`, `openssl`. `pdo_sqlite` is needed for tests. |
| Composer | 2.x | |
| MySQL | 8.x | MariaDB 10.6+ should also work |
| Node.js | 22 LTS | npm 10 |

## 1. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret            # writes JWT_SECRET to .env
```

Edit `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

Create the database and data with **one** of these options:

```bash
# A) migrations + seeder
php artisan migrate --seed

# B) import the dump (creates the `task_management` database itself)
mysql -u root < ../database/database.sql
```

Run the API and the tests:

```bash
php artisan serve                 # http://localhost:8000
php artisan test                  # 17 feature tests, in-memory SQLite
```

## 2. Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.example .env.local        # NEXT_PUBLIC_API_URL=http://localhost:8000/api
npm run dev                       # http://localhost:3000
```

Other scripts: `npm run build`, `npm start`, `npm run lint`, `npm run typecheck`.

Log in with `admin@example.com` / `password` (admin) or `user@example.com` / `password` (member).

## 3. Windows / Laragon notes

### PHP extensions and upload limits

The PHP CLI that runs `php artisan serve` may ship with some extensions disabled. Find its `php.ini` with `php --ini`, then make sure these lines are **not** commented out:

```ini
extension=fileinfo
extension=pdo_mysql
extension=sodium        ; required by jwt-auth (composer install fails without it)
extension=pdo_sqlite    ; required by `php artisan test`
extension=sqlite3
```

Attachments can be up to 20 MB, but PHP's defaults (2M / 8M) are lower. Raise them, or uploads fail before Laravel sees them:

```ini
upload_max_filesize = 25M
post_max_size = 30M
```

In Laragon, you can do both through **Menu → PHP → Extensions** and **Menu → PHP → php.ini**, then restart. Check with `php -m`.

### The `&` in the path problem

If the project sits in a folder whose path contains `&` (e.g. `C:\Users\me\Desktop\Apply Kerjaan & Magang\...`), npm's Windows `.cmd` shims break, because `cmd.exe` treats `&` as a command separator. You see errors such as:

```
'Magang\project-root\frontend\node_modules\.bin\' is not recognized as an internal or external command
Error: Cannot find module 'C:\Users\...\Desktop\eslint\bin\eslint.js'
```

**Workaround (already applied):** the scripts in `frontend/package.json` call the tools through `node` with a relative path instead of the `.bin` shims:

```json
"dev": "node node_modules/next/dist/bin/next dev",
"lint": "node node_modules/eslint/bin/eslint.js"
```

So `npm run dev`, `npm run build` and the other scripts work from any path. `npx <tool>` in that folder still breaks. Either use the `npm run` scripts, or move the project to a path without `&`, which is the cleanest fix. Always quote the path in PowerShell: `cd "C:\...\Apply Kerjaan & Magang\project-root"`.

## 4. Deployment (brief)

A typical small production setup: a VPS or PaaS for the API, managed MySQL, and Vercel or a Node host for the frontend.

**Backend**

1. Server with PHP 8.3+ (FPM) and Nginx, with the document root set to `backend/public`.
2. `composer install --no-dev --optimize-autoloader`
3. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, production `APP_URL`, DB credentials, `JWT_SECRET`, and `CORS_ALLOWED_ORIGINS=https://your-frontend.example`.
4. `php artisan migrate --force` (seed only for demo environments).
5. `php artisan config:cache && php artisan route:cache && php artisan event:cache`
6. Make `storage/` and `bootstrap/cache/` writable by the web user. Attachments live in `storage/app/private`, so put that on a persistent volume, or switch the disk to S3.
7. Set Nginx `client_max_body_size 30m;` and PHP `upload_max_filesize` / `post_max_size` as above.
8. Serve over HTTPS only. The JWT is a bearer credential.
9. Once queue jobs are added: run `php artisan queue:work` under Supervisor or systemd.

**Frontend**

1. Set `NEXT_PUBLIC_API_URL=https://api.your-domain.example/api` **at build time**. It is inlined into the bundle.
2. `npm ci && npm run build && npm start` (port 3000 behind a reverse proxy), or deploy the `frontend/` folder to Vercel.

**Checklist:** HTTPS on both apps, the CORS origin matches the frontend URL exactly, `APP_DEBUG=false`, and a backup strategy for both the database and `storage/app/private`.
