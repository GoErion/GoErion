---
paths:
  - 'routes/**'
  - routes/api.php
  - routes/console.php
---

# Routes

## Tenant Isolation — Routes
Use clear named company-nested routes, resourceful semantics where they fit, matching form and route methods, and controller signatures consistent with route parameters.
Exposed entities use UUID route binding. Nested binding does not replace company authorization or scoped child loading, and public URLs must not expose internal numeric IDs merely for convenience.

## Deliberate Public API
Create API routes only for a confirmed consumer and version a public API deliberately. Every endpoint requires explicit authentication, authorization, validation, and tenant scoping.

## Tenant Isolation — Console Routes
Console routes receive or resolve an explicit company context, verify trusted authorization assumptions, and scope every tenant-owned query to that company.
