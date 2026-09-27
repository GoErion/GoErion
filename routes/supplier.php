<?php

declare(strict_types=1);

use App\Http\Controllers\Supplier\SupplierAddressController;
use App\Http\Controllers\Supplier\SupplierContactController;
use App\Http\Controllers\Supplier\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('companies/{company}')->name('company.')->group(function (): void {
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::patch('suppliers/{supplier}/commercial-terms', [SupplierController::class, 'updateCommercialTerms'])->name('suppliers.commercial-terms.update');
    Route::patch('suppliers/{supplier}/archive', [SupplierController::class, 'archive'])->name('suppliers.archive');
    Route::patch('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');

    Route::get('suppliers/{supplier}/contacts/create', [SupplierContactController::class, 'create'])->name('suppliers.contacts.create');
    Route::post('suppliers/{supplier}/contacts', [SupplierContactController::class, 'store'])->name('suppliers.contacts.store');
    Route::get('suppliers/{supplier}/contacts/{contact}/edit', [SupplierContactController::class, 'edit'])->name('suppliers.contacts.edit');
    Route::patch('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'update'])->name('suppliers.contacts.update');
    Route::delete('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'destroy'])->name('suppliers.contacts.destroy');
    Route::patch('suppliers/{supplier}/contacts/{contact}/primary', [SupplierContactController::class, 'primary'])->name('suppliers.contacts.primary');

    Route::get('suppliers/{supplier}/addresses/create', [SupplierAddressController::class, 'create'])->name('suppliers.addresses.create');
    Route::post('suppliers/{supplier}/addresses', [SupplierAddressController::class, 'store'])->name('suppliers.addresses.store');
    Route::get('suppliers/{supplier}/addresses/{address}/edit', [SupplierAddressController::class, 'edit'])->name('suppliers.addresses.edit');
    Route::patch('suppliers/{supplier}/addresses/{address}', [SupplierAddressController::class, 'update'])->name('suppliers.addresses.update');
    Route::delete('suppliers/{supplier}/addresses/{address}', [SupplierAddressController::class, 'destroy'])->name('suppliers.addresses.destroy');
    Route::patch('suppliers/{supplier}/addresses/{address}/default', [SupplierAddressController::class, 'default'])->name('suppliers.addresses.default');
});
