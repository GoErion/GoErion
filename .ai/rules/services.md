---
paths:
  - 'app/Services/**'
---

# Services

## Tenant Isolation — Services
Reusable domain services operating on tenant data require explicit trusted company context and must keep every lookup and related record within that company.

## Service Responsibilities
Use services only for reusable domain capabilities shared by actions or modules. Do not create generic Helper classes or turn every class into a service; keep services independent of HTTP concerns.

## Calculations — Services
Services own reusable calculation and posting algorithms used across actions or modules. Keep formulas centralized and testable, never use binary floating point for money, and keep all HTTP behavior out of services.

## Auditability — Services
Reusable posting and history services preserve source references, audit inputs and results, authorship, and lifecycle timestamps. They implement approved reversal or adjustment behavior without rewriting completed transactions.

## Query Performance — Services
Select only necessary report data, prefer database aggregation where appropriate, and use queues for heavy work that need not finish during a request. Do not cache without a clear invalidation strategy, and include company context in every tenant-data cache key.
