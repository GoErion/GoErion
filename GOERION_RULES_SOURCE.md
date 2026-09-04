# GoErion ERP Project Rules Source

This document is the approved source for GoErion-specific Laravel Boost project rules. It must be merged into appropriately scoped files under `.ai/rules` by Laravel Boost's `record-rule` tool. It must not replace or overwrite Boost's generated `AGENTS.md`, and it must not be copied into one global rule.

## 1. Purpose

This document defines the mandatory GoErion-specific rules for any coding agent working on GoErion ERP.

GoErion is a multi-company enterprise resource planning system focused on wholesale and hardware businesses. It is being built as a maintainable Laravel domain-organized monolith. Correct business behavior, company isolation, data integrity, security, and understandable code are more important than producing code quickly.

The human developer is the product owner and final architectural authority. Agents implement approved requirements, identify risks, test their work, and explain important decisions. Agents must not silently invent business rules.

## Import and Rule Ownership

- Read the existing `.ai/rules/index.md` and every applicable rule before importing this document.
- Deduplicate this guidance against existing project rules.
- Merge related guidance into existing rules when possible.
- Create focused domain rules when a section applies only to Product, Sales, Purchasing, Inventory, Payments, or Accounting code.
- Keep repository and destructive-action safeguards global.
- Use the actual repository structure to choose path globs.
- Use Laravel Boost's `record-rule` tool so `.ai/rules/index.md` remains authoritative.
- Do not manually edit Boost-generated guideline sections in `AGENTS.md`.
- Do not modify application code while importing these rules.

## 2. Instruction Priority

When working in this repository, follow instructions in this order:

1. The developer's current request.
2. The root `AGENTS.md` generated for the active coding agent.
3. Applicable GoErion rules indexed by `.ai/rules/index.md`.
4. More specific `AGENTS.md` files in subdirectories, if any.
5. Existing GoErion code and tests.
6. Established Laravel conventions.

If instructions conflict, stop and describe the conflict before editing code.

Existing code is not automatically correct, but it is the source of truth for current names, namespaces, table structures, enums, and workflows. Inspect it before proposing changes. Do not rename existing concepts merely to match personal preferences.

## 3. Current Technical Baseline

- PHP 8.5.
- Laravel 13.x.
- A domain-organized modular monolith, not microservices.
- Eloquent ORM and Laravel relationships.
- MySQL is the primary application database unless repository configuration says otherwise.
- Blade with Tailwind CSS is GoErion's current production frontend, not temporary test markup.
- Build complete, accessible, responsive ERP interfaces that follow the existing project styling.
- Alpine.js may be used sparingly for interface behavior where it is already configured.
- UUIDs are used for public route identifiers on exposed entities.
- Numeric primary keys and numeric foreign keys are used internally.
- PHPUnit or Pest must follow the test framework already present in the repository.

Do not downgrade or replace the established stack. Do not introduce Livewire, Inertia, Vue, React, an API layer, a repository pattern, event sourcing, microservices, or another architectural layer unless the developer explicitly approves it.

## 4. Core Engineering Principles

All implementation work must uphold these principles:

1. Every tenant-owned record is isolated by company.
2. Business invariants are enforced in the application and, where possible, by database constraints.
3. Controllers coordinate HTTP concerns; they do not contain substantial business logic.
4. Form Requests validate and authorize input.
5. Action classes execute application use cases.
6. Services implement genuinely shared domain processes.
7. Multi-write business operations are atomic.
8. Posted financial and stock history must remain auditable.
9. Money is never calculated with binary floating-point values.
10. The simplest design that safely satisfies the confirmed requirement is preferred.
11. Code must remain understandable to the human developer.
12. Automated tests must protect important behavior and company boundaries.

Do not create abstractions merely to make the project look more "enterprise." Introduce an interface, DTO, event, listener, job, service, or repository only when it solves a real current problem.

## 5. Product and Domain Boundaries

