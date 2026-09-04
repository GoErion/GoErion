---
paths:
  - 'tests/Feature/**'
---

# Feature

## Minimum Feature Coverage
Cover the authorized path, validation failure, unauthenticated and unauthorized access, company-scoped uniqueness, cross-company access and relationship injection, important persistence and relationships, status transitions, and transaction rollback where applicable.

## Business Invariant Coverage
Where applicable, test exact money and rounding, totals, number formats and uniqueness, retries, stock movements and transfers, approved negative-stock behavior, payment allocations, balanced journals, immutability and reversals, and date, currency, and fiscal-period boundaries.
