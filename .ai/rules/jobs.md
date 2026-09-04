---
paths:
  - 'app/Jobs/**'
---

# Jobs

## Scoped Retry-Safe Jobs
Jobs carry stable identifiers, reload explicitly company-scoped models, and are safe against duplicate execution where practical. Enforce equivalent authorization boundaries through trusted inputs and dispatch after commit when required.
