<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerContactController;
use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('company/{company}/customer/create',[CustomerController::class,'create'])
    ->name('customer.create');
Route::post('company/{company}/customer', [CustomerController::class, 'store'])
    ->name('customer.store');
//address
Route::get('customer/{customer}/address/create',[CustomerAddressController::class, 'create'])
    ->name('address.create');
Route::post('customer/{customer}/address',[CustomerAddressController::class, 'store'])
    ->name('address.store');

//contacts
Route::get('customer/{customer}/contact/create',[CustomerContactController::class, 'create'])
    ->name('contact.create');
Route::post('customer/{customer}/contact',[CustomerContactController::class, 'store'])
    ->name('contact.store');