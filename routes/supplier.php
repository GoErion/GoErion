<?php

use App\Http\Controllers\Supplier\SupplierAddressController;
use App\Http\Controllers\Supplier\SupplierContactController;
use App\Http\Controllers\Supplier\SupplierController;
use Illuminate\Support\Facades\Route;


Route::get('company/{company}/supplier/create',[SupplierController::class, 'create'])
    ->name('supplier.create');
Route::post('company/{company}/supplier',[SupplierController::class, 'store'])
    ->name('supplier.store');
    
//address
Route::get('supplier/{supplier}/address/create',[SupplierAddressController::class, 'create'])
    ->name('supplier.address.create');
Route::post('supplier/{supplier}/address',[SupplierAddressController::class, 'store'])
    ->name('supplier.address.store');

//contacts
Route::get('supplier/{supplier}/contact/create',[SupplierContactController::class, 'create'])
    ->name('supplier.contact.create');
Route::post('supplier/{supplier}/contact',[SupplierContactController::class, 'store'])
    ->name('supplier.contact.store');