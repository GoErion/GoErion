---
paths:
  - 'app/**/Customer/**'
---

# Customer

## Customer and Receivables Boundaries
Keep customer identity, contacts, company accounts, credit, terms, balances, receivables, statements, and history within the Customer domain. Credit approval, overdue behavior, deposits, terms, and balance calculations require explicit approval.

## Commercial terms require separate permission
Customer credit_limit and payment_terms_days require customers.credit.manage for HTTP requests and direct commercial-term Actions. Ordinary customers.update cannot alter them. Zero credit means no credit; zero days means immediately due. Do not infer balance, due-date, or enforcement workflows from these fields.
