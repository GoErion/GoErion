---
paths:
  - 'app/Actions/Product/**'
---

# Product

## Authorize Product mutations at both entry points
Product mutations lock Company before rechecking fresh database-backed authority and resolving and locking the Company-owned target. Existing-Product updates require products.view and products.update; archive/restore require products.view and products.archive. Classification management requires products.classifications.manage alone. Never acquire a User lock after Company. Form Request authorization does not replace Action authorization.

## Product SKU Sequences
Lock the Company before lazily creating its Product sequence. Initialize new sequences with SKU while preserving existing nonempty configured prefixes. Assign Company and immutable SKU explicitly on the server, outside ordinary mass assignment. Allocate through NumberSequenceService in the same transaction as Product creation so failures roll back advancement. Counters continue across years; year formatting does not reset them.

## Product profile and lifecycle boundaries
Product profile updates accept only name, description, category_id, brand_id and unit_id. Reject protected identity, type, tracking, lifecycle and child payloads. Archived Products remain editable without restoration. Preserve current archived classification references, reject newly selected archived classifications, and change is_active only through archive/restore Actions.

## Preserve Category hierarchy during lifecycle changes
Validate Company-scoped Category graphs under the Company lock using bounded cycle and depth checks, including descendants affected by moves. Limit depth to three. Reject archival with active descendants without cascading. Restore children only beneath active parents; roots may restore independently. Form edits must never silently change parentage. Only the freshly stored current parent may retain the archived-parent exception; creation and newly selected parents require active, same-Company records.