GoErion is expected to contain the following business domains. Preserve clear ownership and avoid mixing responsibilities between them.

### 5.1 Identity and Access

Responsibilities include:

- Users and authentication.
- User membership in companies.
- Roles and permissions.
- Invitations and membership status when introduced.
- Authorization for company and domain operations.

Authentication answers who the user is. Company membership and authorization answer what the user may do. Never treat authentication alone as permission to access a company record.

### 5.2 Company

Responsibilities include:

- Company profile.
- Company settings.
- Branches.
- Warehouses.
- Warehouse locations.
- Number sequences.
- Company membership and company-level configuration.

Only the company `company_name` is assumed to be required at company creation. Other company attributes remain nullable unless the current schema or an explicitly approved requirement says otherwise.

Settings are key/value configuration owned by a company. Do not assume that a collection of settings has properties such as `currency`; resolve settings by key or use an approved settings access layer.

### 5.3 Product Catalog

Responsibilities include:

- Categories.
- Units.
- Brands.
- Products.
- Product prices.
- Product barcodes.
- Product-to-supplier relationships.

Established relationships:

- A Product belongs to a Company.
- A Product belongs to a Category.
- A Product belongs to a Unit.
- A Product may belong to a Brand.
- A Product has many ProductPrice records.
- A Product has many ProductBarcode records.
- ProductSupplier represents the relationship between Product and Supplier and may contain `supplier_sku` and `cost`.

Established constraints and conventions:

- Product SKU generation may use `NumberSequenceService`.
- The product number sequence should be created lazily when the first generated product SKU is needed unless the developer changes this decision.
- ProductPrice is a separate entity.
- A product price is unique for the approved combination of product, price type, and currency.
- Barcode type is represented by a PHP enum where that enum already exists.
- ProductSupplier uses its approved composite identity and does not require a public UUID unless the developer explicitly changes that decision.

Do not merge price, barcode, supplier, or inventory responsibilities into the products table for convenience.

### 5.4 Customers and Receivables

Responsibilities include:

- Customer identity and contact information.
- Company-specific customer accounts.
- Credit limits.
- Payment terms.
- Customer balances and receivables.
- Customer statements and transaction history.

A credit limit, payment term, or customer balance must have explicitly defined behavior. Do not assume how credit approval, overdue invoices, deposits, or balance calculations work.

### 5.5 Suppliers and Payables

Responsibilities include:

- Supplier identity and contact information.
- Company-specific supplier accounts.
- Supplier product information.
- Supplier payment terms.
- Supplier balances and payables.
- Supplier statements and transaction history.

Do not assume that customer and supplier workflows are identical merely because some fields are similar.

### 5.6 Sales

Responsibilities may include:

- Quotations.
- Sales orders.
- Deliveries.
- Sales invoices.
- Returns and credit notes.
- Taxes, discounts, totals, and customer receivables.

Sales document transitions must be explicit. Do not invent status names, transition rules, reservation behavior, tax timing, invoice numbering, or posting behavior.

### 5.7 Purchasing

Responsibilities may include:

- Purchase requisitions when approved.
- Purchase orders.
- Goods receipts.
- Supplier bills.
- Purchase returns and debit notes.
- Taxes, discounts, landed costs, and supplier payables.

Ordering goods, receiving stock, and recognizing a payable are distinct business events. Do not collapse them into one operation unless the developer explicitly requests a simplified workflow.

### 5.8 Inventory

Responsibilities may include:

- Stock movements.
- Stock balances.
- Receipts and issues.
- Transfers between warehouses or locations.
- Adjustments.
- Reservations.
- Counts and reconciliations.
- Batch, serial, or expiry tracking if introduced.
- Inventory valuation.

Inventory history must be auditable. Prefer immutable stock movement records and derived or safely maintained balances. Never change a product's stock quantity directly from a controller.

Do not choose or assume any of the following without approval:

