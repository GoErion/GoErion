---
paths:
  - '**'
---

# General

## Workflow and Repository Safety
Follow the developer's request, the root AGENTS.md, and applicable indexed project rules. Inspect existing code and Git status before editing, preserve developer-owned changes, and keep work to the smallest complete vertical slice.
Never run destructive database, filesystem, or Git commands; change dependencies; contact external services; deploy; expose secrets; delete valid tests; or modify production-like data without explicit permission. Do not replace or overwrite AGENTS.md.
Run focused tests and the repository formatter after changes, review the final diff, and report behavior, important decisions, changed files, verification results, assumptions, and remaining risks. Do not claim unverified behavior works.

## Confirmed Requirements and Focused Work
The developer is the final authority for architecture and business behavior. Do not invent rules for money, tax, stock, credit, statuses, numbering, approvals, reversals, accounting, retention, or ownership; surface concrete alternatives and work in the smallest verifiable vertical slice.

## Environment and Lockfile Safety
Never edit or expose .env without explicit permission. Add only non-secret documented keys to .env.example, and do not modify composer.lock or package lockfiles unless an approved dependency operation requires it.

## Pre-Edit and Completion Protocol
Before editing, read applicable rules, inspect Git status and relevant files, trace data flow, and identify company and authorization boundaries. Ask before ambiguous money, inventory, payment, tax, or accounting behavior. Report implemented behavior, changed files, verification, assumptions, and risks; never claim tests passed unless they ran successfully.
