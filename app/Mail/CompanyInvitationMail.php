<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Company\CompanyInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use LogicException;

final class CompanyInvitationMail extends Mailable implements \JsonSerializable
{
    public function __construct(public readonly CompanyInvitation $invitation, #[\SensitiveParameter] private readonly string $entryUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Join '.$this->invitation->company->company_name.' on GoErion');
    }

    public function content(): Content
    {
        return new Content(markdown: 'email.company.invitation', with: ['entryUrl' => $this->entryUrl]);
    }

    public function __serialize(): array
    {
        throw new LogicException('Invitation mail must be sent synchronously.');
    }

    public function jsonSerialize(): never
    {
        throw new LogicException('Invitation mail must remain in memory.');
    }

    public function __debugInfo(): array
    {
        return ['invitation_uuid' => $this->invitation->uuid];
    }
}