- Whether negative stock is allowed.
- FIFO, weighted-average, standard-cost, or another valuation method.
- When stock becomes reserved.
- When ownership or cost is recognized.
- How backdated movements are handled.
- How unit conversions and rounding work.

### 5.9 Payments

Responsibilities may include:

- Customer receipts.
- Supplier payments.
- Refunds.
- Payment methods and references.
- Allocation of payments to documents.
- Unallocated credits or deposits.
- Reversals.

Payment creation and payment allocation are separate concepts unless an approved workflow combines them. Never silently overwrite or delete a completed payment. Financial corrections should be reversible and auditable.

### 5.10 Accounting

Responsibilities may include:

- Chart of accounts.
- Fiscal periods.
- Journal entries and journal lines.
- Posting from operational modules.
- General ledger.
- Trial balance.
- Profit and loss.
- Balance sheet.
- Tax accounts and control accounts.

Accounting rules require explicit approval. A posted journal must balance: total debits must equal total credits. Posted entries should be immutable; correct them through an approved reversal or adjustment workflow.

Never invent account mappings, tax rules, opening balance behavior, retained earnings treatment, fiscal-year closing, foreign-exchange behavior, or posting dates.

### 5.11 Reporting and Audit

Reports read approved domain data; they do not become an alternative place for business logic. Material business operations should record sufficient authorship, timestamps, references, and status history to be audited where required.

## 6. Company and Tenant Isolation

Company isolation is a non-negotiable security boundary.

### 6.1 Required Behavior

For every tenant-owned operation:

- Confirm that the authenticated user belongs to or is authorized for the route company. Follow the established membership check and return `403` when the authenticated user is not authorized for that company.
- Load child records through the route company or apply an explicit `company_id` constraint.
- Verify that related records selected by the request belong to the same company.
- Scope unique validation rules to the company when uniqueness is tenant-specific.
- Scope `exists` validation rules or validate related models through company relationships.
- Add tests proving that a user from one company cannot view, create against, update, or delete another company's data.
- After company access is authorized, return `404` when a nested resource does not belong to that route company.

Example intent:

```php
$product = $company->products()->whereUuid($productUuid)->firstOrFail();
```

Do not use an unscoped lookup followed by an optimistic assumption that route UUIDs are sufficient.

### 6.2 Ownership Assignment

- Never trust a client-provided `company_id`.
- Derive company ownership from the authorized route company or current company context.
- Do not mass-assign ownership fields from request input.
- Do not move a record between companies through an ordinary update action.
- A related category, unit, brand, warehouse, location, customer, supplier, product, document, or account must belong to the same company unless it is explicitly modeled as global shared data.

### 6.3 Route Model Binding

- Publicly exposed models should use UUID route keys through the project's approved `HasUuid` trait and route-key convention.
- Database relationships still use internal numeric IDs.
- Nested binding must not replace explicit tenant authorization unless the implementation demonstrably enforces the same boundary.
- Never expose internal numeric IDs in public URLs merely for convenience.

## 7. Database and Data Modeling Rules

### 7.1 Identifiers

- Use the conventional numeric primary key for internal database identity unless an approved existing table differs.
- Use a unique UUID column on publicly exposed entities.
- Use numeric foreign keys that reference internal primary keys.
- Add foreign-key constraints with deliberate delete behavior.
- Do not add `HasUuid` to pure pivot or composite relationship records unless they need a public identity.

### 7.2 Constraints and Indexes

- Express domain invariants with unique constraints where practical.
- Tenant-specific uniqueness normally includes `company_id`.
- Add indexes for frequently filtered foreign keys, statuses, dates, document numbers, SKUs, and other demonstrated query paths.
- Do not create redundant indexes already covered by a unique or composite index.
- Choose `cascadeOnDelete`, `restrictOnDelete`, or `nullOnDelete` based on the business lifecycle, not habit.
- Financial, stock, or historical data should generally not disappear because a parent record is deleted.

