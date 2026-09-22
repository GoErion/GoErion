---
paths:
  - 'database/migrations/**'
  - 'database/migrations/*company_invitation*.php'
---

# Migrations

## Safe Tenant Data Modeling
Use deliberate foreign keys, delete behavior, constraints, and indexes. Tenant-specific uniqueness normally includes company_id; required columns must reflect real invariants, and financial, stock, or historical records must not disappear through habitual cascades.

## Forward-Only Migration Safety
Inspect current models and migrations first. Prefer a new forward migration for shared schemas, do not edit historical migrations without confirming resetability, and provide a coherent down method where practical. Never run destructive database commands without explicit permission.

## Money Quantity and Currency Storage
Never use floating-point database types for money. Use the approved decimal or minor-unit strategy with deliberate precision and scale, clear currency context, and independently appropriate quantity precision.

## Preserve company-aware invitation history
Invitation migrations preserve historical invitation records while removing plaintext credentials. Invitation-role pivots must enforce invitation/company and role/company alignment. Accepted, cancelled, and expired history must not block role deletion permanently. Invitation and access-history dates use the approved UTC-preserving storage strategy, and unsafe rollback limitations must be explicit.

## Customer group membership uses composite tenant keys
Customer group memberships have one row per Customer. Both Customer and group references include company_id in composite foreign keys; migration preflight rejects legacy cross-company assignments before DDL. Preserve membership on migration rollback and preserve Customers when a group is deleted.
