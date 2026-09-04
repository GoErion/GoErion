---
paths:
  - 'database/migrations/**'
---

# Migrations

## Safe Tenant Data Modeling
Use deliberate foreign keys, delete behavior, constraints, and indexes. Tenant-specific uniqueness normally includes company_id; required columns must reflect real invariants, and financial, stock, or historical records must not disappear through habitual cascades.

## Forward-Only Migration Safety
Inspect current models and migrations first. Prefer a new forward migration for shared schemas, do not edit historical migrations without confirming resetability, and provide a coherent down method where practical. Never run destructive database commands without explicit permission.

## Money Quantity and Currency Storage
Never use floating-point database types for money. Use the approved decimal or minor-unit strategy with deliberate precision and scale, clear currency context, and independently appropriate quantity precision.
