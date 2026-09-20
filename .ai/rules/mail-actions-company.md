---
paths:
  - 'app/Services/Company/SendCompanyInvitation.php'
  - 'app/Mail/CompanyInvitationMail.php'
  - 'app/Actions/Company/InvitationDelivery.php'
---

# Mail Actions Company

## Validate effective invitation mail transports
Validate Laravel’s effective mail configuration, including URL overrides, before resolving transports or consuming credentials. Reject diagnostic, non-delivery, ambiguous and composite transports. Send synchronously after commit; prohibit serialization and reporting credential-bearing transport exceptions. Permit the bearer credential only in the intended invitation email entry URL.
