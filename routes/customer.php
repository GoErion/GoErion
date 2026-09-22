<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerContactController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('companies/{company}')->name('company.')->group(function (): void {
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('customers/{customer}/commercial-terms', [CustomerController::class, 'updateCommercialTerms'])->name('customers.commercial-terms.update');
    Route::patch('customers/{customer}/archive', [CustomerController::class, 'archive'])->name('customers.archive');
    Route::patch('customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');

    Route::get('customer-groups', [CustomerGroupController::class, 'index'])->name('customer-groups.index');
    Route::get('customer-groups/create', [CustomerGroupController::class, 'create'])->name('customer-groups.create');
    Route::post('customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store');
    Route::get('customer-groups/{group}/edit', [CustomerGroupController::class, 'edit'])->name('customer-groups.edit');
    Route::patch('customer-groups/{group}', [CustomerGroupController::class, 'update'])->name('customer-groups.update');
    Route::patch('customer-groups/{group}/archive', [CustomerGroupController::class, 'archive'])->name('customer-groups.archive');
    Route::patch('customer-groups/{group}/restore', [CustomerGroupController::class, 'restore'])->name('customer-groups.restore');

    Route::get('customers/{customer}/contacts/create', [CustomerContactController::class, 'create'])->name('customers.contacts.create');
    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store'])->name('customers.contacts.store');
    Route::get('customers/{customer}/contacts/{contact}/edit', [CustomerContactController::class, 'edit'])->name('customers.contacts.edit');
    Route::patch('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update'])->name('customers.contacts.update');
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy'])->name('customers.contacts.destroy');
    Route::patch('customers/{customer}/contacts/{contact}/primary', [CustomerContactController::class, 'primary'])->name('customers.contacts.primary');

    Route::get('customers/{customer}/addresses/create', [CustomerAddressController::class, 'create'])->name('customers.addresses.create');
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
    Route::get('customers/{customer}/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('customers.addresses.edit');
    Route::patch('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');
    Route::patch('customers/{customer}/addresses/{address}/default', [CustomerAddressController::class, 'default'])->name('customers.addresses.default');
});
