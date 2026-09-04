---
paths:
  - 'app/**/Inventory/**'
---

# Inventory

## Inventory Domain Boundaries
Keep stock movements, balances, transfers, adjustments, reservations, counts, tracking, and valuation within Inventory. Preserve immutable auditable movements; never mutate product stock directly from a controller, and do not invent negative-stock, costing, reservation, backdating, conversion, or rounding rules.
