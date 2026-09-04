<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\Company;

final readonly class UpdateCompany
{
    /** @param array{company_name: string, legal_name?: ?string, registration_number?: ?string, tax_number?: ?string, email?: ?string, phone?: ?string, address?: ?string} $values */
    public function handle(Company $company, array $values): Company
    {
        $company->update($values);

        return $company->refresh();
    }
}