### 7.3 Nullability and Defaults

- Required database fields must correspond to genuine business invariants.
- Do not make optional fields required simply to simplify a form.
- Do not add misleading defaults that make missing business input look valid.
- Preserve the established rule that only company `company_name` is required at initial company creation unless explicitly changed.

### 7.4 Money, Quantities, and Currency

- Never store or calculate money using PHP `float` or database floating-point types.
- Use an approved decimal strategy or integer minor units consistently with the existing code.
- Specify precision and scale deliberately.
- Avoid implicit currency conversion.
- Every monetary amount must have a clear currency context.
- Currency codes should follow the established ISO-style convention in the project.
- Quantity precision may differ from money precision; do not assume all products use whole units.
- Define rounding points and rounding modes for each workflow before implementing complex totals.

### 7.5 Dates and Time

- Use framework date casts and immutable dates where appropriate.
- Distinguish operational dates, posting dates, due dates, and timestamps.
- Store timestamps according to the application's approved timezone strategy.
- Do not calculate due dates until payment-term behavior is confirmed.

### 7.6 Enums and Statuses

- Use backed PHP enums for stable, closed business concepts when consistent with existing code.
- Reuse existing enums rather than repeating string literals.
- Do not add a status value without defining its allowed transitions and effects.
- Do not allow arbitrary client-provided state transitions.

### 7.7 Migrations

- Inspect existing migrations and models before writing a migration.
- Prefer a new forward migration for a schema already used by others or applied outside disposable development databases.
- Do not edit historical migrations without confirming that the project is intentionally resettable.
- Every migration must have a safe and coherent `down()` method when reversal is practical.
- Never run `migrate:fresh`, `db:wipe`, destructive SQL, or equivalent commands without explicit permission.
- Do not seed or modify production-like data unless requested.

## 8. Laravel Code Organization

Follow the repository's existing domain namespaces and directory layout. Do not reorganize the whole application as part of a feature.

### 8.1 Models

Models define persistence behavior, casts, relationships, route keys, and small model-level invariants.

- Declare relationships with accurate Laravel return types.
- Use the project's approved fillable or guarded convention.
- Cast enums, booleans, dates, JSON, and decimal-like values deliberately.
- Avoid large workflow methods on models.
- Avoid hidden database queries in accessors.
- Do not add global scopes for tenant isolation without explicit architectural approval; they can hide queries and complicate administrative operations.
- Eager-load known relationships to avoid N+1 queries.

### 8.2 Form Requests

Form Requests own HTTP validation and request-level authorization.

- Use `authorize()` to enforce the applicable permission or policy.
- Use `rules()` for shape and referential validation.
- Use validated input only.
- Do not pass `$request->all()` into an action or model.
- Normalize input in `prepareForValidation()` only when the transformation is unambiguous.
- Use company-scoped `Rule::unique()` and `Rule::exists()` logic for tenant data.
- Ignore the current record correctly during tenant-scoped update uniqueness checks.

### 8.3 Actions

An Action represents one application use case, such as creating a product, receiving goods, or posting an invoice.

- Prefer a clear method such as `handle(...)` with explicit parameter and return types.
- Accept authorized models and validated values rather than raw Request objects.
- Own the transaction when the action coordinates multiple writes.
- Keep HTTP redirects, session messages, and view responses out of actions.
- Return the created/updated domain result or an explicit result object when genuinely needed.
- Do not make Actions static merely to shorten calls.

### 8.4 Services

Use Services for reusable domain capabilities used by multiple actions or modules, such as number sequencing, tax calculation after rules are defined, inventory costing, or posting.

- Do not turn every class into a service.
- Do not use a generic `Helper` class for domain behavior.
- Keep services independent of HTTP concerns.
- Document important concurrency and transaction expectations.

### 8.5 Controllers

Controllers should:

1. Receive route-bound models and dependencies.
2. Authorize access.
3. Delegate validated work to an Action or focused service.
4. Return a response, redirect, or view.

