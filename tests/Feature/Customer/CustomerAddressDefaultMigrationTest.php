<?php

declare(strict_types=1);

use Database\Factories\CustomerFactory;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshCustomerDatabase;

uses(RefreshCustomerDatabase::class);

it('normalizes only missing and duplicate address defaults deterministically', function () {
    $customer = CustomerFactory::new()->create();
    $billing = [
        $customer->addresses()->create(['type' => 'billing', 'line_1' => 'First', 'is_default' => true]),
        $customer->addresses()->create(['type' => 'billing', 'line_1' => 'Second', 'is_default' => true]),
        $customer->addresses()->create(['type' => 'billing', 'line_1' => 'Third', 'is_default' => false]),
    ];
    $shipping = [
        $customer->addresses()->create(['type' => 'shipping', 'line_1' => 'First', 'is_default' => false]),
        $customer->addresses()->create(['type' => 'shipping', 'line_1' => 'Second', 'is_default' => false]),
    ];
    $other = $customer->addresses()->create(['type' => 'other', 'line_1' => 'Existing', 'is_default' => true]);

    $migration = require database_path('migrations/2026_09_22_023730_normalize_customer_address_defaults.php');
    $migration->up();

    expect($billing[0]->fresh()->is_default)->toBeTrue()
        ->and($billing[1]->fresh()->is_default)->toBeFalse()
        ->and($billing[2]->fresh()->is_default)->toBeFalse()
        ->and($shipping[0]->fresh()->is_default)->toBeTrue()
        ->and($shipping[1]->fresh()->is_default)->toBeFalse()
        ->and($other->fresh()->is_default)->toBeTrue()
        ->and($customer->addresses()->where('is_default', true)->count())->toBe(3);

    $migration->down();
    expect($customer->addresses()->where('is_default', true)->count())->toBe(3)
        ->and(DB::table('customer_addresses')->count())->toBe(6);
});
