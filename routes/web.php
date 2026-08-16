<?php

use App\Http\Controllers\Auth\Profile\ProfileController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\WarehouseController;
use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/',[HomeController::class,'home'])
    ->name('home');
//include auth route
require __DIR__ . '/auth.php';
//profile
Route::get('/profile', [ProfileController::class, 'edit'])
    ->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])
    ->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])
    ->name('profile.destroy');

    //company
Route::get('company',[CompanyController::class,'create'])
    ->name('company.create');
Route::post('company', [CompanyController::class, 'store'])
    ->name('company');
    //branch
Route::get('company/{company}/branch/create',[BranchController::class,'create'])
    ->name('branch.create');
Route::post('company/{company}/branch/create', [BranchController::class, 'store'])
    ->name('branch.store');
    //warehouse
Route::get('company/{company}/warehouse/create',[WarehouseController::class,'create'])
    ->name('warehouse.create');
Route::post('company/{company}/warehouse/create', [WarehouseController::class, 'store'])
    ->name('warehouse');