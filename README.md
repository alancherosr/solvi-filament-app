Below is a complete **README.md** you can drop into the root of your Laravel + Filament ## Importing Your Spreadsheet

The importer expects the exact column headers found in the sample file.
If you added custom columns, rename or drop them **before** uploading the file.

Navigate to `/admin` and use the import action available on any resource page. The Filament import interface provides:

- **Drag-and-drop file upload** – supports Excel (.xlsx) files
- **Column mapping interface** – map your columns to the expected fields
- **Validation preview** – see any errors before committing the import
- **Progress tracking** – real-time import status

* Accounts and Categories are **up-serted** by name.
* Transactions are inserted idempotently using the `Id` column (UUIDs also work).
* It's your data; the import will abort rather than overwrite balances it can't reconcile.reflects the structure of **presupuesto\_familiar.xlsx**—accounts, categories, transactions, budgets, and retirement projections—and documents both the UI and the REST API without over-selling anything.

````markdown
# Personal Finances – Laravel + Filament

Stream–lined bookkeeping for households that prefer self-hosting and full data ownership.
The app mirrors the structure of `presupuesto_familiar.xlsx` (included in `/database/import-samples`)
and exposes the same data through an **admin UI (Filament v3)** and a **token-protected REST API**.

---

## Table of Contents
1. [Features](#features)
2. [Tech Stack](#tech-stack)
3. [Quick Start](#quick-start)
4. [Data Model](#data-model)
5. [Importing Your Spreadsheet](#importing-your-spreadsheet)
6. [REST API](#rest-api)
7. [Running Tests](#running-tests)
8. [Roadmap](#roadmap)
9. [License](#license)

---

## Features
- **Accounts** – current balance, debits, credits (mirrors sheet `accounts`).
- **Categories & Budgets** – monthly limits, spend, % used (`categories`).
- **Transactions** – one table, dual columns `debit` / `credit` just like the XLSX.
- **Retirement Projections** – simple yearly compound view (`retirement`).
- **Dashboards** – Filament widgets: net worth, over-budget categories, month-to-date cash-flow.
- **Excel Importer** – native Filament import action with drag-and-drop interface for easy workbook uploads.
- **JSON REST API** – documented below; guarded by Laravel Sanctum.
- **CI** – basic Pest tests for endpoints, importer, and policies.

---

## Tech Stack
| Layer            | Choice                              | Why not something else? |
|------------------|-------------------------------------|-------------------------|
| Framework        | Laravel 11                          | First-class Sanctum & job queues |
| Admin UI         | Filament v3                         | Ships opinionated widgets, RBAC |
| Excel import     | Filament native import actions      | Built-in drag-and-drop, validation |
| Auth API         | Laravel Sanctum                     | Simpler than Passport, fine for first-party SPA |
| DB               | MySQL/PostgreSQL (pick one)         | Both tested in CI |
| Tests            | Pest + Laravel-test-factory-helpers | Concise syntax, readable failures |

---

## Quick Start

```bash
git clone <repo> personal-finances
cd personal-finances

# PHP, Composer & Node are assumed pre-installed
composer install
npm install && npm run build            # for Filament assets

cp .env.example .env
php artisan key:generate

# adjust DB credentials then:
php artisan migrate --seed              # seeds with empty accounts/categories

# optional: import the sample workbook via Filament admin UI
# Navigate to /admin and use the import action on any resource page

php artisan serve                        # http://127.0.0.1:8000
````

Log in at `/admin` with the admin credentials printed by the seeder.

---

## Data Model

| Table          | Key Columns                                                       | Source sheet |
| -------------- | ----------------------------------------------------------------- | ------------ |
| `accounts`     | `name`, `start_balance`, `balance`                                | accounts     |
| `categories`   | `name`, `budget`, `spent`, `budget_left`                          | categories   |
| `transactions` | `date`, `concept`, `account_id`, `category_id`, `debit`, `credit` | transactions |
| `retirements`  | `year`, `amount`, `interest`, `rent_interest`                     | retirement   |

*Yes, the workbook has a `budget` sheet. It’s more of a pivot; we recompute that view on the fly.*

---

## Importing Your Spreadsheet

The importer expects the exact column headers found in the sample file.
If you added custom columns, rename or drop them **before** running the command.

```bash
php artisan finances:import storage/app/my_budget.xlsx
```

* Accounts and Categories are **up-serted** by name.
* Transactions are inserted idempotently using the `Id` column (UUIDs also work).
* It’s your data; the command will abort rather than overwrite balances it can’t reconcile.

---

## REST API

> All endpoints require a bearer token from `/api/auth/token` (email + password).
> Send `Accept: application/json`; rate-limit is 60 req/min by default.

### Accounts

| Verb   | URI                  | Action                                 |
| ------ | -------------------- | -------------------------------------- |
| GET    | `/api/accounts`      | List all accounts with current balance |
| GET    | `/api/accounts/{id}` | Single account                         |
| POST   | `/api/accounts`      | Create (`name`, `start_balance`)       |
| PUT    | `/api/accounts/{id}` | Update                                 |
| DELETE | `/api/accounts/{id}` | Soft-delete                            |

### Categories & Budgets

| Verb  | URI                           | Notes                                      |
| ----- | ----------------------------- | ------------------------------------------ |
| GET   | `/api/categories`             | Budget, spent, budget\_left, percent\_used |
| PATCH | `/api/categories/{id}/budget` | Update monthly limit                       |
| GET   | `/api/categories/over-budget` | Convenience endpoint                       |

### Transactions

| Verb   | URI                      | Filters                                                 |
| ------ | ------------------------ | ------------------------------------------------------- |
| GET    | `/api/transactions`      | `?from=2025-01-01&to=2025-01-31&category=Groceries`     |
| POST   | `/api/transactions`      | `date, concept, account_id, category_id, debit, credit` |
| PUT    | `/api/transactions/{id}` | Immutability is overrated; still allowed                |
| DELETE | `/api/transactions/{id}` | Soft-delete                                             |

### Retirement

\| GET | `/api/retirement` | Current projection |
\| POST | `/api/retirement/recalculate` | Payload: `initial_amount`, `annual_contribution`, `interest_rate` |

---

## Running Tests

```bash
php artisan test          # API & importer
npm run test              # if you add front-end coverage
```

CI fails on:

* missing database migrations
* any un-documented response changes (strict JSON snapshots)

---

## Roadmap

* **CSV export** for accountants who refuse JSON.
* Envelope budgeting, zero-based planning.
* Webhooks: push a Net-Worth-Updated event to n8n / Zapier.
* OAuth for multi-device clients (if Sanctum ever feels limiting).

Feedback and PRs are welcome. Just bring good test coverage.

---

## License

MIT. No warranty. Back up your data—seriously.

```

Feel free to adjust project name, author, or roadmap items to match your actual repository.
```
