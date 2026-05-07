# PeerLink — Developer Setup Guide

This guide gets a new developer from zero to a running local instance.

---

## Prerequisites

Install these before starting:

| Tool | Version | Notes |
|------|---------|-------|
| PHP | 8.2+ | With extensions: `pdo_mysql`, `mbstring`, `xml`, `curl` |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Node.js | 20+ | [nodejs.org](https://nodejs.org) |
| MySQL | 8.0+ | Or MariaDB 10.6+ |
| Git | any | |

> **Windows tip:** Use Git Bash for all terminal commands in this guide, not PowerShell.

---

## 1. Clone and Navigate

```bash
git clone <repo-url>
cd PeerLink/Peerlink
```

The Laravel application lives inside `Peerlink/`. All commands below run from that directory.

---

## 2. Install PHP Dependencies

```bash
composer install
```

---

## 3. Install JavaScript Dependencies

```bash
npm install
```

---

## 4. Create the Environment File

```bash
# Mac/Linux/Git Bash
cp .env.example .env

# Windows Command Prompt
copy .env.example .env
```

---

## 5. Generate the Application Key

```bash
php artisan key:generate
```

---

## 6. Configure the Database

Open `.env` and update the database section:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=peerlink
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

> The default `.env.example` points to SQLite (used for tests). Change `DB_CONNECTION` to `mysql` for local dev.

### Create the MySQL database

```sql
CREATE DATABASE peerlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 7. Set Up the Database

**Option A — Fresh install from migrations + seeder (recommended for dev)**

```bash
php artisan migrate --seed
```

This runs all migrations in `database/migrations/` and seeds 8 test users with realistic data.

**Option B — Import the production snapshot first, then migrate**

If you have the full course/division data from `database.sql`:

```bash
mysql -u root -p peerlink < database/database.sql
php artisan migrate
```

The migrations are idempotent — they use `hasTable()` / `hasColumn()` guards so they skip anything `database.sql` already created.

---

## 8. Start the Development Servers

You need two terminals running simultaneously.

**Terminal 1 — Laravel server:**

```bash
php artisan serve
```

The app is now accessible at `http://127.0.0.1:8000`.

**Terminal 2 — Vite asset bundler:**

```bash
npm run dev
```

Keep both terminals open while you work. Vite hot-reloads CSS and JS changes instantly.

---

## 9. Test Accounts

After seeding, these accounts are available (password: `password`):

| Email | Name | Role(s) |
|-------|------|---------|
| `alex.santos@up.edu.ph` | Alex Santos | Tutor (CMSC121, CMSC122) |
| `maria.cruz@up.edu.ph` | Maria Cruz | Tutor (MATH18, STAT105) |
| `ramon.delapena@up.edu.ph` | Ramon dela Pena | Tutor (CMSC11, CMSC12) |
| `bianca.reyes@up.edu.ph` | Bianca Reyes | Tutee |
| `carlo.manalo@up.edu.ph` | Carlo Manalo | Tutee |
| `diana.lim@up.edu.ph` | Diana Lim | Tutee |
| `elena.navarro@up.edu.ph` | Elena Navarro | Tutor (CMSC13) + Tutee |
| `francis.tan@up.edu.ph` | Francis Tan | Tutor (CMSC10) + Tutee |

> **Registration note:** Only `@up.edu.ph` and `@edu.ph` email addresses are accepted at the register page.

---

## 10. Running Tests

Tests use SQLite in-memory — no MySQL setup needed.

```bash
cd Peerlink
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

The SQLite migration path is separate from MySQL: the `000005` migration creates tables with the correct schema from the start, and the later `change_tutor_expertise` migration skips SQLite entirely.

---

## Project Structure (key directories)

```
Peerlink/
├── app/
│   ├── Http/Controllers/Api/   # All JSON API controllers
│   └── Models/                  # Eloquent models (custom table names)
├── database/
│   ├── migrations/              # All schema changes, dual MySQL/SQLite
│   ├── seeders/DatabaseSeeder.php
│   ├── database.sql             # MySQL snapshot (ground truth for prod bootstrap)
│   └── seed.sql                 # Legacy — superseded by DatabaseSeeder
├── public/
│   ├── app.js                   # All frontend logic (vanilla JS SPA)
│   └── style.css                # All styles (compiled, edit this directly)
├── resources/views/
│   ├── dashboard.blade.php      # Single-page app shell
│   └── auth/                    # Login / register Blade views
└── routes/
    ├── web.php                  # All routes (web + API prefix)
    └── auth.php                 # Auth routes with rate limiting
```

---

## Known Gotchas

**1. Session driver must be `database`**
The default `.env.example` sets `SESSION_DRIVER=database`. Run `php artisan migrate` before first use or sessions will fail.

**2. `public/style.css` is the live file**
The CSS served to users is `public/style.css`, not `resources/css/style.css`. Edit `public/style.css` directly for style changes — Vite compiles `resources/css/` into `public/` during `npm run dev`, which may overwrite manual edits. If you see your CSS changes disappear, check `vite.config.js`.

**3. UUID primary keys everywhere**
All custom tables use `char(36)` UUIDs, not auto-increment integers. If you add a new table with a foreign key to `Users`, use `char(36)` not `bigint`.

**4. Table names are PascalCase**
Models specify `protected $table = 'Users'` etc. MySQL is case-sensitive on Linux — keep exact casing.

**5. MySQL vs SQLite dual migrations**
Any migration that uses MySQL-specific syntax (ENUMs, `SET FOREIGN_KEY_CHECKS`, raw `ALTER TABLE`) must include an `if (DB::getDriverName() !== 'mysql') return;` guard so PHPUnit tests don't break.

**6. Rate limits on auth**
Login and register are throttled at 10 requests/minute per IP. During manual testing, if you hit 429 errors, wait 60 seconds or use a different test account.
