---
paths:
  - 'database/**'
---

# Database

## Tenant Isolation — Database
Use numeric primary and foreign keys internally, unique UUID columns for publicly exposed entities, and company ownership foreign keys for tenant data. Tenant-specific unique constraints normally include company_id.
Choose indexes and foreign-key delete behavior deliberately. Financial, stock, and historical records must not disappear through habitual cascade deletion.
