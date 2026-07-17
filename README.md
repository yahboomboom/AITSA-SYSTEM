# AITSA-SYSTEM

Enrollment with Clearance System of AITSA — a BSIT capstone project (Pamantasan ng Cabuyao) implementing student enrollment, multi-department clearance, change of matriculation, faculty/room loading, and sandbox payments.

**Stack:** Laravel 12 (Blade + PHP), React 18 islands (Vite), Tailwind (CDN), Sanctum (stateful cookie auth), SQLite by default.

## Setup (local, XAMPP)

1. Clone the repo and install dependencies:
   ```
   composer install
   npm install
   ```
2. Copy the env file and generate an app key:
   ```
   cp .env.example .env
   php artisan key:generate
   ```
3. Run migrations and seed demo data (creates demo student/staff accounts, non-production only):
   ```
   php artisan migrate --seed
   ```
4. Build frontend assets (or run Vite in dev mode alongside `php artisan serve`):
   ```
   npm run build
   # or, in a separate terminal:
   npm run dev
   ```
5. Start the app on **port 8000** — required, since it's in Sanctum's default stateful-domain whitelist (`config/sanctum.php`); other ports will 401 on `/api/*` calls:
   ```
   php artisan serve --port=8000
   ```

## Demo accounts

Seeded by `DatabaseSeeder` (guarded to non-production environments). Student accounts use password `password`; staff accounts (chair/cashier/registrar/faculty/department officer) use `password123`.

| Login ID | Role |
|---|---|
| `2300410` | Student (regular) |
| `2300411` | Student (irregular) |
| `2300420` | Student (mid-clearance demo: chair approved, cashier/registrar pending) |
| `faculty01`, `faculty02` | Faculty |
| `*01` staff logins | Chair / Cashier / Registrar / Department Officer |

## Tests

```
php artisan test
```

## Project docs

Design specs and implementation plans for each feature gap live under `docs/superpowers/`.
