<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\CompanyInvitation;
use JsonSerializable;
use LogicException;

final class InvitationDelivery implements JsonSerializable
{
    public function __construct(public readonly CompanyInvitation $invitation, #[\SensitiveParameter] private ?string $rawToken) {}

    public function takeToken(): string
    {
        $token = $this->rawToken ?? throw new LogicException('Invitation delivery token was already consumed.');
        $this->rawToken = null;

        return $token;
    }

    public function __serialize(): array
    {
        throw new LogicException('Invitation delivery must remain in memory.');
    }

    public function jsonSerialize(): never
    {
        throw new LogicException('Invitation delivery must remain in memory.');
    }

    public function __debugInfo(): array
    {
        return ['invitation_uuid' => $this->invitation->uuid];
    }
}
