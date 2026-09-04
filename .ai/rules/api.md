---
paths:
  - 'app/Http/Controllers/Api/**'
---

# Api

## Confirmed API Consumers
Do not duplicate Blade workflows with an API without a confirmed consumer. Approved APIs use resources, explicit authentication and authorization, tenant scoping, validation, pagination, consistent errors, and no internal IDs or unrestricted serialization.

## API Sensitive Data
APIs must never expose sensitive company settings, credentials, secrets, password data, or unrestricted model serialization.
