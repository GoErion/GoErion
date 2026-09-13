<?php

declare(strict_types=1);

namespace App\Services\Company;

final class InvitationToken
{
    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public function digest(#[\SensitiveParameter] string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
