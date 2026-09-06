---
paths:
  - 'app/Models/Auth/**'
---

# Models Auth

## Company-aware user role pivots
Every user_roles write includes company_id, assigned_by, and timestamps. The target must already belong to company_user, the role must belong to that same company, and operations must never globally sync away roles from other companies.
