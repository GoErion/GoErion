---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Tenant Isolation — Controllers
Authorize the route company, load nested resources through it, delegate validated writes to an Action or focused service, and return the response. Keep totals, stock mutations, accounting entries, number generation, and multi-step persistence out of controllers.
Provide views with all required company-scoped relationship options, computed results, permissions, and component props. Do not move Blade markup responsibilities into controllers.

## Measured Listing Performance
Prevent known N+1 queries, select only necessary listing data, and paginate growing result sets. Keep heavy reporting work out of synchronous requests where practical.
