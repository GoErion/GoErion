---
paths:
  - 'app/Models/Company/**'
---

# Company

## Company Creation and Settings
At initial company creation, only company_name is assumed required; other fields remain nullable unless the schema or an approved requirement says otherwise. Company settings are key/value data: resolve them by key or an approved access layer, not collection properties.
