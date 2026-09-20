<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Validation\Rule;

final class InvitationIndexRequest extends ManageInvitationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'cancelled', 'expired'])],
        ];
    }
}
