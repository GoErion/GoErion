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
