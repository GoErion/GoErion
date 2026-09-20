---
paths:
  - 'app/Actions/Auth/ResolveAuthenticatedDestination.php'
  - 'app/Http/Controllers/Auth/LoginController.php'
  - 'app/Http/Controllers/Auth/RegisterController.php'
  - 'app/Http/Controllers/Auth/EmailVerificationPromptController.php'
  - 'app/Http/Controllers/Auth/VerifyEmailController.php'
  - 'app/Http/Controllers/Auth/EmailVerificationNotificationController.php'
---

# Controllers Auth

## Resume invitation review through authentication and verification
Preserve only the server-held invitation claim through normal authentication and verification. Registration must still require verification. Verified continuation branches resolve valid claims to review. Acceptance independently requires a verified matching account; clear claims on success and terminal rejection. Preserve ordinary authentication behavior and safe redirects.
