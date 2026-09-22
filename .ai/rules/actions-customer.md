---
paths:
  - 'app/Actions/Customer/**'
---

# Actions Customer

## Customer numbering is company locked and immutable
Allocate CUS-YEAR-000001 codes inside Customer creation while locking the Company and its number sequence. Create the sequence lazily, keep numbering continuous across years, and never accept or update a Customer code from request input. Roll back sequence allocation when creation fails.

## Contact and address defaults lock the parent Customer
Lock the parent Customer before changing contacts or addresses. Keep exactly one primary contact when contacts exist and one default address per populated type; promote the oldest remaining record after removal and repair both types when an address changes type.
