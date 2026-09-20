---
paths:
  - 'app/Mail/**'
---

# Mail

## Committed and Secret-Safe Mail
Send mail only after dependent data commits. Never place secrets or sensitive customer, supplier, or financial data in logs, source control, or exception messages. Keep ordinary secrets and credentials out of URLs; the sole exception is the restricted company invitation bearer link described below.

## Restrict invitation bearer-link URLs
The only credential permitted in a URL is the intentional, short-lived, single-use company invitation bearer link in the intended email and initial invitation entry request. Persist only its digest; keep raw-token handling transient and in memory for delivery and entry scrubbing. Application middleware must scrub the raw token before session handling and diagnostics. Production deployment requires proxy/CDN/WAF/web-server invitation-query logging to be redacted or disabled. This exception never permits passwords, API keys, general secrets, or persistent credentials in URLs.
