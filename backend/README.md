# District Badges – Backend

The backend is a [CakePHP 5](https://cakephp.org) PHP application that provides the data management layer for the District Badges system. It handles badge stock, orders, invoices, accounts, groups, users and the audit trail that keeps the stock ledger accurate.

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ≥ 8.2   |
| MySQL / MariaDB | any recent release |
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

**Database DSN format:**

```
mysql://username:password@hostname/database_name?encoding=utf8mb4&timezone=UTC&cacheMetadata=true
```

### 3. Run database migrations

```bash
bin/cake migrations migrate
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
| **Groups** | Scout groups that hold accounts |
| **Accounts** | A purchasing account within a group |
| **Orders** | An order placed by an account for one or more badges |
| **Order Lines** | Individual badge line items within an order |
| **Invoices** | Invoices raised against an account |
| **Fulfilments** | Records that a batch of stock has been dispatched |
| **Replenishments** | Records of stock received into the warehouse |
| **Stock Transactions** | Immutable ledger entries that track every stock movement |
| **Audits** | Periodic physical stock-count events |
| **Users** | Staff users who operate the system |

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
