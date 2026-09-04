---
paths:
  - 'app/Models/**'
---

# Models

## Tenant Isolation — Models
Tenant-owned models expose accurate company and child relationships. Publicly exposed entities use the approved HasUuid route-key convention while database relationships use numeric IDs.
Do not add a global tenant scope without explicit architectural approval, and do not give pure pivot or composite relationship records a public UUID unless they need a public identity.

## Model Responsibilities
Models define persistence, casts, accurate typed relationships, route keys, and small invariants. Avoid workflow-heavy methods and hidden accessor queries; cast enums, booleans, dates, JSON, and decimal-like values deliberately, and eager-load known relationships.

## Query Performance — Models
Prevent known N+1 queries through deliberate eager loading. Add indexes for actual query paths and do not optimize without measurement or a credible bottleneck.

## Dates Enums and Statuses
Cast dates and stable closed concepts deliberately and reuse existing backed enums. Distinguish operational, posting, due, and timestamp dates; do not add statuses or client-driven transitions without defining their allowed transitions and effects.

## Dates and Payment Terms
Follow the application's approved timezone strategy and distinguish operational, posting, due-date, and timestamp values. Do not calculate due dates until payment-term behavior is confirmed.

## Deliberate Soft Deletes
Do not add soft deletes indiscriminately. Add them only when recovery, uniqueness, relationships, and default query behavior have been deliberately designed.
