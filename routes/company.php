<?php
declare(strict_types=1);

use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\SettingController;
use App\Http\Controllers\Company\WarehouseController;
use App\Http\Controllers\Company\WarehouseLocationController;
use Illuminate\Support\Facades\Route;

//company
Route::get('company', [CompanyController::class, 'create'])
    ->name('company.create');
Route::post('company', [CompanyController::class, 'store'])
    ->name('company');
//branch
Route::get('company/{company}/branch/create', [BranchController::class, 'create'])
    ->name('branch.create');
Route::post('company/{company}/branch', [BranchController::class, 'store'])
    ->name('branch.store');
//warehouse
Route::get('company/{company}/warehouse/create', [WarehouseController::class, 'create'])
    ->name('warehouse.create');
Route::post('company/{company}/warehouse', [WarehouseController::class, 'store'])
    ->name('warehouse');
    //warehouse locations
Route::get('warehouse/{warehouse}/location/create',[WarehouseLocationController::class,'create'])
    ->name('location.create');
Route::post('warehouse/{warehouse}/location', [WarehouseLocationController::class, 'store'])
    ->name('location');
    //settings
Route::get('company/{company}/settings/edit',[SettingController::class,'edit'])
    ->name('settings.edit');
Route::put('company/{company}/settings', [SettingController::class, 'update'])
    ->name('settings');