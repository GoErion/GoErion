<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Company Invitation</title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #020617;
        font-family: Arial, sans-serif;
    "
>

<div style="max-width: 600px; margin: 40px auto; padding: 20px;">

    <div
        style="
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 32px;
            color: #ffffff;
        "
    >

        <h1 style="margin-top: 0;">
            You're invited
        </h1>

        <p style="color: #94a3b8;">
            You have been invited to join
            <strong style="color: #ffffff;">
                {{ $invitation->company->company_name }}
            </strong>
            on GoErion.
        </p>

        <div
            style="
                margin-top: 24px;
                padding: 20px;
                background-color: #020617;
                border-radius: 14px;
            "
        >

            <p style="margin: 0; color: #64748b;">
                Company
            </p>

            <p style="margin-top: 6px; font-size: 18px;">
                {{ $invitation->company->company_name }}
            </p>

            <p style="margin-top: 20px; color: #64748b;">
                Role
            </p>

            <p style="margin-top: 6px; font-size: 18px;">
                {{ $invitation->role->display_name ?? $invitation->role->name }}
            </p>

        </div>

        <p style="margin-top: 24px; color: #94a3b8;">
            Click the button below to accept your invitation.
        </p>

        <div style="margin-top: 28px;">

            <a
                href="{{ route('invitations.accept', [
                    'token' => $invitation->token,
                ]) }}"
                style="
                    display: inline-block;
                    padding: 14px 24px;
                    background-color: #047857;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: bold;
                "
            >
                Accept Invitation
            </a>

        </div>

        <p
            style="
                margin-top: 30px;
                color: #64748b;
                font-size: 13px;
            "
        >
            This invitation expires on
            {{ $invitation->expires_at->format('M d, Y H:i') }}.
        </p>

    </div>

</div>

</body>
</html>