Controllers must not contain complex totals, stock mutations, accounting entries, number generation, or multi-step persistence logic.

### 8.6 Policies and Authorization

- Use policies, gates, or the established authorization layer consistently.
- Check both company membership and the permission required for the action.
- UI visibility is not authorization.
- A hidden button does not secure its endpoint.
- Background jobs and console commands must enforce equivalent boundaries through trusted inputs and explicit scoping.

### 8.7 Routes

- Use clear names and follow the established company-nested route convention.
- Prefer resourceful route semantics where they fit.
- Match form methods to route methods.
- Keep route parameters and controller signatures consistent.
- Use UUID route binding for exposed entities.
- Do not create duplicate routes with competing names or behaviors.

### 8.8 Blade Views

- Keep Blade responsible for presentation, not business operations.
- Build production-quality server-rendered Blade forms while keeping business logic outside views.
- Treat Blade and Tailwind CSS as the current production ERP frontend.
- Use named routes rather than hard-coded application URLs.
- Include CSRF protection and the correct method spoofing for non-POST operations.
- Display validation errors and preserve old input.
- Escape untrusted output by default.
- Do not query models from Blade views.
- Pass every required component prop explicitly.
- Do not assume a variable such as `$company` or `$companies` is automatically available to a component.
- Build accessible labels, focus states, semantic controls, and understandable error messages.
- Follow the existing `x-app`, `x-dashboard`, `x-home`, and `x-sidebar` component structure.
- Pages using `x-home` must explicitly receive and pass `$company` and `$companies` when those props are required.
- Group long ERP forms into clear business sections.
- Reuse a form partial for create and edit screens when they genuinely share fields.
- Index pages should use readable tables, status badges, empty states, search or filters where required, and Laravel pagination.
- Search and filter forms should use `GET` requests and preserve active filters.
- Relationship options must be loaded through the authorized company before being passed to the view.

## 9. PHP Coding Standards

- Start project PHP files with `declare(strict_types=1);` when consistent with the existing file type.
- Follow PSR-12 and the repository formatter.
- Use explicit parameter and return types.
- Use `final` for concrete application classes when extension is not intended and consistent with the codebase.
- Use `readonly` only when the class state genuinely supports it.
- Prefer constructor dependency injection.
- Prefer early returns to deeply nested conditionals.
- Use descriptive domain names instead of abbreviations.
- Avoid dynamic properties and magic behavior that obscures data flow.
- Do not suppress exceptions without handling or reporting them appropriately.
- Do not catch `Throwable` merely to return a generic success response.
- Avoid comments that only repeat the code; explain non-obvious business reasons.
- Remove debugging calls such as `dd()`, `dump()`, and temporary logging before completion.

Use Laravel features when they improve clarity, but do not use a feature solely because it is new.

## 10. Transactions, Concurrency, and Number Sequences

### 10.1 Transactions

Use `DB::transaction()` for operations that must succeed or fail as one unit, including:

- Creating a document and its lines.
- Receiving goods and recording inventory movements.
- Posting a financial document and its journal.
- Creating a payment and its allocations.
- Transfers that create matched outbound and inbound movements.
- Any operation that updates a balance plus its supporting history.

Keep external network calls and slow non-database work outside database transactions where possible. Dispatch side effects after commit when sending them before commit could create inconsistency.

### 10.2 Concurrency

- Use row locks for contested sequential state or balance updates where appropriate.
- Design inventory, document posting, and payments for duplicate submissions.
- Use database uniqueness as the final defense against duplicate numbers.
- Prefer idempotent processing for queued or retried operations.
- Do not solve concurrency with a preliminary `exists()` check alone.

### 10.3 Number Sequences

The established `NumberSequenceService` receives a NumberSequence model and increments it transactionally using a database lock.

