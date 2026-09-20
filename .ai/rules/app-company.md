---
paths:
  - 'app/**/Company/**'
  - 'app/**/Company/*Invitation*.php'
---

# App Company

## Company Domain Boundaries
Keep company profiles, settings, branches, warehouses, locations, number sequences, membership, and company configuration within the Company domain. Use company_name as the actual company-name field and resolve key/value settings by key or an approved access layer.

## Keep invitation credentials digest-only
Company invitation bearer tokens contain at least 256 bits of cryptographic randomness. Within GoErion application processing and storage, persist only the invitation token digest. A raw invitation token may exist transiently only in the in-memory InvitationDelivery result, the synchronously rendered invitation email sent to its intended recipient, and the initial invitation-entry request before privacy middleware replaces it with a digest claim. Never persist the raw token in the database, session, cookies, cache, queues, flash data, validation data, logs, diagnostics or exception context. Upstream proxy, CDN, WAF and web-server logging must redact or omit the credential-bearing entry query before production deployment. Resend must rotate the digest so previous links become invalid.
