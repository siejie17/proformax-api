# ProFormaX API (Backend)

Laravel 12 REST API for ProFormaX — building assessment, cost estimation, projects, and user authentication via [Laravel Sanctum](https://laravel.com/docs/sanctum).

## Prerequisites

Install these before setting up the project:

| Tool | Version | Notes |
|------|---------|--------|
| **PHP** | 8.2 or newer | Required extensions below |
| **Composer** | 2.x | [getcomposer.org](https://getcomposer.org/) |
| **Node.js** | 18+ (20 LTS recommended) | For Vite frontend assets |
| **npm** | Comes with Node.js | |
| **MySQL** | 8.0+ or **MariaDB** 10.3+ | Dump tested on MariaDB 11.8 |

### Required PHP extensions

Enable these in `php.ini` (common on XAMPP, Laragon, or system PHP):

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `tokenizer`
- `xml`

Verify with:

```bash
php -v
php -m
composer check-platform-reqs
```

Ensure the **MySQL/MariaDB client** is on your `PATH` (`mysql` command) if you import via CLI.

## Installation

### 1. Clone and enter the project

```bash
git clone <repository-url>
cd backend
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment file

```bash
cp .env.example .env
```

On Windows (PowerShell):

```powershell
Copy-Item .env.example .env
```

### 4. Application key

```bash
php artisan key:generate
```

### 5. Database setup

Choose **one** of the following. Do not run both on the same database.

| Approach | Best for | Database name |
|----------|----------|-----------------|
| **[A] SQL import (recommended)](#a-import-from-proformaxsql)** | Full schema + reference data, sample users/projects | `proformax` |
| **[B] Migrations + seeders](#b-fresh-database-migrations--seeders)** | Empty DB, minimal seed data only | `proformax_api` (or any name) |

---

#### A. Import from `proformax.sql`

The repository includes `proformax.sql` — a phpMyAdmin dump with tables, indexes, and seed data (locations, structures, costs, certifications, sample users, projects, etc.). The dump targets database name **`proformax`**.

**1. Set `.env` to match the dump**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proformax
DB_USERNAME=root
DB_PASSWORD=
```

**2. Create the database**

```sql
CREATE DATABASE proformax CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. Import the dump**

Obtain `proformax.sql` from the project maintainers and place it in the project root (`backend/`). Then run:

**Linux / macOS / Git Bash**

```bash
mysql -u root -p proformax < proformax.sql
```

**Windows (PowerShell or CMD)**

```powershell
mysql -u root -p proformax < proformax.sql
```

Replace `root` and the password prompt with your MySQL credentials.

**phpMyAdmin**

1. Open phpMyAdmin → create database `proformax` (collation `utf8mb4_unicode_ci`).
2. Select `proformax` → **Import** → choose `proformax.sql` → **Go**.
3. If the file is large, increase **Max allowed size** in phpMyAdmin or import via CLI.

**MySQL Workbench**

Server → **Data Import** → **Import from Self-Contained File** → select `proformax.sql` → set default target schema to `proformax` → **Start Import**.

**4. Verify import**

```bash
php artisan migrate:status
```

All migrations should show as **Ran** (the dump includes a populated `migrations` table). You can also check table counts in MySQL:

```sql
USE proformax;
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM items;
```

**5. Do not run** on an imported database:

- `php artisan migrate` (schema already applied)
- `php artisan db:seed` (would duplicate or conflict with existing data)

**Sample data in the dump** includes users, projects, costs, locations, tender indices, and form-related tables. Use existing accounts from the `users` table for login testing, or register a new user via `/api/register`.

---

#### B. Fresh database (migrations + seeders)

Use this when you want an empty database built only from Laravel migrations.

**1. Create the database** (name must match `.env`):

```sql
CREATE DATABASE proformax_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Default `.env.example`:

```env
DB_DATABASE=proformax_api
```

**2. Run migrations**

```bash
php artisan migrate
```

**3. Seed reference data**

```bash
php artisan db:seed
```

Seeds Malaysian locations, building structures, and a test user:

- Email: `test@example.com`
- Password: `password` (Laravel factory default)

**Optional extra seeders** (run only on a fresh migrated DB):

```bash
php artisan db:seed --class=LocationIndexSeeder
php artisan db:seed --class=TenderPriceIndexSeeder
php artisan db:seed --class=PrevProjectCostsSeeder
```

---

### 6. Storage symlink

Required for public file access (e.g. profile pictures):

```bash
php artisan storage:link
```

### 7. Install and build frontend assets

```bash
npm install
npm run build
```

For local development with hot reload, use `npm run dev` instead of `build`.

## Configuration (`.env`)

Copy `.env.example` to `.env` and set the values below.

### Application

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Display name | `ProFormaX` |
| `APP_ENV` | Environment | `local` / `production` |
| `APP_DEBUG` | Verbose errors (disable in production) | `true` |
| `APP_URL` | Public base URL of this API | `http://localhost:8000` |

### Database (MySQL / MariaDB)

| Variable | Description | SQL import | Fresh install |
|----------|-------------|------------|---------------|
| `DB_CONNECTION` | Driver | `mysql` | `mysql` |
| `DB_HOST` | Host | `127.0.0.1` | `127.0.0.1` |
| `DB_PORT` | Port | `3306` | `3306` |
| `DB_DATABASE` | Database name | **`proformax`** | `proformax_api` |
| `DB_USERNAME` | MySQL user | `root` | `root` |
| `DB_PASSWORD` | MySQL password | *(your password)* | *(your password)* |

### Session, cache, and queue

The default setup uses the **database** for sessions, cache, and queues. With **SQL import**, `cache`, `jobs`, `sessions`, and related tables are already in `proformax.sql`. With **migrations**, those tables are created automatically.

| Variable | Default | Purpose |
|----------|---------|---------|
| `SESSION_DRIVER` | `database` | Web session storage |
| `CACHE_STORE` | `database` | Application cache |
| `QUEUE_CONNECTION` | `database` | Background jobs |

### Mail (registration & password reset)

Users must verify email (`MustVerifyEmail`). For local development, `MAIL_MAILER=log` writes messages to `storage/logs/laravel.log`.

For real delivery, configure SMTP (or another driver):

| Variable | Description |
|----------|-------------|
| `MAIL_MAILER` | e.g. `smtp` |
| `MAIL_HOST` | SMTP host |
| `MAIL_PORT` | SMTP port |
| `MAIL_USERNAME` | SMTP user |
| `MAIL_PASSWORD` | SMTP password |
| `MAIL_FROM_ADDRESS` | Sender email |
| `MAIL_FROM_NAME` | Sender name |

Verification links use `APP_URL` (web routes under `/verify-email/...`).

### API authentication (Sanctum)

Mobile or SPA clients authenticate with **Bearer tokens** returned from `/api/login` and `/api/register`.

If a first-party SPA runs on another origin (e.g. `http://localhost:3000`), add it to stateful domains:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1:3000
```

Optional:

```env
SANCTUM_TOKEN_PREFIX=
```

### Optional services

Redis, AWS S3, and other integrations are preconfigured in `config/` but not required for local development. Leave defaults unless you use them.

## Running the application

### API server only

```bash
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

Health check: `http://127.0.0.1:8000/up`

### Full local stack (server + queue + Vite)

Runs the HTTP server, queue worker, and Vite dev server together:

```bash
composer run dev
```

### Queue worker (separate terminal)

If you dispatch queued jobs, keep a worker running:

```bash
php artisan queue:work
```

Or use the listener included in `composer run dev`:

```bash
php artisan queue:listen --tries=1
```

## Database file reference

| File | Description |
|------|-------------|
| `proformax.sql` | Full dump (~4.5k lines): schema, indexes, and data for `proformax` |
| `database/migrations/` | Laravel migrations (use with fresh install only) |
| `database/seeders/` | PHP seeders (use with fresh install only) |

**Tables included in the dump** (among others): `users`, `projects`, `items`, `costs`, `locations`, `structures`, `certifications`, `options`, `selections`, `user_answers`, `actual_user_answers`, `tender_price_indices`, `location_indices`, `prev_projects`, `cache`, `jobs`, `sessions`, `migrations`, etc.

## API overview

All routes in `routes/api.php` are prefixed with `/api`.

### Public endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/register` | Register user (sends verification email) |
| `POST` | `/api/login` | Login, returns Sanctum token |
| `POST` | `/api/forgot-password` | Request password reset |
| `POST` | `/api/reset-password` | Reset password with token |

### Protected endpoints (`Authorization: Bearer <token>`)

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/logout` | Revoke current token |
| `GET` | `/api/form-inputs` | Assessment form schema |
| `POST` | `/api/submit-assessment` | Submit assessment |
| `POST` | `/api/results` | Calculate / fetch results |
| `GET` | `/api/users/{userId}` | User profile |
| `GET` | `/api/users/{userId}/projects` | User projects |
| `GET` | `/api/projects/{projectId}` | Project details |
| … | … | See `routes/api.php` for full list |

### Web routes (browser)

Email verification, password reset UI, and privacy policy live in `routes/web.php` (no `/api` prefix), e.g.:

- `GET /verify-email/{id}/{hash}`
- `GET /reset-password/{token}`
- `GET /privacy-policy`

## Testing

PHPUnit uses an in-memory SQLite database (see `phpunit.xml`) — independent of `proformax.sql`:

```bash
composer test
```

Or:

```bash
php artisan test
```

## Production checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`.
2. Run `composer install --no-dev --optimize-autoloader`.
3. Run `npm ci && npm run build`.
4. **Database:** import `proformax.sql` into production MySQL **or** run `php artisan migrate --force` (not both on the same DB).
5. Run `php artisan config:cache`, `route:cache`, and `view:cache` if appropriate.
6. Configure real SMTP for mail.
7. Set a strong `APP_KEY` (never commit `.env`).
8. Point the web server document root to `public/`.
9. Ensure `storage/` and `bootstrap/cache/` are writable.
10. Run a persistent queue worker (Supervisor, systemd, etc.) if using queues.

## Project structure (high level)

```
app/
  Http/Controllers/   # API controllers (Auth, Form, Results, Projects, User)
  Models/             # Eloquent models
  Services/           # Business logic (costs, form mapping, green elements)
database/
  migrations/         # Schema (fresh install path)
  seeders/            # Reference data (fresh install path)
proformax.sql         # Full database dump (import path)
routes/
  api.php             # REST API
  web.php             # Verification & reset-password pages
config/               # Laravel & Sanctum configuration
```

## Troubleshooting

| Issue | What to try |
|-------|-------------|
| `SQLSTATE[HY000] [1049]` | Create the database; ensure `DB_DATABASE` matches (`proformax` after import, `proformax_api` for fresh install). |
| `mysql` not recognized (Windows) | Add MySQL `bin` to PATH (e.g. XAMPP/Laragon), or use phpMyAdmin import. |
| Import fails / timeout | Use CLI instead of phpMyAdmin; increase `max_allowed_packet` in MySQL config. |
| `Table already exists` on migrate | You imported `proformax.sql` — skip `migrate`; use `migrate:status` to confirm. |
| Empty API / no form data | Confirm import succeeded (`SHOW TABLES` in `proformax`); check `DB_DATABASE=proformax` in `.env`, then `php artisan config:clear`. |
| `No application encryption key` | Run `php artisan key:generate`. |
| 401 on API routes | Send `Authorization: Bearer <token>` from login/register response. |
| Emails not received locally | Check `storage/logs/laravel.log` when `MAIL_MAILER=log`. |
| Profile images 404 | Run `php artisan storage:link`. |

## License

MIT (Laravel framework). Application code follows the repository license.
