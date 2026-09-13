<?php

declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends Builder<CompanyInvitation> */
final class CompanyInvitationBuilder extends Builder
{
    public function delete(): never
    {
        throw new LogicException('Invitations must be retained for history.');
    }

    public function forceDelete(): never
    {
        throw new LogicException('Invitations must be retained for history.');
    }

    public function truncate(): never
    {
        throw new LogicException('Invitations must be retained for history.');
    }
}
