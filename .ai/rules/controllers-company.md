---
paths:
  - 'app/Http/Middleware/ProtectInvitationPrivacy.php'
  - 'app/Http/Controllers/Company/InvitationClaimController.php'
  - 'bootstrap/app.php'
  - 'routes/web.php'
---

# Controllers Company

## Sanitize invitation entry requests before session and diagnostics
Identify invitation entry by matched route name. Before session handling and diagnostic collection, replace credential-bearing request data with a server-created digest claim. Redirect immediately to the token-free review route; GET must not consume invitations. Apply privacy headers to success, redirects and failures. Upstream logging requires separate query redaction.
