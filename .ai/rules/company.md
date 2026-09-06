---
paths:
  - 'app/Models/Company/**'
  - app/Models/Company/CompanyAccessEvent.php
---

# Company

## Company Creation and Settings
At initial company creation, only company_name is assumed required; other fields remain nullable unless the schema or an approved requirement says otherwise. Company settings are key/value data: resolve them by key or an approved access layer, not collection properties.

## Append-only company access history
Company access events are append-only and are created inside the same transaction as the access change. There are no update or delete workflows; nullable actor, subject, role, and invitation references preserve history when related records disappear.
