# District Badges – Backend

> Part of the [District Badges](../README.md) system. See also: [Webstore](../webstore/README.md) · [Design](../design/README.md) · [Postman](../postman/README.md)

The backend is a [CakePHP 5](https://cakephp.org) PHP application that provides the data management layer for the District Badges system. It handles badge stock, orders, invoices, groups, sections, users and the audit trail that keeps the stock ledger accurate.

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ≥ 8.2   |
| PostgreSQL | any recent supported release |
| [Composer](https://getcomposer.org) | ≥ 2.x |

## Getting Started

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Configure the environment

Copy the example environment file and fill in the values for your local setup:

```bash
cp config/.env.example config/.env
```

Key variables to set in `config/.env`:

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name (used for cache key prefixes) |
| `DEBUG` | Set to `true` in development, `false` in production |
| `SECURITY_SALT` | A long random string used for hashing |
| `APP_FULL_BASE_URL` | Full URL of this application (e.g. `https://badges.example.com`) |
| `DATABASE_URL` | Full DSN for the primary database (see below) |
| `DATABASE_TEST_URL` | Full DSN for the test database |
| `DISTRICT_CORE_DATA_URL` | Basic Auth-protected DistrictCoreData base/landing URL |
| `DISTRICT_CORE_DATA_USERNAME` | DistrictCoreData username |
| `DISTRICT_CORE_DATA_PASSWORD` | DistrictCoreData password |
| `WEBSTORE_ALLOWED_ORIGINS` | Comma-separated webstore origins permitted by CORS |

**Database DSN format:**

```
postgres://username:password@hostname/database_name?timezone=UTC
```

### 3. Run database migrations

```bash
bin/cake migrations migrate
```

Import the canonical groups, sections and their shared UUIDs after migrating:

```bash
bin/cake district_core_data:sync
```

### 4. Start the development server

```bash
bin/cake server -p 8765
```

Visit [http://localhost:8765](http://localhost:8765) to confirm the application is running.

## Project Structure

```
backend/
├── config/           # Application configuration, routes, migrations
│   ├── .env.example  # Environment variable template
│   ├── Migrations/   # Database migration files
│   ├── app.php       # Application-level defaults
│   └── routes.php    # URL routing definitions
├── src/
│   ├── Controller/   # Request handling – one controller per resource
│   ├── Model/
│   │   ├── Entity/   # ORM entity classes
│   │   └── Table/    # ORM table classes with associations & validation
│   ├── Service/      # Business logic separated from controllers
│   └── View/         # View helpers
├── templates/        # HTML templates (CakePHP .php template files)
├── tests/            # PHPUnit test suite
└── webroot/          # Public web root (index.php, static assets)
```

## Key Domain Concepts

| Resource | Description |
|----------|-------------|
| **Badges** | Scout badge catalogue with stock levels (`on_hand_quantity`, `pending_quantity`, `receipted_quantity`) |
| **Groups** | Scout groups imported from DistrictCoreData using stable shared UUIDs |
| **Sections** | Sections imported from DistrictCoreData and associated with a group |
| **Accounts** | A purchasing account within a group |
| **Orders** | An order placed by an account for one or more badges |
| **Order Lines** | Individual badge line items within an order |
| **Invoices** | Invoices raised against an account |
| **Fulfilments** | Records that a batch of stock has been dispatched |
| **Replenishments** | Records of stock received into the warehouse |
| **Stock Transactions** | Immutable ledger entries that track every stock movement |
| **Audits** | Periodic physical stock-count events |
| **Users** | Staff users who operate the system |

## Database Schema

```mermaid
erDiagram
    badges {
        uuid id PK
        string badge_name
        integer national_product_code
        json national_data
        boolean stocked
        integer on_hand_quantity
        integer receipted_quantity
        integer pending_quantity
        string latest_hash
        decimal price
    }
    groups {
        uuid id PK
        string group_name
        integer group_osm_id
        integer sort_order
    }
    sections {
        uuid id PK
        uuid group_id FK
        integer section_osm_id
        string section_name
        string section_type
    }
    accounts {
        uuid id PK
        string account_name
        uuid group_id FK
    }
    users {
        uuid id PK
        string first_name
        string last_name
        uuid account_id FK
        string email
        string login
        integer admin_role
        boolean can_login
    }
    orders {
        uuid id PK
        string order_number
        timestamp placed_date
        boolean fulfilled
        decimal total_amount
        integer total_quantity
        uuid account_id FK
        uuid user_id FK
    }
    order_lines {
        uuid id PK
        uuid order_id FK
        uuid badge_id FK
        integer quantity
        decimal amount
        boolean fulfilled
    }
    invoices {
        uuid id PK
        timestamp invoice_date
        timestamp due_date
        string invoice_number
        uuid account_id FK
    }
    invoice_lines {
        uuid id PK
        uuid invoice_id FK
        uuid badge_id FK
        string description
        integer quantity
        decimal unit_price
    }
    fulfilments {
        uuid id PK
        timestamp fulfilment_date
        string fulfilment_number
    }
    replenishments {
        uuid id PK
        timestamp created_date
        boolean order_submitted
        timestamp order_submitted_date
        boolean received
        timestamp received_date
        decimal total_amount
        integer total_quantity
        string wholesale_order_number
    }
    audits {
        uuid id PK
        uuid user_id FK
        timestamp audit_timestamp
        boolean audit_completed
    }
    stock_transactions {
        uuid id PK
        integer transaction_type
        timestamp transaction_timestamp
        uuid badge_id FK
        string audit_hash
        uuid fulfilment_id FK
        uuid audit_id FK
        uuid replenishment_id FK
        uuid order_line_id FK
        integer on_hand_quantity_change
        integer receipted_quantity_change
        integer pending_quantity_change
    }

    groups ||--o{ accounts : "has"
    groups ||--o{ sections : "has"
    accounts ||--o{ users : "has"
    accounts ||--o{ orders : "places"
    accounts ||--o{ invoices : "receives"
    orders ||--o{ order_lines : "contains"
    order_lines }o--|| badges : "for"
    invoices ||--o{ invoice_lines : "contains"
    invoice_lines }o--|| badges : "for"
    audits }o--|| users : "conducted by"
    fulfilments ||--o{ stock_transactions : "generates"
    audits ||--o{ stock_transactions : "generates"
    replenishments ||--o{ stock_transactions : "generates"
    order_lines ||--o{ stock_transactions : "generates"
    stock_transactions }o--|| badges : "affects"
```

> For a detailed explanation of how stock transactions are recorded and the derived line models (AuditLines, FulfilmentLines, ReplenishmentOrderLines, ReplenishmentReceiptLines), see [docs/stock-transactions.md](docs/stock-transactions.md).

## Webstore order API

`POST /api/orders.json` accepts a contact name and email, shared `group_id` and `section_id` UUIDs, and order lines containing only `badge_id` and `quantity`. It verifies that the section belongs to the group and calculates prices and totals from backend badge records; client-supplied totals are not trusted. The account, user, order, and lines are persisted synchronously in one transaction, and successful requests return HTTP `201`.

The API Gateway/SQS route accepts the identical JSON payload. The `orders:consume_queue` worker passes each SQS message body through the same `OrderPlacementService` used by the synchronous controller, so validation, party resolution, pricing, and persistence remain consistent across both entry points.

[`config/schema/scout-shop-order-v1.json`](config/schema/scout-shop-order-v1.json) is the canonical, versioned transport contract for both the `POST /api/orders.json` request body and the unchanged SQS message body. Edge services must reject payloads that fail this schema before enqueueing them. Database-backed checks, including group/section relationships and badge existence, are performed when the backend processes the valid payload.

Run `bin/cake district_core_data:sync` whenever the canonical group or section data changes. Deploy and sync DistrictCoreData changes before deploying a webstore build that contains the new UUIDs.

## Running Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit --colors=always
```

## Code Quality

Check coding standards (CakePHP CS rules via PHP_CodeSniffer):

```bash
composer cs-check
```

Auto-fix fixable violations:

```bash
composer cs-fix
```

Static analysis (PHPStan at level 8):

```bash
vendor/bin/phpstan analyse
```

## Configuration Reference

| File | Purpose |
|------|---------|
| `config/app.php` | Application-wide defaults (encoding, locale, timezone, cache, email) |
| `config/app_local.php` | Environment-specific overrides – **not committed to source control** |
| `config/.env` | Environment variables loaded in development – **not committed to source control** |
| `phpcs.xml` | PHP_CodeSniffer ruleset |
| `phpstan.neon` | PHPStan configuration |
| `phpunit.xml.dist` | PHPUnit test configuration |