- Reuse the approved service instead of generating random document numbers.
- Preserve its configured prefix, year, padding, and formatting behavior.
- Do not call sequence generation outside the transaction that persists the associated business document when a failure could waste or duplicate a number, unless gaps are explicitly acceptable.
- Sequence identity must be company-specific and document-type-specific.
- Product SKU sequences should be initialized lazily according to the established decision.
- Do not reset sequences annually unless the approved sequence design explicitly requires it.
- The established six-digit document format is `PREFIX-YEAR-000001`, such as `INV-2026-000001`; preserve the approved prefix and padding behavior.

## 11. Business Calculations

Calculations must be centralized and testable.

- Do not duplicate subtotal, discount, tax, total, balance, cost, or currency logic between controllers and views.
- Persist calculation inputs and results required for auditability.
- Define whether discounts are line-level or document-level before implementation.
- Define whether prices are tax-inclusive or tax-exclusive before implementation.
- Define tax rounding and discount ordering before implementation.
- Never infer Kenyan tax compliance rules from memory; require a confirmed business requirement and current authoritative guidance.
- Historical documents should not silently change when current product prices, names, tax rates, or account mappings change. Snapshot approved document facts where required.

## 12. Events, Jobs, Notifications, and Integrations

- Use events for meaningful completed domain facts, not as a way to hide the primary workflow.
- Use queued jobs for retryable or slow work.
- A job must carry stable identifiers and reload explicitly scoped models.
- Jobs must be safe against duplicate execution where practical.
- Dispatch jobs and notifications after commit when they depend on committed data.
- Do not put secrets in jobs, logs, source control, URLs, or exception messages.
- Wrap external integrations behind a focused boundary only when an integration actually exists.
- Validate webhook signatures and implement replay protection when webhooks are introduced.

## 13. API Rules

GoErion does not require an API merely because it is an ERP.

- Do not create duplicate API endpoints for Blade functionality unless there is a confirmed consumer.
- If an API is requested, use Laravel API Resources, explicit authentication, authorization, tenant scoping, validation, pagination, and consistent error responses.
- Never expose internal database IDs, sensitive settings, secrets, password data, or unrestricted model serialization.
- Version a public API deliberately rather than prematurely.

## 14. Security Rules

- Treat all external input as untrusted.
- Protect every state-changing web request with authentication, authorization, validation, and CSRF protection where applicable.
- Prevent mass-assignment of ownership, balances, status, approval, posting, or audit fields.
- Avoid raw SQL; parameterize every unavoidable raw expression.
- Escape untrusted Blade output.
- Validate uploaded file type, size, storage visibility, and access.
- Never commit `.env`, credentials, application keys, tokens, private keys, production exports, or customer data.
- Never reveal sensitive values in logs or agent output.
- Do not disable framework security controls to make a test pass.
- Rate-limit sensitive public operations where appropriate.
- Use Laravel hashing and encryption facilities; never invent cryptography.
- Consider insecure direct object reference risks on every UUID route. UUIDs reduce guessability but do not replace authorization.

## 15. Auditability and Deletion

- Use database transactions and explicit references to connect derived records to their source document.
- Record the acting user where the business process requires authorship.
- Preserve created, posted, reversed, and voided timestamps where relevant.
- Do not hard-delete posted invoices, payments, journals, or stock movements.
- Do not add soft deletes indiscriminately. Use them only when recovery and query behavior are deliberately handled.
- Prefer status changes, reversal documents, or adjustments for auditable business history.
- Never rewrite a completed transaction merely to make the current total look correct.

## 16. Performance Rules

- Correctness and isolation come first, but avoid known inefficiencies.
- Prevent N+1 queries with deliberate eager loading.
- Select only necessary data for large reports and listings.
- Paginate growing lists.
- Use database aggregation rather than loading entire datasets into PHP when appropriate.
- Add indexes based on actual filters and joins.
- Use queues for heavy work that does not need to complete in the request.
- Do not introduce caching until the invalidation and tenant-key strategy are clear.
- Every cache key for tenant-owned data must include the company context.
- Do not optimize without measuring or identifying a credible bottleneck.

