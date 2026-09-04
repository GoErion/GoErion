---
paths:
  - 'app/Http/**'
---

# Http

## Tenant Isolation — HTTP
Authenticate, authorize, and validate every tenant-owned request. Return 403 when the authenticated user is not authorized for the route company; after company authorization succeeds, return 404 when a nested resource belongs to another company.
UUID route binding does not replace authorization or explicit company-scoped child loading.

## Application Security Boundaries
Treat external input as untrusted and protect state changes with authentication, authorization, validation, and CSRF where applicable. Validate uploads, rate-limit sensitive public operations where appropriate, use Laravel hashing and encryption, parameterize unavoidable raw SQL, and never weaken security to pass a test.
