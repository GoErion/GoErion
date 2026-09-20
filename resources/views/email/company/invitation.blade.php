<x-mail::message>
# You’re invited to {{ $invitation->company->company_name }}

{{ $invitation->invitedBy?->name ?? 'A company administrator' }} has invited you to collaborate on GoErion.

Review your invitation and the company roles selected for you. Sign in or create an account using the email address that received this message, then complete normal email verification.

<x-mail::button :url="$entryUrl">
Review invitation
</x-mail::button>

This invitation expires on **{{ $invitation->expires_at->format('d M Y, H:i') }} UTC** and can be accepted once. Opening the link does not join the company; you will confirm before access is granted.

If you weren’t expecting this invitation, you can ignore this email.

Thanks,<br>
The GoErion team
</x-mail::message>
