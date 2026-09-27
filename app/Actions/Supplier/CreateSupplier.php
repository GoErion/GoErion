<?php

declare(strict_types=1);

namespace App\Actions\Supplier;

use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Supplier\Supplier;
use App\Services\Company\NumberSequenceService;
use App\Services\Supplier\SupplierInput;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateSupplier
{
    public function __construct(private NumberSequenceService $numbers) {}

    /** @param array<string, mixed> $values */
    public function handle(Company $company, User $actor, array $values): Supplier
    {
        return DB::transaction(function () use ($company, $actor, $values): Supplier {
            $company = Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();
            SupplierMutation::authorize($company, $actor, 'create');
            if (array_intersect(['currency', 'payment_terms_days', 'lead_time_days'], array_keys($values)) !== []) {
                SupplierMutation::authorize($company, $actor, 'manageTerms');
            }
            $values = Validator::make(SupplierInput::normalize($values), [...SupplierInput::profile(), ...SupplierInput::terms(false)])->validate();
            $currency = strtoupper((string) $company->currency);
            Validator::make(['currency' => $currency], ['currency' => SupplierInput::terms()['currency']])->validate();
            $sequence = $company->numberSequences()->where('type', 'supplier')->lockForUpdate()->first();
            if ($sequence === null) {
                $maximum = 0;
                foreach ($company->suppliers()->pluck('code') as $legacyCode) {
                    if (preg_match('/^SUP-[0-9]{4}-([0-9]{6,})$/D', $legacyCode, $match)) {
                        if (strlen(ltrim($match[1], '0')) > 10 || (int) $match[1] >= 2147483646) {
                            throw new DomainException('Supplier number sequence is exhausted.');
                        }
                        $maximum = max($maximum, (int) $match[1]);
                    }
                }
                $sequence = $company->numberSequences()->create(['type' => 'supplier', 'prefix' => 'SUP', 'next_number' => $maximum + 1]);
            }
            do {
                $sequence->refresh();
                if ($sequence->prefix !== 'SUP' || $sequence->next_number < 1 || $sequence->next_number >= 2147483647) {
                    throw new DomainException('Supplier number sequence is invalid or exhausted.');
                }
                $code = $this->numbers->next($sequence);
            } while ($company->suppliers()->where('code', $code)->exists());

            return $company->suppliers()->create([
                ...$values, 'code' => $code, 'currency' => $values['currency'] ?? $currency,
                'payment_terms_days' => $values['payment_terms_days'] ?? 0,
                'lead_time_days' => $values['lead_time_days'] ?? null, 'is_active' => true,
            ]);
        }, 3);
    }
}
