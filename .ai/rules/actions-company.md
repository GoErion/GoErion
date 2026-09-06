---
paths:
  - 'app/Actions/Company/**'
---

# Actions Company

## Serialize company ownership changes
All owner promotion, demotion, removal, and voluntary departure actions must lock the route Company row with lockForUpdate before resolving the protected owner role or counting owners. Generic member-role assignment cannot add or remove the owner role, and generic member removal rejects owners.

## Revalidate access authority under the company lock
Privileged role, member, and ownership mutations lock the route Company row before rechecking current membership and the existing policy ability with database-backed permission queries. Do not trust loaded role relationships or pre-lock Form Request authorization. Rejection must leave access and history unchanged; self-service departure rechecks membership and last-owner state under the same Company lock.
