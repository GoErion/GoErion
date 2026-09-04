---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## Form Request Responsibilities
Form Requests own request authorization and validation. Use only validated input, never pass request all-data into actions or models, and scope tenant unique and exists rules to the authorized company, including correct update exclusions.

## Tenant Isolation — Requests
Never accept client ownership as authoritative. Scope related-record existence and tenant-specific uniqueness to the authorized company, and prevent mass assignment of company, balance, status, approval, posting, and audit fields.
