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

Seeded by `DatabaseSeeder` (`php artisan db:seed`). The institutional accounts (item 1–5) are seeded in every environment; everything below them is demo/dev-only and guarded out of `production`.

### Institutional accounts (password `password123`)

| Login ID | Email | Role |
|---|---|---|
| `2026-10254` | `student@aitsa.edu.ph` | Student |
| `chair01` | `chair@aitsa.edu.ph` | Chair |
| `cashier01` | `cashier@aitsa.edu.ph` | Cashier |
| `registrar01` | `registrar@aitsa.edu.ph` | Registrar |
| `admin01` | `admin@aitsa.edu.ph` | Admin |

### Demo/dev-only accounts (non-production)

| Login ID | Email | Role | Password | Notes |
|---|---|---|---|---|
| `2300410` | `regular.demo@aitsa.test` | Student | `password` | Regular BSOA 1st year |
| `2300411` | `irregular.demo@aitsa.test` | Student | `password` | Irregular BSOA 2nd year, has one failed subject (`BSOA111`) |
| `2300420` | `clearance.demo@aitsa.test` | Student | `password` | Mid-clearance demo: chair approved, cashier/registrar pending |
| `faculty01` | `faculty01@faculty.aitsa.test` | Faculty | `password123` | Auto-assigned to non-clashing sections |
| `faculty02` | `faculty02@faculty.aitsa.test` | Faculty | `password123` | Auto-assigned to non-clashing sections |

### Pending applicants (Registrar → Applicant Queue)

Not real logins — these are `role: applicant` rows awaiting the Registrar to activate them into student accounts. One of each `applicant_type`, each left in a different reservation state so the queue's three states all have a row to demo:

| Name | Type | Program | Reservation state |
|---|---|---|---|
| Jasmine Reyes | NEW | BSOA | Reserved (fee paid) |
| Miguel Santos | TRANSFEREE | BOM | Wants to reserve (unpaid) |
| Karen Villanueva | RETURNEE | BTVTED | Not reserved → shows "Activate account" |

## Tests

```
php artisan test
```

## Project docs

Design specs and implementation plans for each feature gap live under `docs/superpowers/`.
