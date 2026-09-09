# Layout / Design System — Rollback Guide

This project supports toggling between **legacy UI** (original widget headers) and the **ERP design rollout** (light cards, page titles, list toolbars).

## Quick commands

From the project root (`/home/elitewave360/public_html/php`):

```bash
# Revert to legacy / pre-design UI
bash web/scripts/rollback-layout-system.sh

# Re-apply the saved design rollout
bash web/scripts/apply-layout-system.sh
```

Then hard-refresh: **Ctrl+Shift+R**

## In Cursor chat

- Say **rollback** → assistant runs `rollback-layout-system.sh`
- Say **apply design** or **re-apply layout** → assistant runs `apply-layout-system.sh`

## What is stored

| Folder | Purpose |
|--------|---------|
| `.rollback-layout-backup/design-rollout/` | Full design-system snapshot (pages + CSS/JS helpers) |
| `.rollback-layout-backup/pre-design/` | Optional file copies of legacy pages |
| `.rollback-layout-backup/files/` | Older pilot backup (legacy) |

## Current state

**Legacy UI is active** — no `ew-design-system.css`, no `ew-erp-layout.js`, classic `.heading` bars on pages.

Expense features (GCN expense, general expense, trip summary, expense type master) are kept; only layout wrappers were reverted.

## Notes

- Git-tracked pages (branch, pickup, vendor, client, invoice, transaction lists, etc.) restore from git `HEAD` on rollback.
- New expense pages restore from manual legacy wrappers when rollback runs.
- Business logic, database schema, and menus are **not** removed by rollback.
