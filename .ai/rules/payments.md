---
paths:
  - 'app/**/Payments/**'
---

# Payments

## Payments Domain Boundaries
Keep receipts, supplier payments, refunds, methods, allocations, deposits, and reversals within Payments. Creation and allocation are separate unless an approved workflow combines them; completed payments are corrected through auditable reversals, never silent overwrite or deletion.
