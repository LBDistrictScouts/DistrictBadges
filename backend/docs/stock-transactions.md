# Stock Transactions Model

This document describes the `StockTransactions` model and the derived line models
that are mapped to the same `stock_transactions` table.

## Core Model

`StockTransactions` is the base table/model for all stock movement records.
It owns the shared behaviors and hooks, including:

- `beforeSave` and `beforeRules` to generate `audit_hash`
- `afterSave` to refresh the parent `Badge` stock totals and `latest_hash`

## Derived Line Models

The following models inherit from `StockTransactionsTable` and use the same
`stock_transactions` table. They are role-specific aliases that apply default
filters and validation rules.

| Model | Table Alias | Required FK | TransactionType |
| --- | --- | --- | --- |
| `AuditLines` | `stock_transactions` | `audit_id` | `TransactionType::Audit` (0) |
| `FulfilmentLines` | `stock_transactions` | `fulfilment_id` | `TransactionType::Fulfilment` (2) |
| `ReplenishmentOrderLines` | `stock_transactions` | `replenishment_id` | `TransactionType::ReplenishmentOrder` (3) |
| `ReplenishmentReceiptLines` | `stock_transactions` | `replenishment_id` | `TransactionType::ReplenishmentReceipt` (4) |

Notes:

- All derived models inherit the same `beforeSave`, `beforeRules`, and `afterSave`
  behavior from `StockTransactionsTable`.
- Each derived model applies a default `beforeFind` filter to constrain results
  to the correct `TransactionType`, and validation requires the relevant foreign
  key.
- The spelling `Receipt` matches existing code and class names. If you want to
  correct this to `Receipt`, the rename should be done everywhere (class names,
  controller, fixtures, routes, templates, associations).

## Enum Mapping

The `TransactionType` enum is defined in
[TransactionType.php](backend/src/Model/Enum/TransactionType.php).
These values are used by the derived line models as the canonical type mapping.
