<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

/** HTTP invitation integration remains disabled until Phase 2B2B. */
final class CompanyInvitationController extends Controller
{
    public function create(): never
    {
        abort(404);
    }

    public function store(): never
    {
        abort(404);
    }

    public function accept(): never
    {
        abort(404);
    }

    public function confirm(): never
    {
        abort(404);
    }
}