## 17. Testing Requirements

Every material feature must have tests at the appropriate level.

### 17.1 Minimum Feature Coverage

Test:

- The authorized successful path.
- Validation failures.
- Unauthenticated access.
- Unauthorized access.
- Cross-company access and cross-company relationship injection.
- Company-scoped uniqueness.
- Database changes and important relationships.
- Important status transitions.
- Transaction rollback when a coordinated operation fails.

### 17.2 Domain-Specific Coverage

Where applicable, test:

- Exact money and rounding behavior.
- Document totals.
- Number-sequence uniqueness and formatting.
- Duplicate request or retry behavior.
- Inventory movement direction and quantity.
- Transfers balancing between source and destination.
- Prevention or handling of negative stock according to the approved rule.
- Payment allocation limits and remaining balances.
- Debit/credit equality for journal entries.
- Posted-record immutability and reversal behavior.
- Date, due-date, currency, and fiscal-period boundaries.

### 17.3 Test Quality

- Follow the project's existing test style.
- Use factories and named states to make intent clear.
- Keep tests independent and deterministic.
- Do not depend on execution order.
- Do not weaken assertions to make failing code pass.
- Do not delete a valid failing test without explaining why its requirement changed.
- Prefer behavior-focused tests over tests of framework internals.

Run the smallest relevant test set during development, then the broader affected suite before declaring completion.

## 18. Agent Working Protocol

### 18.1 Before Editing

For every task, the agent must:

1. Read the root `AGENTS.md`, `.ai/rules/index.md`, and every applicable project rule.
2. Inspect the current Git status without discarding existing changes.
3. Locate the relevant routes, migrations, models, requests, actions, controllers, policies, views, factories, and tests.
4. Trace the current data flow and relationships.
5. Identify tenant and authorization boundaries.
6. State any assumptions that could affect business behavior.
7. Propose a focused implementation plan for non-trivial work.

If the task is ambiguous in a way that changes money, stock, tax, accounting, authorization, data ownership, or status transitions, ask the developer before implementation.

### 18.2 While Editing

- Work on one coherent feature or vertical slice at a time.
- Make the smallest complete change that satisfies the request.
- Preserve unrelated developer changes in a dirty worktree.
- Do not edit generated or vendor files.
- Do not perform broad formatting unrelated to the task.
- Do not rename, move, or redesign unrelated code.
- Keep public contracts stable unless the requested change requires otherwise.
- Update tests alongside behavior.
- Explain unexpected repository conditions before taking risky action.

### 18.3 Verification

After editing:

1. Run the repository formatter on changed files where available.
2. Run focused relevant tests.
3. Run static analysis if configured and relevant.
4. Check routes or migrations when they were changed.
5. Review the final diff for accidental changes, debugging code, secrets, and missing tenant scoping.
6. Confirm that no unrequested package or configuration change was introduced.

Do not claim that code works unless the relevant verification completed successfully. If verification cannot run, state exactly why and what remains unverified.

### 18.4 Completion Report

At completion, report:

- The behavior implemented.
- Important business and architectural decisions.
- Files changed and why.
- Tests or checks run and their results.
- Any assumptions, risks, migrations, or follow-up decisions.
- A short explanation of unfamiliar or non-obvious Laravel mechanisms used.

The developer wants to learn the system, so explanations must focus on why the code is structured as it is, not merely list syntax.

## 19. Git and Workspace Safety

- Inspect `git status` before and after work.
- Treat all pre-existing modifications as developer-owned.
- Never discard, overwrite, stash, amend, or commit developer changes without permission.
- Never use `git reset --hard`, destructive checkout commands, `git clean`, force-push, or history rewriting without explicit permission.
- Do not create commits or branches unless asked.
- Keep changes focused enough for human diff review.
- Never modify `.env` unless explicitly requested, and never expose its contents.
- Use `.env.example` only for non-secret documented configuration keys.
- Do not modify `composer.lock` or package lockfiles unless an approved dependency operation requires it.

