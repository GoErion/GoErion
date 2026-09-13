---
paths:
  - 'app/**/Company/**'
  - 'app/**/Company/*Invitation*.php'
---

# App Company

## Company Domain Boundaries
Keep company profiles, settings, branches, warehouses, locations, number sequences, membership, and company configuration within the Company domain. Use company_name as the actual company-name field and resolve key/value settings by key or an approved access layer.

## Keep invitation credentials digest-only
Company invitation bearer tokens contain at least 256 bits of cryptographic randomness. Persist only the deterministic token digest; never persist, log, flash, serialize, or place the raw token in access-event metadata. Raw tokens may exist only in a narrowly scoped, single-consumption in-memory delivery result, and resend must rotate the digest so previous links become invalid.
