---
paths:
  - 'tests/**'
---

# Tests

## Tenant Isolation — Tests
Use Pest and follow the existing test style. Material features cover the authorized path, validation, unauthenticated and unauthorized access, database changes, important relationships, and transaction rollback when coordinated work fails.
Tenant-owned features prove 403 for unauthorized route-company access and 404 for another company's nested resource, plus cross-company create/update/delete and manipulated relationship IDs.

## Test Quality and Verification
Follow the repository's Pest style with independent, deterministic, behavior-focused tests and factories or named states where useful. Do not weaken assertions or delete valid failing tests; run the smallest relevant set during development and the broader affected suite before completion.

## Supplier test isolation fails closed
Supplier test infrastructure must reject MySQL before connection resolution unless the exact disposable Supplier database and schema-exclusive credentials have been independently certified. Never enumerate or clean tables across schemas, accept qualified cleanup targets, or fall back to development database credentials.

## Product tests remain isolated by default
Product tests use explicit testing configuration and SQLite :memory: until separately approved MySQL isolation is certified. Never fall back to project environment credentials or persistent databases. Preserve explicit MySQL-only checks and keep workers disabled before application bootstrap.
