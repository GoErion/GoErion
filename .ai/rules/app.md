---
paths:
  - 'app/**'
  - 'app/**/*.php'
---

# App

## GoErion Domain Monolith Architecture
GoErion is a Laravel 13/PHP 8.5 multi-company ERP organized as a domain monolith. Preserve its established domain namespaces and directories; do not reorganize unrelated code.
Use Eloquent relationships, Form Requests for validation and request authorization, Actions for use cases, Services for genuinely reusable domain processes, and thin controllers. Wrap coordinated writes in a transaction.
Do not introduce repositories, event sourcing, microservices, an API layer, or other architectural layers without approval.

## PHP Application Standards
Use strict types when consistent with the file type, explicit parameter and return types, descriptive domain names, constructor injection, and the repository formatter. Prefer final concrete classes when extension is not intended; remove debugging calls and do not suppress failures.

## Domain Delivery Sequence
Do not implement unrequested modules. When prioritization is not otherwise specified, preserve the dependency order from identity/company foundations through Product, customers/suppliers, Inventory, Purchasing, Sales, Payments, Accounting, then reporting and integrations.
