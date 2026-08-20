# Transaction Expense

Transaction Expense is a Laravel + Vue + Inertia.js application for managing a shared income and expense ledger. It includes categories, chart-of-account entries, transactions, dashboard totals, profit/loss reporting, and XLSX export.

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- SQLite (the default database)

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed --no-interaction
npm install
npm run build
php artisan serve
```

Open <http://localhost:8000> after starting the server.

The default seeder creates the accounting data and a demo user:

| Field    | Value              |
| -------- | ------------------ |
| Email    | `demo@example.com` |
| Password | `password`         |

The seeded ledger contains sample transactions from 2022 through 2026. The demo credentials are for local development only and must be changed or removed before a production deployment.

## Features

- Category management
- Chart-of-account management
- Transaction management
- Dashboard & Profit/loss reporting
- Import transactions
- Audit logging
