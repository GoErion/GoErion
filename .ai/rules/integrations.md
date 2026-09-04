---
paths:
  - 'app/Services/Integrations/**'
---

# Integrations

## Focused Integration Boundaries
Create a focused integration boundary only when an integration exists. Validate webhook signatures, prevent replay, keep secrets out of payload logs and URLs, and do not contact external services without permission.
