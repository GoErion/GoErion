<?php
declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Customer\Customer;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomerAddress
{
    public function handle(array $input,Customer $customer)
    {
        return DB::transaction(function() use($input,$customer)
        {
            if ($input['is_default'] ?? false)
            {
                $customer->addresses()
                    ->where('type',$input['type'])
                    ->update(['is_default' => false]);
                
                return $customer->addresses()->create(
                    [
                        'type' => $input['type'],
                        'line_1' => $input['line_1'],
                        'line_2' => $input['line_2'] ?? null,
                        'city' => $input['city'] ?? null,
                        'postal_code' => $input['postal_code'] ?? null,
                        'country' => $input['country'] ?? null,
                        'is_default' => $input['is_default'] ?? false,
                    ]);
            }
        });
    }
}
