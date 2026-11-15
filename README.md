# FinCore Business Management System

A MySQL-backed web app for running a small business's finances: sales, payments, expenses, products, inventory, customers and employees, with a live dashboard of revenue, profit and low-stock alerts.

**Stack:** PHP 8 · MySQL / MariaDB · vanilla JavaScript · Chart.js

| Dashboard | Records |
|---|---|
| Revenue, expenses and net profit at a glance, a six-month revenue trend, low-stock alerts, and the latest sales and expenses. | Add, edit and delete customers, employees, products, inventory, payments and expenses. Recording a sale checks stock and updates inventory through database triggers. |

## Features

- **17-table relational schema** with primary and foreign keys, covering customers, employees, products, inventory, sales (with a `sale_product` line-item table), payments, expenses, accounts payable/receivable, revenue streams, reports and audit logs.
- **Triggers** that keep inventory in step with sales, reject invalid payment amounts, and log deleted customers, employees and products.
- **Reporting queries** for monthly revenue, expenses, profit and margin, top customers, outstanding balances, best-selling products and inventory value (`database/queries/`).
- **Secure by default:** password-hashed login, CSRF tokens on every form, prepared statements for every query, output escaping, hardened session cookies and friendly error messages that never expose database internals.
- **Responsive UI** that works on phones (slide-out menu, scrollable tables) through to desktop.

## Run it locally

Requirements: PHP 8.1+ with `mysqli`, and MySQL 8 or MariaDB 10.6+.

1. **Create the database and an app user** (the app should not connect as `root`):

   ```sql
   CREATE DATABASE fincore;
   CREATE USER 'fincore_app'@'localhost' IDENTIFIED BY 'choose-a-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON fincore.* TO 'fincore_app'@'localhost';
   ```

   ```bash
   mysql -u root -p fincore < database/fincore.sql
   ```

2. **Configure the app:**

   ```bash
   cp app/config.example.php app/config.php
   php -r 'echo password_hash("your-admin-password", PASSWORD_DEFAULT), "\n";'
   ```

   Put the database password and the printed hash into `app/config.php`. That file is git-ignored, so real credentials never get committed.

3. **Start it:**

   ```bash
   php -S localhost:8000 -t app
   ```

   Open http://localhost:8000 and sign in as `admin` with the password you hashed. On Apache or nginx, point the document root at `app/`.

## Tests

`tests/smoke_test.sh` runs end-to-end checks against a running instance: login and logout, every page rendering without PHP errors, CSRF rejection, HTML/JS injection through a customer name, SQL characters in search, invalid sale quantities, and friendly foreign-key errors. It adds one test customer.

```bash
tests/smoke_test.sh http://localhost:8000 admin 'your-admin-password'
```

## Project structure

```text
app/                    the web app (document root)
  includes/bootstrap.php  config, session, CSRF, escaping and DB helpers
  includes/sidebar.php    navigation
  assets/                 styles and shared JavaScript
  *.php                   one page per area (dashboard, sales, payments, …)
  config.example.php      copy to config.php
database/
  fincore.sql             schema, triggers and sample data
  queries/                reporting and financial-analysis queries
tests/smoke_test.sh     end-to-end checks
```

## Team

Built February–April 2026.

- Noel Mekogue: project manager
- Haley Harper: database architect
- Dylan Scott: backend developer
- Tayla Scott: data analyst
- Joshua Mays: financial systems analyst
- Kai Ilela: frontend developer
