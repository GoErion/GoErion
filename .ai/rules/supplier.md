---
paths:
  - 'app/**/Supplier/**'
  - 'app/Actions/Supplier/**'
---

# Supplier

## Supplier and Payables Boundaries
Keep supplier identity, contacts, company accounts, product information, terms, balances, payables, statements, and history within the Supplier domain. Do not assume supplier and customer workflows are identical because fields overlap.

## Serialize Supplier invariants
Lock the Company while allocating immutable Supplier numbers. Lock the parent Supplier before changing primary contacts or default addresses, and revalidate authorization inside transactions for direct Action use. Failed creation must roll back sequence allocation.
