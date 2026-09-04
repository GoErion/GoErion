---
paths:
  - app/Services/Company/NumberSequenceService.php
---

# Services Company

## Transactional Number Sequences
Reuse NumberSequenceService with its transactional row lock and company/document-type identity. Preserve configured prefix, year, six-digit padding, and the format PREFIX-YEAR-000001, for example INV-2026-000001; do not reset annually unless approved.
