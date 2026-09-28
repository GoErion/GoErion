---
paths:
  - 'app/Actions/Product/**'
---

# Product

## Authorize Product mutations at both entry points
Product Actions accept the authorized Company and actor, lock the Company before rechecking current database-backed membership and the required policy ability, and resolve related records through that Company. Form Request authorization does not replace direct Action authorization.

## Product SKU Sequences
Lock the Company before lazily creating its Product sequence. Initialize new sequences with SKU while preserving existing nonempty configured prefixes. Assign Company and immutable SKU explicitly on the server, outside ordinary mass assignment. Allocate through NumberSequenceService in the same transaction as Product creation so failures roll back advancement. Counters continue across years; year formatting does not reset them.
