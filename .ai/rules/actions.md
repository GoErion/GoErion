---
paths:
  - 'app/Actions/**'
---

# Actions

## Tenant Isolation — Actions
Accept an authorized Company and validated values, never a client-owned company_id or raw Request. Derive ownership from the authorized company and reject related records outside it.
An ordinary update action must not move a record between companies.

## Action Responsibilities
An Action represents one application use case. Use an explicit instance method with typed inputs and result, accept authorized models and validated values, keep HTTP responses out, and own transactions that coordinate multiple writes.

## Transactions and Concurrency
Use database transactions when coordinated writes must succeed or fail together. Use locks and uniqueness for contested sequences or balances, design retried operations for duplicate submission, and keep external calls outside transactions where possible.

## Calculations — Actions
Actions coordinate the use case and its transaction. They may call focused calculation services, but must not duplicate shared subtotal, discount, tax, total, balance, cost, quantity, or currency formulas.

## Auditability — Actions
Actions connect derived records to their source, record authorship and lifecycle timestamps where required, and coordinate approved reversals or adjustments. They must not silently rewrite or hard-delete completed financial or stock activity.

## Tenant-Aware Caching
Every cache entry containing tenant-owned data includes company context in its key. Do not add caching until invalidation and tenant isolation are clear.

## Serialize account lifecycle before company memberships
Account deletion and company or membership creation for an existing user must re-resolve and lock the numeric User row inside the transaction before enumerating memberships or creating a company/membership. Account deletion then locks Company rows in ascending numeric ID order. Never add a User-row lock after a Company lock in access Actions; retain transaction retries for database deadlocks.
