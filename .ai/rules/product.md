---
paths:
  - 'app/Actions/Product/**'
---

# Product

## Product SKU Sequences
Generate product SKUs through the approved number-sequence service and initialize the product sequence lazily on first use. Keep sequence generation in the transaction that persists the associated record unless approved gaps are acceptable.