## 20. Prohibited Autonomous Actions

Without explicit permission, an agent must not:

- Run destructive database or filesystem commands.
- Run `migrate:fresh`, `migrate:reset`, `db:wipe`, or production migrations.
- Delete business records or user files.
- Install, remove, or upgrade Composer, npm, system, or Docker packages.
- Change framework, PHP, database, or frontend versions.
- Modify authentication or authorization architecture.
- Add a package to solve behavior Laravel already supports adequately.
- Contact external services, send messages, send emails, or trigger real payments.
- Use real customer, supplier, financial, or production data in tests.
- Generate or rotate secrets and credentials.
- Deploy, push to Git remotes, publish packages, or alter hosting.
- Rewrite Git history.
- Silence tests, validation, exceptions, or security checks to produce a passing result.

## 21. Decisions Agents Must Not Invent

Stop and ask the developer when the task depends on an unconfirmed decision about:

- Tax rates, tax inclusion, withholding, exemptions, or jurisdictional compliance.
- Currency conversion and exchange gains or losses.
- Inventory valuation and costing.
- Negative stock.
- Serial, batch, or expiry behavior.
- Unit conversion and precision.
- Credit limits, credit approval, overdue blocking, or payment terms.
- Discounts, promotions, commissions, or price precedence.
- Document lifecycle and allowed status transitions.
- Document numbering gaps or resets.
- Approvals and separation of duties.
- Returns, refunds, reversals, voiding, or deletion.
- Account mappings and journal posting rules.
- Fiscal periods, period locking, and year-end closing.
- Branch-versus-company ownership of transactions.
- Warehouse reservation and fulfillment behavior.
- Backdating and historical recalculation.
- Personal data retention or legal compliance.

When asking, present the concrete alternatives and describe how each would affect the data model or workflow.

## 22. Definition of Done

A GoErion feature is complete only when:

- The confirmed business requirement is implemented.
- Company isolation is enforced at every entry point.
- Authorization and validation are present.
- Database constraints protect important invariants.
- Multi-write operations are transactional.
- Routes, controllers, actions, models, and views have appropriate responsibilities.
- Relevant tests cover success, failure, and cross-company behavior.
- Relevant tests and formatting pass.
- No unrelated changes, secrets, debugging statements, or unsafe commands remain.
- The implementation is understandable and its important decisions are explained.

## 23. Recommended Feature Sequence

Unless the developer selects a different priority, prefer completing stable foundations before dependent workflows:

1. Authentication, companies, membership, roles, and permissions.
2. Company settings, branches, warehouses, locations, and number sequences.
3. Categories, units, brands, products, prices, barcodes, and supplier products.
4. Customers and suppliers.
5. Inventory movement foundation and opening stock.
6. Purchasing and goods receipt.
7. Sales, delivery, and invoicing.
8. Customer receipts, supplier payments, and allocation.
9. Accounting foundations and operational posting.
10. Reporting, auditing, imports, integrations, and optimization.

This sequence is guidance, not permission to implement unrequested modules.

## 24. Task Prompt Contract

When given a broad request, convert it into a small verifiable vertical slice. A useful task response should make clear:

- Confirmed behavior.
- Files and domains involved.
- Company and authorization boundary.
- Database invariants.
- Transaction boundary.
- Tests required.
- Open business decisions.

For inspection-only requests, do not edit files. For review or diagnosis requests, provide evidence and recommendations but do not implement changes unless asked.

---

These rules exist to help agents accelerate GoErion development without taking ownership of its architecture away from the developer. When speed conflicts with correctness, auditability, company isolation, or understanding, choose the safer and clearer implementation and explain the tradeoff. After importing this source into `.ai/rules`, verify every created or updated rule is listed in `.ai/rules/index.md`.